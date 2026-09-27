<?php

namespace Tests\Unit;

use App\Services\HomepageDataService;
use Botble\Blog\Models\Category;
use Illuminate\Support\Facades\Cache;
use Tests\Concerns\CreatesPosts;
use Tests\TestCase;
use Tests\Traits\RefreshDatabaseWithPlugins;

class HomepageDataServiceTest extends TestCase
{
    use CreatesPosts;
    use RefreshDatabaseWithPlugins;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_get_homepage_data_returns_required_keys(): void
    {
        $this->createPost();

        $service = app(HomepageDataService::class);
        $data = $service->getHomepageData();

        $this->assertArrayHasKey('latestPosts', $data);
        $this->assertArrayHasKey('aiTextPosts', $data);
        $this->assertArrayHasKey('featuredStories', $data);
        $this->assertArrayHasKey('editorsPicks', $data);
        $this->assertArrayHasKey('categories', $data);
        $this->assertArrayHasKey('postsByCategory', $data);
        $this->assertArrayHasKey('trending', $data);
    }

    public function test_latest_posts_are_ordered_by_created_at(): void
    {
        $older = $this->createPost(['name' => 'Older Post', 'created_at' => now()->subDay()]);
        $newer = $this->createPost(['name' => 'Newer Post', 'created_at' => now()]);

        $service = app(HomepageDataService::class);
        $data = $service->getHomepageData();

        $this->assertSame($newer->id, $data['latestPosts']->first()->id);
        $this->assertSame($older->id, $data['latestPosts']->skip(1)->first()->id);
    }

    public function test_featured_stories_only_include_featured_posts(): void
    {
        // Create enough featured posts so the service does not need fallback posts.
        $featuredIds = [];
        for ($i = 0; $i < 7; $i++) {
            $post = $this->createPost([
                'name' => 'Featured Post ' . ($i + 1),
                'is_featured' => true,
                'image' => 'featured-' . ($i + 1) . '.jpg',
            ]);
            $featuredIds[] = $post->id;
        }

        $this->createPost(['name' => 'Regular Post', 'is_featured' => false, 'image' => 'regular.jpg']);

        $service = app(HomepageDataService::class);
        $data = $service->getHomepageData();

        $this->assertCount(7, $data['featuredStories']);
        foreach ($data['featuredStories'] as $post) {
            $this->assertTrue((bool) $post->is_featured);
            $this->assertContains($post->id, $featuredIds);
        }
    }

    public function test_category_sections_need_enough_posts_to_fill_a_row(): void
    {
        // The homepage grid is three columns wide, so a one- or two-post section
        // leaves visible holes. Only full rows should render.
        // Distinct timestamps: the service orders by created_at, and posts created
        // in the same second would leave that ordering up to the storage engine.
        // The guard under test is the post count, so the order must not be ambiguous.
        $sparse = $this->createCategory(['name' => 'Sparse Category']);
        for ($i = 0; $i < 2; $i++) {
            $this->createPost(['created_at' => now()->subMinutes(20 - $i)])
                ->categories()
                ->attach($sparse->id);
        }

        $full = $this->createCategory(['name' => 'Full Category']);
        for ($i = 0; $i < 3; $i++) {
            $this->createPost(['created_at' => now()->subMinutes(10 - $i)])
                ->categories()
                ->attach($full->id);
        }

        // Assert the fixture landed before asserting on the service. If the
        // database or the pivot rows were not what this test set up, say so
        // here instead of reporting an empty section list and leaving the
        // cause ambiguous.
        $this->assertTrue(
            \Illuminate\Support\Facades\Schema::hasTable('posts') && \Illuminate\Support\Facades\Schema::hasTable('categories'),
            'Precondition failed: the posts/categories tables are missing, so the service fell back to an empty payload.'
        );
        $this->assertSame(
            3,
            $full->posts()->where('posts.status', 'published')->count(),
            'Precondition failed: the full category does not have three published posts attached.'
        );

        // HomepageDataService memoises its payload, so start from a clean cache
        // immediately before exercising it.
        Cache::flush();

        $service = app(HomepageDataService::class);
        $sections = $service->getRenderableCategories($service->getHomepageData());
        $names = $sections->pluck('category.name')->all();

        $this->assertContains('Full Category', $names, 'Sections rendered: ' . implode(', ', $names));
        $this->assertNotContains('Sparse Category', $names);
    }

    public function test_categories_without_a_slug_are_not_renderable(): void
    {
        // A slugless category resolves to the site root, so its "View all" link
        // would point at the homepage. Those sections must not render.
        $category = $this->createCategory(['name' => 'Slugless Category']);
        for ($i = 0; $i < 3; $i++) {
            $this->createPost()
                ->categories()
                ->attach($category->id);
        }

        \Botble\Slug\Models\Slug::where('reference_type', Category::class)
            ->where('reference_id', $category->id)
            ->delete();

        $service = app(HomepageDataService::class);
        $sections = $service->getRenderableCategories($service->getHomepageData());

        $this->assertNotContains('Slugless Category', $sections->pluck('category.name')->all());
    }

    public function test_empty_database_returns_empty_collections(): void
    {
        $service = app(HomepageDataService::class);
        $data = $service->getHomepageData();

        $this->assertTrue($data['latestPosts']->isEmpty());
        $this->assertTrue($data['featuredStories']->isEmpty());
        $this->assertTrue($data['editorsPicks']->isEmpty());
        $this->assertTrue($data['categories']->isEmpty());
        $this->assertTrue($data['trending']->isEmpty());
    }

}
