<?php

namespace Tests\Unit;

use App\Services\RecommendationService;
use Botble\Blog\Models\Post;
use Botble\Blog\Models\Category;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;
use Tests\Traits\RefreshDatabaseWithPlugins;

class RecommendationServiceTest extends TestCase
{
    use RefreshDatabaseWithPlugins;

    protected RecommendationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(RecommendationService::class);
    }

    private function createPost(array $overrides = []): Post
    {
        $post = new Post();
        $post->name = $overrides['name'] ?? 'Test Post ' . uniqid();
        $post->description = $overrides['description'] ?? 'Test description for ' . $post->name;
        $post->content = $overrides['content'] ?? '<p>Test content.</p>';
        $post->image = $overrides['image'] ?? null;
        $post->status = $overrides['status'] ?? 'published';
        $post->views = $overrides['views'] ?? 0;
        $post->save();

        return $post;
    }

    public function test_get_related_posts_returns_empty_collection_for_post_without_categories_or_tags(): void
    {
        $post = $this->createPost();

        $related = $this->service->getRelatedPosts($post, 4);

        $this->assertTrue($related->isEmpty());
    }

    public function test_get_trending_posts_returns_published_posts_ordered_by_views(): void
    {
        Cache::flush();

        $this->createPost(['views' => 100]);
        $this->createPost(['views' => 50]);
        $this->createPost(['status' => 'draft', 'views' => 999]);

        $trending = $this->service->getTrendingPosts(5);

        $this->assertCount(2, $trending);
        $this->assertEquals(100, $trending->first()->views);
    }

    public function test_get_trending_posts_respects_exclude_ids(): void
    {
        Cache::flush();

        $excluded = $this->createPost(['views' => 1000]);
        $this->createPost(['views' => 100]);

        $trending = $this->service->getTrendingPosts(5, [$excluded->id]);

        $this->assertCount(1, $trending);
        $this->assertNotEquals($excluded->id, $trending->first()->id);
    }

    public function test_get_recommended_for_homepage_returns_mixed_results(): void
    {
        Cache::flush();

        for ($i = 0; $i < 10; $i++) {
            $this->createPost(['views' => $i * 10]);
        }

        $recommended = $this->service->getRecommendedForHomepage(6);

        $this->assertCount(6, $recommended);
    }

    public function test_get_related_posts_finds_posts_in_same_category(): void
    {
        Cache::flush();

        $category = Category::create([
            'name' => 'Test Category',
            'slug' => 'test-category-' . uniqid(),
            'status' => 'published',
        ]);

        $post = $this->createPost();
        $post->categories()->attach($category->id);

        $relatedPost = $this->createPost(['name' => 'Related Post']);
        $relatedPost->categories()->attach($category->id);

        $related = $this->service->getRelatedPosts($post, 4);

        $this->assertCount(1, $related);
        $this->assertEquals($relatedPost->id, $related->first()->id);
    }

    public function test_clear_cache_for_post_invalidates_related_cache(): void
    {
        Cache::flush();

        $category = Category::create([
            'name' => 'Test Category',
            'slug' => 'test-category-' . uniqid(),
            'status' => 'published',
        ]);

        $post = $this->createPost();
        $post->categories()->attach($category->id);

        $relatedPost = $this->createPost(['name' => 'Related Post']);
        $relatedPost->categories()->attach($category->id);

        $before = $this->service->getRelatedPosts($post, 4);
        $this->assertCount(1, $before);

        $this->service->clearCacheForPost($post, 4);

        $after = $this->service->getRelatedPosts($post, 4);
        $this->assertCount(1, $after);
        $this->assertEquals($relatedPost->id, $after->first()->id);
    }
}
