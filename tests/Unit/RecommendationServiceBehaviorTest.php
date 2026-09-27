<?php

namespace Tests\Unit;

use App\Models\UserBehaviorLog;
use App\Services\RecommendationService;
use Botble\Blog\Models\Category;
use Botble\Blog\Models\Post;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;
use Tests\Traits\RefreshDatabaseWithPlugins;

class RecommendationServiceBehaviorTest extends TestCase
{
    use RefreshDatabaseWithPlugins;

    protected RecommendationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(RecommendationService::class);
        Cache::flush();
    }

    public function test_personalized_recommendations_fallback_without_behavior(): void
    {
        $post = $this->createPost('Fallback Post');

        $recommendations = $this->service->getPersonalizedRecommendations($post, 4);

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $recommendations);
        $this->assertLessThanOrEqual(4, $recommendations->count());
    }

    public function test_personalized_recommendations_use_preferred_categories(): void
    {
        $category = Category::create([
            'name' => 'Tech',
            'slug' => 'tech-' . uniqid(),
            'status' => 'published',
        ]);

        $preferredPost = $this->createPost('Preferred Post');
        $preferredPost->categories()->attach($category->id);

        $recommendedPost = $this->createPost('Recommended Post');
        $recommendedPost->categories()->attach($category->id);

        UserBehaviorLog::create([
            'visitor_id' => 'behavior-visitor-1',
            'post_id' => $preferredPost->id,
            'action' => 'view',
            'source' => 'article_page',
            'created_at' => now(),
        ]);

        $recommendations = $this->service->getPersonalizedRecommendations(null, 4, 'behavior-visitor-1');

        $this->assertTrue(
            $recommendations->contains('id', $recommendedPost->id),
            'Personalized recommendations should include posts from preferred categories.'
        );
    }

    public function test_personalized_recommendations_exclude_recently_viewed(): void
    {
        $post = $this->createPost('Viewed Post');

        UserBehaviorLog::create([
            'visitor_id' => 'behavior-visitor-2',
            'post_id' => $post->id,
            'action' => 'view',
            'source' => 'article_page',
            'created_at' => now(),
        ]);

        $recommendations = $this->service->getPersonalizedRecommendations(null, 4, 'behavior-visitor-2');

        $this->assertFalse(
            $recommendations->contains('id', $post->id),
            'Personalized recommendations should exclude recently viewed posts.'
        );
    }

    protected function createPost(string $name): Post
    {
        $post = new Post();
        $post->name = $name . ' ' . uniqid();
        $post->description = 'Test description';
        $post->content = 'Test content';
        $post->status = 'published';
        $post->views = 0;
        $post->save();

        return $post;
    }
}
