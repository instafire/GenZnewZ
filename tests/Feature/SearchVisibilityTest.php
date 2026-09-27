<?php

namespace Tests\Feature;

use App\Services\SearchEnginePingService;
use Illuminate\Support\Facades\Cache;
use Tests\Concerns\CreatesPosts;
use Tests\TestCase;
use Tests\Traits\RefreshDatabaseWithPlugins;

/**
 * Guards the crawler-facing surface: the directory directives we advertise for
 * every page, the Google News sitemap, and the discovery files that point
 * crawlers and agents at it.
 *
 * These are regression tests for changes that are invisible in the UI but
 * decide whether a news article is eligible for large previews and for Google
 * News at all.
 */
class SearchVisibilityTest extends TestCase
{
    use CreatesPosts;
    use RefreshDatabaseWithPlugins;

    protected string $sitemapDir;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        $this->sitemapDir = sys_get_temp_dir() . '/gzn-sitemap-test-' . uniqid();
        @mkdir($this->sitemapDir, 0777, true);
    }

    protected function tearDown(): void
    {
        if (isset($this->sitemapDir) && is_dir($this->sitemapDir)) {
            foreach (glob($this->sitemapDir . '/*') ?: [] as $file) {
                @unlink($file);
            }

            @rmdir($this->sitemapDir);
        }

        parent::tearDown();
    }

    /**
     * The directives are emitted from the shared header partial, so the homepage
     * covers every indexable page type. (Fetching a post body in tests is not yet
     * possible: incoming slug route resolution needs the same plugin bootstrap
     * that the harness already stubs for outgoing $post->url.)
     */
    public function test_indexable_pages_allow_large_image_previews(): void
    {
        $this->createPost(['name' => 'Large Preview Headline']);

        $response = $this->get('/');

        $response->assertOk();

        // Without these Google falls back to a small thumbnail and a truncated
        // snippet, and the page is ineligible for Discover-style surfaces.
        $response->assertSee('max-image-preview:large', false);
        $response->assertSee('max-snippet:-1', false);
        $response->assertSee('max-video-preview:-1', false);
        $response->assertSee('index, follow', false);
    }

    /**
     * The preview directives are conditional, not a blanket meta tag. A page we
     * tell Google not to index must not also invite it to build a rich preview.
     */
    public function test_noindex_pages_do_not_advertise_large_previews(): void
    {
        $response = $this->get(route('ai.reporter.register'));

        $response->assertOk();

        $body = $response->getContent();

        $this->assertMatchesRegularExpression(
            '/<meta[^>]+name="robots"[^>]+content="noindex, follow"/',
            $body,
            'The reporter registration page must stay noindex.'
        );

        $this->assertStringNotContainsString(
            'max-image-preview:large',
            $body,
            'A noindex page must not advertise preview directives.'
        );
    }

    public function test_news_sitemap_uses_google_news_namespace_and_required_fields(): void
    {
        $post = $this->createPost(['name' => 'Fresh News Headline']);

        $this->generateSitemaps('news');

        $document = $this->parseXml($this->readGenerated('news-sitemap.xml'));

        $this->assertSame(
            'http://www.google.com/schemas/sitemap-news/0.9',
            $document->documentElement->getAttribute('xmlns:news')
        );

        $xpath = new \DOMXPath($document);
        $xpath->registerNamespace('news', 'http://www.google.com/schemas/sitemap-news/0.9');
        $xpath->registerNamespace('sm', 'http://www.sitemaps.org/schemas/sitemap/0.9');

        $this->assertSame($post->url, $xpath->evaluate('string(//sm:url/sm:loc)'));
        $this->assertNotEmpty($xpath->evaluate('string(//news:publication/news:name)'));
        $this->assertSame('en', $xpath->evaluate('string(//news:publication/news:language)'));
        $this->assertSame('Fresh News Headline', $xpath->evaluate('string(//news:title)'));
        $this->assertNotEmpty($xpath->evaluate('string(//news:publication_date)'));
    }

    /**
     * Google News rejects the whole file if it contains anything older than two
     * days, so the window is not a nicety - it is the contract.
     */
    public function test_news_sitemap_excludes_articles_older_than_48_hours(): void
    {
        $recent = $this->createPost(['name' => 'Recent Headline']);

        $this->createPost([
            'name' => 'Stale Headline',
            'created_at' => now()->subDays(9),
        ]);

        $this->createPost([
            'name' => 'Draft Headline',
            'status' => 'draft',
        ]);

        $this->generateSitemaps('news');

        $body = $this->readGenerated('news-sitemap.xml');

        $this->assertStringContainsString($recent->url, $body);
        $this->assertStringNotContainsString('Stale Headline', $body);
        $this->assertStringNotContainsString('Draft Headline', $body);
    }

    /**
     * A quiet newsroom must produce an empty file, never a fallback to older
     * articles - Google rejects a news sitemap containing stale URLs outright.
     */
    public function test_news_sitemap_is_empty_rather_than_stale_when_nothing_is_recent(): void
    {
        $this->createPost([
            'name' => 'Long Ago Headline',
            'created_at' => now()->subDays(40),
        ]);

        $this->generateSitemaps('news');

        $body = $this->readGenerated('news-sitemap.xml');

        $document = $this->parseXml($body);

        $this->assertSame(0, $document->getElementsByTagName('url')->length);
        $this->assertStringNotContainsString('Long Ago Headline', $body);
    }

    public function test_news_sitemap_carries_tags_as_keywords(): void
    {
        $post = $this->createPost(['name' => 'Tagged Headline']);

        // Inserted directly: the tags table requires an author_type the model
        // cannot infer, and the keyword output is what this test is about.
        $tagId = \DB::table('tags')->insertGetId([
            'name' => 'Glacier Watch',
            'status' => 'published',
            'author_type' => 'Botble\\ACL\\Models\\User',
            'author_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $post->tags()->attach($tagId);

        $this->generateSitemaps('news');

        $this->assertStringContainsString(
            'Glacier Watch',
            $this->readGenerated('news-sitemap.xml')
        );
    }

    public function test_posts_sitemap_carries_image_metadata(): void
    {
        $withImage = $this->createPost([
            'name' => 'Illustrated Headline',
            'image' => 'pexels/illustrated.webp',
        ]);

        $this->generateSitemaps('posts');

        $body = $this->readGenerated('sitemap-posts.xml');

        $document = $this->parseXml($body);

        $xpath = new \DOMXPath($document);
        $xpath->registerNamespace('image', 'http://www.google.com/schemas/sitemap-image/1.1');
        $xpath->registerNamespace('sm', 'http://www.sitemaps.org/schemas/sitemap/0.9');

        // Google's image sitemap namespace is plain http, not https.
        $this->assertSame(
            'http://www.google.com/schemas/sitemap-image/1.1',
            $document->documentElement->getAttribute('xmlns:image')
        );

        // Scope to this post's own <url> entry rather than counting globally, so
        // the assertion survives any pre-existing fixture content.
        $imageLoc = $xpath->evaluate(
            'string(//sm:url[sm:loc="' . $withImage->url . '"]/image:image/image:loc)'
        );
        $imageTitle = $xpath->evaluate(
            'string(//sm:url[sm:loc="' . $withImage->url . '"]/image:image/image:title)'
        );

        $this->assertStringContainsString('pexels/illustrated.webp', $imageLoc);
        $this->assertSame('Illustrated Headline', $imageTitle);

        // Exactly one block per article: a duplicated <image:image> is invalid
        // and an empty one is worse than none.
        $this->assertSame(1, $xpath->query(
            '//sm:url[sm:loc="' . $withImage->url . '"]/image:image'
        )->length);
    }

    public function test_organization_schema_declares_editorial_policies(): void
    {
        $response = $this->get('/');

        $response->assertOk();

        // E-E-A-T: search engines look for named, machine-readable policies on
        // the publishing entity, not just a sentence in the footer.
        $response->assertSee('NewsMediaOrganization', false);
        $response->assertSee('publishingPrinciples', false);
        $response->assertSee(url('/editorial-policy'), false);
        $response->assertSee('masthead', false);
        $response->assertSee(url('/our-team'), false);
    }

    public function test_robots_txt_advertises_the_news_sitemap(): void
    {
        $robots = file_get_contents(base_path('public/robots.txt'));

        $this->assertStringContainsString(
            'Sitemap: https://genznewz.com/news-sitemap.xml',
            $robots
        );

        // The existing sitemaps must not have been dropped in the process.
        foreach (['sitemap.xml', 'sitemap-posts.xml', 'sitemap-pages.xml', 'sitemap-ai.xml'] as $sitemap) {
            $this->assertStringContainsString(
                "Sitemap: https://genznewz.com/{$sitemap}",
                $robots
            );
        }
    }

    public function test_llms_txt_documents_the_news_sitemap_and_freshness(): void
    {
        $llms = file_get_contents(base_path('public/llms.txt'));

        $this->assertStringContainsString('news-sitemap.xml', $llms);
        $this->assertStringContainsString('last 48 hours', $llms);
        $this->assertStringContainsString('## Freshness', $llms);
    }

    /**
     * Articles published through the automation API are the site's main output,
     * but that path does not raise Botble's content events - so the search ping
     * had to be wired in explicitly.
     */
    public function test_publishing_notifies_search_engines(): void
    {
        $post = $this->createPost(['name' => 'Agent Published Headline']);

        $ping = new class extends SearchEnginePingService {
            public array $notified = [];

            public function notifyOnPublish(string $url): void
            {
                $this->notified[] = $url;
            }
        };

        $this->app->instance(SearchEnginePingService::class, $ping);

        $this->automationController()->notify($post);

        $this->assertSame([$post->url], $ping->notified);
    }

    public function test_a_failing_ping_does_not_break_a_publish(): void
    {
        $post = $this->createPost(['name' => 'Resilient Headline']);

        $ping = new class extends SearchEnginePingService {
            public int $attempts = 0;

            public function notifyOnPublish(string $url): void
            {
                $this->attempts++;

                throw new \RuntimeException('IndexNow is unreachable');
            }
        };

        $this->app->instance(SearchEnginePingService::class, $ping);

        // Must not throw: a ping failure can never fail an article publish.
        $this->automationController()->notify($post);

        $this->assertSame(1, $ping->attempts);
    }

    protected function automationController(): object
    {
        return new class extends \Theme\Newspaper\Http\Controllers\API\AutomationController {
            public function notify(\Botble\Blog\Models\Post $post): void
            {
                $this->notifySearchEngines($post);
            }
        };
    }

    protected function generateSitemaps(string $type): void
    {
        $this->artisan('sitemap:generate', [
            '--type' => $type,
            '--path' => $this->sitemapDir . '/sitemap.xml',
            '--output-dir' => $this->sitemapDir,
        ])->assertSuccessful();
    }

    protected function readGenerated(string $file): string
    {
        $path = $this->sitemapDir . '/' . $file;

        $this->assertFileExists($path, "Expected {$file} to have been generated.");

        return (string) file_get_contents($path);
    }

    protected function parseXml(string $body): \DOMDocument
    {
        $document = new \DOMDocument();

        $previous = libxml_use_internal_errors(true);
        $loaded = $document->loadXML($body);
        $errors = libxml_get_errors();
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $this->assertTrue($loaded, 'Not well-formed XML: ' . json_encode(array_map(
            fn ($error) => trim($error->message),
            $errors
        )));

        return $document;
    }
}
