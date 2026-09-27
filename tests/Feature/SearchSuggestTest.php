<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Tests\Concerns\CreatesPosts;
use Tests\TestCase;
use Tests\Traits\RefreshDatabaseWithPlugins;

class SearchSuggestTest extends TestCase
{
    use CreatesPosts;
    use RefreshDatabaseWithPlugins;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_an_empty_query_returns_trending_topics_instead_of_suggestions(): void
    {
        $topic = $this->createTopicWithPosts('Quantum Widgets');

        $response = $this->getJson('/api/search/suggest?q=');

        $response->assertOk();
        $response->assertJsonPath('suggestions', []);
        $response->assertJsonFragment(['url' => $topic->url]);
    }

    public function test_a_short_query_does_not_hit_the_database_for_matches(): void
    {
        $this->createPublishedPost('Only Matchable Headline');

        $response = $this->getJson('/api/search/suggest?q=a');

        $response->assertOk();
        $response->assertJsonPath('suggestions', []);
    }

    public function test_posts_matching_the_query_are_suggested_with_their_url_and_topic(): void
    {
        $category = $this->createCategory(['name' => 'Robotics Desk']);
        $post = $this->createPost(['name' => 'Humanoid Robots Enter Factories']);
        $post->categories()->attach($category->id);

        $response = $this->getJson('/api/search/suggest?q=Humanoid');

        $response->assertOk();
        $response->assertJsonPath('suggestions.0.type', 'post');
        $response->assertJsonPath('suggestions.0.title', 'Humanoid Robots Enter Factories');
        $response->assertJsonPath('suggestions.0.url', $post->url);
        $response->assertJsonPath('suggestions.0.meta', 'Robotics Desk');
    }

    public function test_topic_matches_are_ranked_ahead_of_post_matches(): void
    {
        $category = $this->createCategory(['name' => 'Astrophysics']);
        $post = $this->createPost(['name' => 'Astrophysics Breakthrough Announced']);
        $post->categories()->attach($category->id);

        $response = $this->getJson('/api/search/suggest?q=Astro');

        $response->assertOk();

        $types = array_column($response->json('suggestions'), 'type');
        $this->assertNotEmpty($types);
        $this->assertSame('category', $types[0], 'Topic matches should lead so readers can jump to a whole section.');
    }

    /**
     * A topic with no published stories is a dead end; suggesting it would send
     * the reader to an empty archive.
     */
    public function test_topics_without_published_posts_are_not_suggested(): void
    {
        $this->createCategory(['name' => 'Empty Hinterland']);

        $response = $this->getJson('/api/search/suggest?q=Hinterland');

        $response->assertOk();
        $this->assertSame([], $response->json('suggestions'));
    }

    public function test_draft_posts_are_never_suggested(): void
    {
        $this->createPost(['name' => 'Unreleased Embargo Story', 'status' => 'draft']);

        $response = $this->getJson('/api/search/suggest?q=Embargo');

        $response->assertOk();
        $this->assertSame([], $response->json('suggestions'));
    }

    /**
     * A title that starts with the query is a better match than one that
     * merely contains it, regardless of which is more popular.
     */
    public function test_title_prefix_matches_outrank_mid_title_matches(): void
    {
        $this->createPost(['name' => 'Sustaining Growth In Emerging Markets', 'views' => 9000]);
        $this->createPost(['name' => 'AI Agents Reshape The Newsroom', 'views' => 5]);

        $response = $this->getJson('/api/search/suggest?q=AI');

        $response->assertOk();
        $this->assertSame(
            'AI Agents Reshape The Newsroom',
            $response->json('suggestions.0.title'),
            'A prefix match must outrank a far more viewed mid-title match.'
        );
    }

    /**
     * `%` and `_` are LIKE wildcards. A reader typing them should match those
     * literal characters rather than being handed arbitrary rows.
     */
    public function test_like_wildcards_in_the_query_are_escaped(): void
    {
        $this->createPublishedPost('Ordinary Headline');

        $this->getJson('/api/search/suggest?q=' . urlencode('%_%'))->assertOk()->assertJsonPath('suggestions', []);
        $this->getJson('/api/search/suggest?q=' . urlencode('%%'))->assertOk()->assertJsonPath('suggestions', []);
    }

    public function test_the_query_is_sanitised_and_echoed_back(): void
    {
        $response = $this->getJson('/api/search/suggest?q=' . urlencode('<script>alert(1)</script>  Climate   '));

        $response->assertOk();
        $this->assertStringNotContainsString('<script>', $response->json('query'));
        $this->assertStringNotContainsString('  ', $response->json('query'));
    }

    public function test_suggestions_are_cached_per_query(): void
    {
        $post = $this->createPublishedPost('Cacheable Headline');

        $first = $this->getJson('/api/search/suggest?q=Cacheable');
        $first->assertJsonPath('suggestions.0.url', $post->url);

        $post->delete();

        // Same query, served from cache: no second database round-trip.
        $this->getJson('/api/search/suggest?q=Cacheable')->assertJsonPath('suggestions.0.url', $post->url);
    }

    protected function createPublishedPost(string $title): \Botble\Blog\Models\Post
    {
        return $this->createPost(['name' => $title, 'views' => 25]);
    }

    protected function createTopicWithPosts(string $name): \Botble\Blog\Models\Category
    {
        $category = $this->createCategory(['name' => $name]);

        for ($i = 1; $i <= 3; $i++) {
            $this->createPost(['name' => $name . ' Story ' . $i])->categories()->attach($category->id);
        }

        return $category;
    }
}
