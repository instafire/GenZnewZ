<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Tests\Concerns\CreatesPosts;
use Tests\TestCase;
use Tests\Traits\RefreshDatabaseWithPlugins;

class FeedTest extends TestCase
{
    use CreatesPosts;
    use RefreshDatabaseWithPlugins;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_site_wide_rss_feed_is_served_as_xml(): void
    {
        $this->createPublishedPost('Feed Test Headline');

        $response = $this->get('/feed');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/rss+xml; charset=UTF-8');
        $response->assertSee('Feed Test Headline');

        $document = $this->parseXml($response->getContent());
        $this->assertSame('rss', $document->documentElement->nodeName);
        $this->assertSame('2.0', $document->documentElement->getAttribute('version'));
    }

    /**
     * A feed that is not well-formed XML is worse than no feed: every reader
     * and crawler that touches it fails. Parse it rather than string-match it.
     */
    public function test_rss_feed_is_well_formed_xml_with_self_link(): void
    {
        $this->createPublishedPost('Well Formed Headline');

        $document = $this->parseXml($this->get('/feed')->getContent());

        $xpath = new \DOMXPath($document);
        $xpath->registerNamespace('atom', 'http://www.w3.org/2005/Atom');

        $selfLinks = $xpath->query('/rss/channel/atom:link[@rel="self"]');
        $this->assertSame(1, $selfLinks->length, 'Feed must declare exactly one atom:link rel="self"');
        $this->assertSame(url('feed'), $selfLinks->item(0)->getAttribute('href'));
        $this->assertSame(
            'application/rss+xml',
            $selfLinks->item(0)->getAttribute('type')
        );

        // The channel link is the human-readable page, not the feed itself.
        $this->assertSame(url('/'), $xpath->evaluate('string(/rss/channel/link)'));
        $this->assertSame(1, $xpath->query('/rss/channel/item')->length);
    }

    /**
     * Parse a feed response, failing the test with the libxml reason when the
     * document is malformed.
     */
    protected function parseXml(string $body): \DOMDocument
    {
        $document = new \DOMDocument();

        $previous = libxml_use_internal_errors(true);
        $loaded = $document->loadXML($body);
        $errors = libxml_get_errors();
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $this->assertTrue($loaded, 'Feed is not well-formed XML: ' . json_encode(array_map(
            fn ($error) => trim($error->message),
            $errors
        )));

        return $document;
    }

    public function test_rss_items_carry_required_fields_and_absolute_urls(): void
    {
        $post = $this->createPublishedPost('Item Fields Headline', [
            'content' => '<p>Body text</p><img src="/storage/example.jpg" alt="Example">',
        ]);

        $body = $this->get('/feed')->getContent();
        $xml = simplexml_load_string($body);
        $item = $xml->channel->item[0];

        $this->assertSame('Item Fields Headline', (string) $item->title);
        $this->assertSame($post->url, (string) $item->link);
        $this->assertSame($post->url, (string) $item->guid);
        $this->assertNotEmpty((string) $item->pubDate);
        $this->assertNotEmpty((string) $item->description);

        // Readers resolve relative media against the feed document, which is
        // not the article, so root-relative URLs must be absolutised.
        $this->assertStringContainsString(url('/'), (string) $item->link);
        $this->assertStringNotContainsString('src="/storage', $body);
    }

    public function test_json_feed_matches_spec_shape(): void
    {
        $post = $this->createPublishedPost('JSON Feed Headline');

        $response = $this->get('/feed.json');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/feed+json; charset=UTF-8');
        $response->assertJsonPath('version', 'https://jsonfeed.org/version/1.1');
        $response->assertJsonPath('feed_url', url('feed.json'));
        $response->assertJsonPath('items.0.url', $post->url);
        $response->assertJsonPath('items.0.title', 'JSON Feed Headline');

        $payload = $response->json();
        $this->assertNotEmpty($payload['title']);
        $this->assertNotEmpty($payload['items'][0]['date_published']);
        $this->assertArrayHasKey('authors', $payload['items'][0]);
    }

    public function test_category_feeds_are_served_per_category(): void
    {
        $category = $this->createCategory(['name' => 'Feedable Topic']);

        $included = $this->createPublishedPost('Included In Category Feed');
        $included->categories()->attach($category->id);

        $excluded = $this->createPublishedPost('Not In Category Feed');

        $rss = $this->get('/feed/' . $category->slug);
        $rss->assertOk();
        $rss->assertSee('Included In Category Feed');
        $rss->assertDontSee($excluded->name);

        $json = $this->get('/feed/' . $category->slug . '.json');
        $json->assertOk();
        $json->assertJsonPath('feed_url', url('feed/' . $category->slug . '.json'));
        $this->assertSame(['Included In Category Feed'], array_column($json->json('items'), 'title'));
    }

    /**
     * `feed/ai-news.json` must resolve as the category feed rather than as a
     * category literally named "ai-news.json".
     */
    public function test_json_category_feed_url_resolves_the_bare_slug(): void
    {
        $category = $this->createCategory(['name' => 'Dotted Feed Topic']);
        $post = $this->createPublishedPost('Dotted Slug Headline');
        $post->categories()->attach($category->id);

        $response = $this->get('/feed/' . $category->slug . '.json');

        $response->assertOk();
        $this->assertSame(['Dotted Slug Headline'], array_column($response->json('items'), 'title'));
    }

    public function test_unknown_category_feed_returns_404(): void
    {
        $this->get('/feed/definitely-not-a-real-category')->assertNotFound();
        $this->get('/feed/definitely-not-a-real-category.json')->assertNotFound();
    }

    public function test_feeds_only_include_published_posts(): void
    {
        $this->createPost(['name' => 'Draft Feed Headline', 'status' => 'draft']);

        $this->get('/feed')->assertDontSee('Draft Feed Headline');
        $this->get('/feed.json')->assertJsonMissing(['title' => 'Draft Feed Headline']);
    }

    public function test_pages_advertise_their_feeds_for_autodiscovery(): void
    {
        $category = $this->createCategory(['name' => 'Advertised Topic']);

        $home = $this->get('/');
        $home->assertOk();
        $home->assertSee('rel="alternate"', false);
        $home->assertSee(url('feed.json'), false);
    }

    protected function createPublishedPost(string $title, array $attributes = []): \Botble\Blog\Models\Post
    {
        return $this->createPost(array_merge([
            'name' => $title,
            'views' => 10,
        ], $attributes));
    }
}
