<?php

namespace Tests\Feature;

use App\Models\AIReporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Guards the automation API credential model.
 *
 * The site previously published a literal shared token in public docs
 * (public/MAGIC_LINK.txt, public/SEND_TO_AI.txt) and kept a second, legacy
 * publishing endpoint in the web root that authenticated with that shared
 * secret and bypassed the quality gate entirely. These tests keep both
 * from coming back.
 */
class ApiTokenHygieneTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Decides whether the value documented after `X-API-Token:` is a real
     * credential or an obvious placeholder.
     *
     * A real token is a long opaque string (`ai_` + 56 hex, or a 64-char hex
     * secret). Placeholders are short, start with a `your`/`YOUR` marker, are made
     * of filler x's, or are wrapped in angle brackets / braces / a shell variable.
     */
    private function looksLikeARealToken(string $line): bool
    {
        $firstWord = preg_split('/[\s"\'`]+/', trim($line))[0] ?? '';
        $candidate = trim($firstWord, '"\'`<>{}$');

        if (strlen($candidate) < 20 || ! preg_match('/^[A-Za-z0-9_-]+$/', $candidate)) {
            return false;
        }

        if (preg_match('/^(your|YOUR)|x{6,}|^token$/', $candidate)) {
            return false;
        }

        return true;
    }

    public function test_no_public_document_publishes_a_literal_api_token(): void
    {
        $offenders = [];

        foreach (File::allFiles(public_path()) as $file) {
            if (! in_array($file->getExtension(), ['txt', 'md', 'json', 'html'], true)) {
                continue;
            }

            // Skip vendored/asset trees; only review human-facing documentation.
            if (str_contains($file->getPathname(), '/themes/') || str_contains($file->getPathname(), '/vendor/')) {
                continue;
            }

            $contents = File::get($file->getPathname());

            // Capture to end of line, not to the first space: a placeholder like
            // "<your token>" is otherwise captured as "<your" and looks like a leak.
            if (! preg_match_all('/X-API-Token:[ \t]*(.+)$/im', $contents, $matches)) {
                continue;
            }

            foreach ($matches[1] as $value) {
                if ($this->looksLikeARealToken($value)) {
                    $offenders[] = $file->getRelativePathname() . ' -> ' . $value;
                }
            }
        }

        $this->assertSame([], $offenders, 'Public files must not publish a literal API token.');
    }

    public function test_the_documented_flow_is_registration_based(): void
    {
        $docs = File::get(public_path('MAGIC_LINK.txt')) . File::get(public_path('SEND_TO_AI.txt'));

        $this->assertStringContainsString('/api/v1/automation/register', $docs);
        $this->assertStringNotContainsString('genznewz-automation-token', $docs);
    }

    public function test_legacy_web_root_api_is_gone(): void
    {
        $this->assertFileDoesNotExist(public_path('ai-automation/post.php'));
        $this->assertFileDoesNotExist(public_path('ai-automation/config.php'));

        $this->postJson('/ai-automation/post.php')->assertNotFound();
    }

    public function test_automation_api_rejects_an_unknown_token(): void
    {
        $this->postJson('/api/v1/automation/posts/create', [
            'title' => 'Some article title',
            'content' => '<p>Body</p>',
        ], ['X-API-Token' => 'not-a-real-token'])->assertStatus(401);
    }

    public function test_registration_issues_a_unique_random_token(): void
    {
        $payload = [
            'description' => 'We cover artificial intelligence and technology news.',
            'workflow_summary' => 'We research primary sources and write finished articles.',
            'publishing_mode' => 'direct_article_submission',
            'agrees_no_code_deliverables' => true,
            'agrees_editorial_standard' => true,
        ];

        $first = $this->postJson('/api/v1/automation/register', $payload)->assertCreated();
        $second = $this->postJson('/api/v1/automation/register', $payload)->assertCreated();

        $tokenOne = $first->json('data.api_token');
        $tokenTwo = $second->json('data.api_token');

        $this->assertNotEmpty($tokenOne);
        $this->assertNotSame($tokenOne, $tokenTwo, 'Each reporter must receive its own token.');
        $this->assertStringStartsWith('ai_', $tokenOne);
        $this->assertGreaterThanOrEqual(32, strlen($tokenOne));

        // Only a one-way digest is stored; raw credentials are never serialized.
        $digest = AIReporter::hashApiToken($tokenOne);
        $this->assertDatabaseHas('ai_reporters', ['api_token' => $digest]);
        $this->assertNotSame($tokenOne, AIReporter::where('username', $first->json('data.username'))->value('api_token'));
        $this->assertArrayNotHasKey(
            'api_token',
            AIReporter::where('username', $first->json('data.username'))->firstOrFail()->toArray()
        );
        $this->assertSame(2, AIReporter::query()->whereNotNull('api_token')->count());

        $this->getJson('/api/v1/automation/me', ['X-API-Token' => $tokenOne])->assertOk();
        $this->getJson('/api/v1/automation/me', ['X-API-Token' => $digest])->assertUnauthorized();
    }

    public function test_legacy_plaintext_tokens_migrate_on_first_successful_authentication(): void
    {
        $token = 'ai_' . \Illuminate\Support\Str::random(60);
        $reporter = AIReporter::create([
            'name' => 'Legacy Token Agent',
            'email' => 'legacy-token-agent@example.test',
            'username' => 'legacy-token-agent',
            'password' => bcrypt('a-long-enough-password'),
            'api_token' => $token,
            'status' => 'active',
        ]);

        $found = AIReporter::findByApiToken($token);

        $this->assertSame($reporter->id, $found?->id);
        $this->assertDatabaseHas('ai_reporters', [
            'id' => $reporter->id,
            'api_token' => AIReporter::hashApiToken($token),
        ]);
        $this->assertNull(AIReporter::findByApiToken(AIReporter::hashApiToken($token)));
    }

    /**
     * The internal refresh token must be read through config(), not env().
     *
     * Asserted against the config layer rather than by calling the endpoint: that
     * controller injects LiveWorldEventsServiceOptimized, which cannot be resolved
     * in this harness, so the request never reaches the authorization check. The
     * fail-closed behaviour itself was verified live - the endpoint returns 403 to
     * both a missing and a wrong X-Internal-Token under production config.
     */
    public function test_internal_refresh_token_is_config_backed_not_env(): void
    {
        $this->assertArrayHasKey('internal_token', config('automation'));

        $source = File::get(base_path('app/Http/Controllers/Api/LiveEventsController.php'));

        $this->assertStringContainsString("config('automation.internal_token'", $source);
        $this->assertStringNotContainsString("env('AI_AUTOMATION_TOKEN'", $source);
    }
}
