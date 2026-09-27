<?php

namespace Tests\Feature;

use App\Services\HomepageDataService;
use Illuminate\Support\Facades\Cache;
use Tests\Concerns\CreatesPosts;
use Tests\TestCase;
use Tests\Traits\RefreshDatabaseWithPlugins;

class HomepageTest extends TestCase
{
    use CreatesPosts;
    use RefreshDatabaseWithPlugins;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_homepage_renders_successfully(): void
    {
        $this->createHomepageData();

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Latest News From Around The World');
        $response->assertSee('Featured Stories');
        $response->assertSee('Recommended For You');
    }

    public function test_homepage_renders_with_no_posts(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Latest News From Around The World');
    }

    public function test_homepage_displays_seeded_post_titles(): void
    {
        $post = $this->createPost(['name' => 'Breaking Test News Story']);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Breaking Test News Story');
    }

    public function test_homepage_behavior_meta_tag_contains_latest_post_ids(): void
    {
        $this->createHomepageData();

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('behavior-homepage');

        // Regression test: the meta tag must contain the IDs of the latest posts.
        // This catches ordering bugs where the tag is rendered before $latestPosts is defined.
        $service = app(HomepageDataService::class);
        $homepageData = $service->getHomepageData();
        $expectedIds = $homepageData['latestPosts']->pluck('id')->take(10)->values()->all();
        $expectedJson = json_encode($expectedIds);

        $response->assertSee('data-post-ids="' . $expectedJson . '"', false);
    }

    public function test_categories_chunk_endpoint_returns_json(): void
    {
        $category = $this->createCategory(['name' => 'Technology']);

        for ($i = 0; $i < 3; $i++) {
            $post = $this->createPost(['name' => 'Tech Post ' . ($i + 1)]);
            $post->categories()->attach($category->id);
        }

        Cache::flush();

        $response = $this->getJson('/home/categories-chunk?offset=0&limit=5');

        $response->assertOk();
        $response->assertJsonStructure(['html', 'nextOffset', 'hasMore']);
    }

    protected function createHomepageData(): void
    {
        $category = $this->createCategory(['name' => 'World News']);

        for ($i = 0; $i < 8; $i++) {
            $post = $this->createPost([
                'name' => 'Homepage Test Post ' . ($i + 1),
                'is_featured' => $i < 3,
                'views' => ($i + 1) * 10,
                'created_at' => now()->subMinutes(8 - $i),
                // The featured rail only surfaces image-backed posts.
                'image' => 'homepage-test-' . ($i + 1) . '.jpg',
            ]);
            $post->categories()->attach($category->id);
        }
    }
}
