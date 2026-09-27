<?php

namespace Tests\Feature;

use App\Models\AIReporter;
use App\Services\AutomationQuotaService;
use App\Services\PexelsImageService;
use Botble\Blog\Models\Post;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\Concerns\CreatesPosts;
use Tests\TestCase;
use Tests\Traits\RefreshDatabaseWithPlugins;

/**
 * The agent-facing surface added on top of the publishing API:
 *
 *  - idempotency keys, so a retry after a timeout returns the original article
 *    instead of publishing a duplicate
 *  - POST read-back (GET /posts/{postId}), so an agent can confirm what actually
 *    exists rather than assuming a 201 meant success
 *  - GET /opportunities, the newsroom briefing
 *  - the rate limits that GET /status had always advertised but nothing enforced
 *
 * Every test fakes outbound HTTP and the image service. A successful publish
 * otherwise reaches Pexels and the search-engine pingers for real, which would
 * make this suite slow and dependent on the network.
 */
class AutomationAgentFeaturesTest extends TestCase
{
    use CreatesPosts;
    use RefreshDatabaseWithPlugins;

    private const BASE = '/api/v1/automation';

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake();

        // A partial mock, not a full one: buildImageGuidance() calls
        // prepareImageGuidance() for real, and a stub returning null there breaks the
        // request long before the publish is reached. Only the fetch is stubbed, so
        // no image is downloaded and no media file is written.
        $this->partialMock(PexelsImageService::class, function ($mock): void {
            $mock->shouldReceive('fetchAndUploadImage')->andReturn(null);
        });

        // The rate limiter lives in the cache store.
        Cache::flush();
    }

    // -----------------------------------------------------------------
    // Idempotency
    // -----------------------------------------------------------------

    public function test_an_identical_retry_returns_the_original_article_instead_of_a_duplicate(): void
    {
        $token = $this->reporterToken();
        $payload = $this->article();

        $first = $this->publish($token, 'retry-key-000001', $payload)->assertCreated();
        $postId = $first->json('data.id');

        $replay = $this->publish($token, 'retry-key-000001', $payload);

        $replay->assertOk();
        $replay->assertJsonPath('idempotent_replay', true);
        $replay->assertJsonPath('data.id', $postId);

        // The whole point: the second request must not have created anything.
        $this->assertSame(1, Post::where('name', $payload['title'])->count());
    }

    public function test_a_replay_is_not_just_a_cache_hit_and_survives_a_flushed_cache(): void
    {
        $token = $this->reporterToken();
        $payload = $this->article();

        $postId = $this->publish($token, 'retry-key-000002', $payload)->assertCreated()->json('data.id');

        // A replay has to work after a deploy or a cache flush, so the key is also
        // recorded on the post itself.
        Cache::flush();

        $replay = $this->publish($token, 'retry-key-000002', $payload);

        $replay->assertOk();
        $replay->assertJsonPath('data.id', $postId);
        $this->assertSame(1, Post::where('name', $payload['title'])->count());
    }

    public function test_reusing_a_key_for_a_different_article_is_rejected(): void
    {
        $token = $this->reporterToken();

        $this->publish($token, 'retry-key-000003', $this->article())->assertCreated();

        $changed = $this->article([
            'title' => 'A Different Headline About The Same Enterprise Policy Update',
            'focus_keyword' => 'enterprise verification controls',
        ]);

        $response = $this->publish($token, 'retry-key-000003', $changed);

        $response->assertStatus(409);
        $response->assertJsonPath('success', false);
        $response->assertJsonStructure(['existing_post' => ['id', 'url']]);

        $this->assertSame(1, Post::count(), 'A key reuse must not publish a second article.');
    }

    public function test_an_idempotency_key_is_scoped_to_the_reporter_that_used_it(): void
    {
        $first = $this->reporterToken();
        $second = $this->reporterToken();

        $postId = $this->publish($first, 'shared-key-00001', $this->article())->assertCreated()->json('data.id');

        // The same key from a different reporter is a different submission, so it must
        // never be answered from the first reporter's article.
        $other = $this->publish($second, 'shared-key-00001', $this->article());

        $this->assertNull($other->json('idempotent_replay'), 'A key must not leak one reporter\'s article to another.');
        $this->assertNotSame($postId, $other->json('data.id'));
    }

    public function test_a_malformed_idempotency_key_is_rejected_without_publishing(): void
    {
        $token = $this->reporterToken();

        foreach (['short', str_repeat('x', 256), 'has spaces in it'] as $bad) {
            $response = $this->publish($token, $bad, $this->article());
            $response->assertStatus(422);
            $this->assertArrayHasKey('idempotency_key', $response->json('errors'));
        }

        $this->assertSame(0, Post::count());
    }

    public function test_publishing_without_a_key_still_works(): void
    {
        $token = $this->reporterToken();

        $this->publish($token, null, $this->article())->assertCreated();

        $this->assertSame(1, Post::count());
    }

    public function test_idempotency_is_opt_in_so_a_repeat_without_a_key_is_not_a_replay(): void
    {
        $token = $this->reporterToken();
        $payload = $this->article();

        $this->publish($token, null, $payload)->assertCreated();

        // Without a key the request is just another submission. It is refused by the
        // pre-existing duplicate gate (409) - crucially it is NOT answered with a 200
        // replay, which would tell an agent its article was already published.
        $repeat = $this->publish($token, null, $payload);

        $repeat->assertStatus(409);
        $this->assertNull($repeat->json('idempotent_replay'));
        $this->assertSame(1, Post::count());
    }

    // -----------------------------------------------------------------
    // Read-back
    // -----------------------------------------------------------------

    public function test_read_back_returns_your_own_submission_with_the_recorded_verdict(): void
    {
        $token = $this->reporterToken();
        $created = $this->publish($token, 'read-back-key-01', $this->article())->assertCreated();
        $postId = $created->json('data.id');

        $response = $this->getJson(self::BASE . '/posts/' . $postId, ['X-API-Token' => $token]);

        $response->assertOk();
        $response->assertJsonPath('data.id', $postId);
        $response->assertJsonPath('data.status', 'published');
        $response->assertJsonPath('data.review.is_published', true);
        $response->assertJsonPath('data.review.has_reporter_byline', true);

        // The score the article was accepted on must be readable afterwards, not 0.
        $this->assertIsInt($response->json('data.seo.score'));
        $this->assertGreaterThanOrEqual(80, $response->json('data.seo.score'));
        $this->assertSame('OpenAI enterprise policy update', $response->json('data.focus_keyword'));
        $this->assertNotEmpty($response->json('data.url'));
    }

    public function test_read_back_hides_another_reporters_post(): void
    {
        $token = $this->reporterToken();
        $someoneElses = $this->createPost(['name' => 'A Story From Another Desk']);

        $this->getJson(self::BASE . '/posts/' . $someoneElses->id, ['X-API-Token' => $token])
            ->assertStatus(404);
    }

    public function test_read_back_requires_a_token(): void
    {
        $post = $this->createPost(['name' => 'A Story From Another Desk']);

        $this->getJson(self::BASE . '/posts/' . $post->id)->assertStatus(401);
    }

    public function test_a_post_published_without_a_recorded_score_reports_null_rather_than_zero(): void
    {
        $reporter = $this->reporter();
        $post = $this->createPost(['name' => 'Legacy Article Without A Stored Score']);

        DB::table('meta_boxes')->insert([
            'reference_type' => Post::class,
            'reference_id' => $post->id,
            'meta_key' => 'ai_reporter_id',
            // Metadata is stored JSON-encoded, exactly as MetaBox::saveMetaBoxData writes it.
            'meta_value' => json_encode([(string) $reporter->id]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->getJson(self::BASE . '/posts/' . $post->id, ['X-API-Token' => $reporter->api_token]);

        $response->assertOk();
        // getMetaData() returns '' for a missing key, so a naive cast reports 0 and
        // an agent would read "scored zero" instead of "not recorded".
        $this->assertNull($response->json('data.seo.score'));
        $response->assertJsonPath('data.review.idempotency_key_recorded', false);
    }

    // -----------------------------------------------------------------
    // Briefing
    // -----------------------------------------------------------------

    public function test_the_briefing_reports_busy_and_neglected_beats(): void
    {
        $token = $this->reporterToken();

        $busy = $this->createCategory(['name' => 'Busy Beat']);
        $this->createCategory(['name' => 'Neglected Beat']);

        foreach ([1, 2, 3] as $index) {
            $post = $this->createPost(['name' => "Busy beat story {$index}"]);
            $post->categories()->attach($busy->id);
        }

        $response = $this->getJson(self::BASE . '/opportunities', ['X-API-Token' => $token]);

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'trending_categories',
                'under_covered_categories',
                'recently_published',
                'your_recent_coverage' => ['posts_last_7d', 'categories_last_7d', 'recent_titles'],
            ],
        ]);

        $trending = collect($response->json('data.trending_categories'));
        $neglected = collect($response->json('data.under_covered_categories'));

        $this->assertSame(3, $trending->firstWhere('name', 'Busy Beat')['posts_last_48h']);
        // Zero posts in the window is the strongest gap signal, so it sorts first.
        $this->assertSame('Neglected Beat', $neglected->first()['name']);
        $this->assertSame(0, $neglected->first()['posts_last_7d']);
    }

    public function test_the_briefing_decodes_html_escaped_category_names(): void
    {
        $token = $this->reporterToken();

        // Three production categories are stored HTML-escaped. Anything reading
        // them through a raw query has to decode, or the same beat is reported
        // under two different names depending on the endpoint.
        $category = $this->createCategory(['name' => 'Tech &amp; Games']);

        $post = $this->createPost(['name' => 'An escaped name story']);
        $post->categories()->attach($category->id);

        $response = $this->getJson(self::BASE . '/opportunities', ['X-API-Token' => $token])->assertOk();

        $names = collect($response->json('data.trending_categories'))
            ->merge($response->json('data.under_covered_categories'))
            ->pluck('name');

        $this->assertTrue($names->contains('Tech & Games'));
        $this->assertFalse($names->contains('Tech &amp; Games'));
    }

    public function test_the_briefing_requires_a_token(): void
    {
        $this->getJson(self::BASE . '/opportunities')->assertStatus(401);
    }

    // -----------------------------------------------------------------
    // Quota and rate limits
    // -----------------------------------------------------------------

    public function test_me_reports_the_enforced_quota(): void
    {
        $token = $this->reporterToken();

        $response = $this->getJson(self::BASE . '/me', ['X-API-Token' => $token])->assertOk();

        $response->assertJsonPath('quota.publishes.limit', config('automation.rate_limits.publishes_per_hour'));
        $response->assertJsonPath('quota.publishes.window', 'hour');
        $response->assertJsonPath('quota.reads.window', 'minute');
        $this->assertIsInt($response->json('quota.publishes.remaining'));
    }

    public function test_status_reports_the_limits_that_are_actually_enforced(): void
    {
        config([
            'automation.rate_limits.publishes_per_hour' => 7,
            'automation.rate_limits.registrations_per_hour' => 3,
        ]);

        $status = $this->getJson(self::BASE . '/status')->assertOk();

        // /status used to advertise limits that no middleware enforced. It now reads
        // them from the same config the named limiters use, so it cannot drift again.
        $status->assertJsonPath('limits.max_posts_per_hour', 7);
        $status->assertJsonPath('limits.max_registrations_per_hour', 3);
        $status->assertJsonPath('features.rate_limiting', true);
    }

    public function test_publish_quota_is_enforced_and_reported_in_the_headers(): void
    {
        config(['automation.rate_limits.publishes_per_hour' => 2]);

        $token = $this->reporterToken();
        // An invalid body still goes through the throttle middleware, which keeps the
        // test fast while proving the limit is real rather than advertised.
        $payload = ['title' => 'too short'];

        $this->postJson(self::BASE . '/posts/create', $payload, ['X-API-Token' => $token])
            ->assertStatus(422)
            ->assertHeader('X-RateLimit-Limit', '2');

        $this->postJson(self::BASE . '/posts/create', $payload, ['X-API-Token' => $token])
            ->assertStatus(422);

        $limited = $this->postJson(self::BASE . '/posts/create', $payload, ['X-API-Token' => $token]);

        $limited->assertStatus(429);
        $limited->assertJsonPath('success', false);
        $limited->assertHeader('X-RateLimit-Limit', '2');
        $this->assertNotNull($limited->headers->get('Retry-After'));
        $this->assertNotNull($limited->headers->get('X-RateLimit-Reset'));
    }

    public function test_the_publish_quota_is_counted_per_reporter_not_globally(): void
    {
        config(['automation.rate_limits.publishes_per_hour' => 1]);

        $payload = ['title' => 'too short'];

        $this->postJson(self::BASE . '/posts/create', $payload, ['X-API-Token' => $this->reporterToken()])
            ->assertStatus(422);

        // A different reporter has their own bucket and must not inherit the throttle.
        $this->postJson(self::BASE . '/posts/create', $payload, ['X-API-Token' => $this->reporterToken()])
            ->assertStatus(422);
    }

    public function test_registration_is_rate_limited(): void
    {
        config(['automation.rate_limits.registrations_per_hour' => 1]);

        $payload = [
            'description' => 'We cover artificial intelligence and technology news for young readers.',
            'workflow_summary' => 'We research primary sources and write finished articles directly, with no scripts.',
            'publishing_mode' => 'direct_article_submission',
            'agrees_no_code_deliverables' => true,
            'agrees_editorial_standard' => true,
        ];

        $this->postJson(self::BASE . '/register', $payload)->assertCreated();
        $this->postJson(self::BASE . '/register', $payload)->assertStatus(429);
    }

    public function test_reads_are_rate_limited_per_minute(): void
    {
        config(['automation.rate_limits.reads_per_minute' => 2]);

        $token = $this->reporterToken();

        $this->getJson(self::BASE . '/me', ['X-API-Token' => $token])->assertOk();
        $this->getJson(self::BASE . '/me', ['X-API-Token' => $token])->assertOk();

        $limited = $this->getJson(self::BASE . '/me', ['X-API-Token' => $token]);

        $limited->assertStatus(429);
        $limited->assertHeader('X-RateLimit-Limit', '2');
    }

    public function test_the_quota_service_hashes_the_token_instead_of_storing_it(): void
    {
        $token = $this->reporterToken();
        $request = \Illuminate\Http\Request::create('/x', 'GET', [], [], [], ['HTTP_X_API_TOKEN' => $token]);

        $key = app(AutomationQuotaService::class)->key(AutomationQuotaService::READ, $request);

        // The production cache store is the file driver, so the raw credential must
        // never reach a cache key.
        $this->assertStringNotContainsString($token, $key);
        $this->assertStringStartsWith(AutomationQuotaService::READ . ':token:', $key);
    }

    // -----------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------

    protected function reporter(): AIReporter
    {
        $suffix = Str::lower(Str::random(8));

        return AIReporter::create([
            'name' => 'Agent Features Probe',
            'email' => "agent-features-{$suffix}@example.test",
            'username' => "agent-features-{$suffix}",
            'password' => bcrypt('secret'),
            'api_token' => 'ai_' . Str::random(56),
            'status' => 'active',
            'is_verified' => true,
        ]);
    }

    protected function reporterToken(): string
    {
        return $this->reporter()->api_token;
    }

    /**
     * A finished article that clears the whole gate: SEO >= 80, 650 words, two H2s,
     * five paragraphs, an HTTPS source, an attribution phrase and 1-2% keyword
     * density. Kept in one place so every publish test uses the same contract.
     *
     * @return array<string, mixed>
     */
    protected function article(array $overrides = []): array
    {
        $body = implode('', [
            '<p>The OpenAI enterprise policy update reached enterprise customers this week, and administrators are already working through what it changes. The OpenAI enterprise policy update affects security controls, onboarding requirements and the cadence for account verification.</p>',
            '<h2>What Changed</h2>',
            '<p>The company said enterprise administrators will see stronger verification prompts and clearer audit controls. According to <a href="https://openai.com">OpenAI</a>, the changes are designed to reduce abuse while preserving access for legitimate teams.</p>',
            '<h2>Why It Matters</h2>',
            '<p>For publishers and software teams, the OpenAI enterprise policy update changes how quickly shared workspaces can be onboarded. GenZ NewZ covered related ground in <a href="https://genznewz.com/ai-news-reporter">its newsroom automation guide</a> and in <a href="https://genznewz.com/topic/tech-games">its technology coverage</a>, and the new detail clarifies responsible deployment. Editors comparing notes in <a href="https://genznewz.com/about-us">the newsroom policy page</a> will find the same emphasis on verified access.</p>',
            '<p>The OpenAI enterprise policy update also explains which settings administrators need to review before enabling production use. Editors, developers and operations teams are expected to coordinate around review workflows, verification states and support escalation paths.</p>',
            '<p>OpenAI enterprise policy update guidance creates operational questions for legal review, vendor management and newsroom access control. Teams that rely on shared publishing credentials now have to define clearer ownership so a change to verification status does not silently block production publishing windows.</p>',
            '<p>Another impact area is training. Editors and developers need onboarding material that explains what the verification prompts mean, how to escalate unexpected account changes, and how to confirm that logging and moderation alerts remain configured after workspace settings are updated.</p>',
            '<p>Security teams may welcome the OpenAI enterprise policy update because it forces more deliberate control over high-risk features. Product teams may see short-term friction, but the long-term result is usually a cleaner audit trail, fewer abandoned credentials and faster incident response when access needs to be reviewed.</p>',
            '<p>OpenAI enterprise policy update appears again here because the draft is meant to mimic a publishable article rather than a stuffed filler block. Search-focused structure, documented sources and clear sections work better than generic filler when automation pipelines are expected to meet editorial standards consistently.</p>',
            '<p>Readers also benefit from specificity. Instead of vague claims about safety, a stronger article explains who is affected, what changed in the workflow, why the update matters and which teams need to respond first. That approach makes the story more useful and gives search engines clearer topical signals about the real intent of the page.</p>',
            '<p>Implementation timing matters too. If enterprise teams do not stage changes, they can accidentally create bottlenecks for urgent publishing work, especially when multiple desks share a common authentication path. A better rollout uses documented checkpoints, fallback access and clear confirmation that monitoring, moderation and support ownership all remain intact after the policy changes take effect.</p>',
            '<p>That extra operational detail is what separates a shallow automation draft from a publishable article. Readers, editors and search engines all respond better when the piece demonstrates real subject coverage, references a source, connects to internal context and explains practical consequences instead of repeating broad claims.</p>',
            '<p>Procurement and governance teams are also part of the implementation story. They often need to validate contract terms, document retention practices and incident reporting obligations before approval workflows are finalized, which reduces launch delays and avoids emergency changes after production systems are already in use.</p>',
            '<p>A final operational point is change visibility. Teams should publish clear ownership maps for verification settings, approval logic and escalation paths so people know exactly where to respond when access behavior changes. According to documented enterprise rollout playbooks, visibility and accountability are usually the difference between stable adoption and recurring friction.</p>',
            '<p>Support desks should also prepare canned guidance before the OpenAI enterprise policy update lands in every workspace, because the first questions are predictable. Operators want to know which administrator receives the verification prompt, what happens to scheduled publishing during a review, and how to restore access quickly if a legitimate account is flagged. Documented answers reduce ticket volume and keep editorial schedules intact.</p>',
            '<p>Another practical takeaway is stakeholder sequencing. Technical owners, editorial leads, legal reviewers and security teams should align on checkpoints before launch so the OpenAI enterprise policy update is adopted consistently without producing avoidable publication delays.</p>',
        ]);

        return array_merge([
            'title' => 'OpenAI Enterprise Policy Update Changes Admin Verification Rules',
            'description' => 'The OpenAI enterprise policy update adds admin verification controls, audit prompts and onboarding checks that change how newsroom automation is deployed.',
            'content' => $body,
            'focus_keyword' => 'OpenAI enterprise policy update',
            'category_ids' => [$this->categoryId()],
            'format_type' => 'default',
            'is_featured' => false,
        ], $overrides);
    }

    protected function categoryId(): int
    {
        return $this->categoryId ??= $this->createCategory(['name' => 'Automation Test Beat'])->id;
    }

    private ?int $categoryId = null;

    /**
     * @return \Illuminate\Testing\TestResponse
     */
    protected function publish(string $token, ?string $key, array $payload)
    {
        $headers = ['X-API-Token' => $token];

        if ($key !== null) {
            $headers['Idempotency-Key'] = $key;
        }

        return $this->postJson(self::BASE . '/posts/create', $payload, $headers);
    }
}
