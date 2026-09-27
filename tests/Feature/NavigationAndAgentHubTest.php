<?php

namespace Tests\Feature;

use App\Services\NavigationService;
use Botble\Blog\Models\Category;
use Botble\Blog\Models\Post;
use Botble\Slug\Models\Slug;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
use Tests\Concerns\CreatesPosts;
use Tests\TestCase;
use Tests\Traits\RefreshDatabaseWithPlugins;

/**
 * Guards three moves that are easy to undo by accident:
 *
 *  1. The header navigation is built from the database, so a beat that no longer
 *     resolves still has to render a working link rather than disappearing.
 *  2. Subcategories belong in the navigation. They used to be duplicated as page
 *     furniture (a pill row under the category title and a list in the category
 *     sidebar), which is what readers were seeing.
 *  3. The AI agent hub is a collapsed `<details>`. Its whole point is that a
 *     reader scrolls past one compact bar while agents and crawlers still get the
 *     full contract in the markup.
 */
class NavigationAndAgentHubTest extends TestCase
{
    use CreatesPosts;
    use RefreshDatabaseWithPlugins;

    protected function setUp(): void
    {
        parent::setUp();

        // Both the navigation and the homepage are cached; a stale entry from an
        // earlier test would make these assertions meaningless.
        Cache::flush();
        app(NavigationService::class)->flush();
    }

    /**
     * `createCategory()` slugifies a fixture name; navigation is keyed by the real
     * production slugs, so point the fixture at one.
     */
    private function assignNavSlug(Category $category, string $key): void
    {
        Slug::query()
            ->where('reference_type', Category::class)
            ->where('reference_id', $category->id)
            ->update(['key' => $key]);
    }

    /** @return array<string, array{label: string, url: string, children: array}> */
    private function navigationByLabel(): array
    {
        return collect(app(NavigationService::class)->getPrimaryNavigation())
            ->keyBy('label')
            ->all();
    }

    public function test_navigation_lists_every_configured_beat(): void
    {
        $navigation = app(NavigationService::class)->getPrimaryNavigation();

        $this->assertCount(count(NavigationService::PRIMARY_ITEMS), $navigation);
        $this->assertSame(
            array_column(NavigationService::PRIMARY_ITEMS, 'label'),
            array_column($navigation, 'label')
        );
        $this->assertSame(url('/'), $navigation[0]['url']);
        $this->assertSame([], $navigation[0]['children']);
    }

    public function test_navigation_hangs_published_children_off_their_parent(): void
    {
        $culture = $this->createCategory(['name' => 'Culture']);
        $this->assignNavSlug($culture, 'culture');

        $child = $this->createCategory(['name' => 'Celebrity', 'parent_id' => $culture->id]);
        $this->assignNavSlug($child, 'celebrity');

        $navigation = $this->navigationByLabel();

        $this->assertSame(url('/topic/culture'), $navigation['Culture']['url']);
        $this->assertSame(['Celebrity'], array_column($navigation['Culture']['children'], 'label'));
        $this->assertSame(url('/topic/celebrity'), $navigation['Culture']['children'][0]['url']);

        // Beats without children must stay plain links.
        $this->assertSame([], $navigation['Politics']['children']);
    }

    public function test_navigation_excludes_unpublished_children(): void
    {
        $culture = $this->createCategory(['name' => 'Culture']);
        $this->assignNavSlug($culture, 'culture');

        $this->createCategory(['name' => 'Hidden Child', 'parent_id' => $culture->id, 'status' => 'draft']);

        $this->assertSame([], $this->navigationByLabel()['Culture']['children']);
    }

    public function test_navigation_keeps_working_when_a_category_is_missing(): void
    {
        // No categories at all: every beat still resolves to its public topic URL
        // instead of vanishing from the header.
        $navigation = $this->navigationByLabel();

        $this->assertCount(count(NavigationService::PRIMARY_ITEMS), $navigation);
        $this->assertSame(url('/topic/politics'), $navigation['Politics']['url']);
        $this->assertSame(url('/'), $navigation['Home']['url']);
    }

    public function test_header_renders_a_submenu_for_a_beat_with_children(): void
    {
        $culture = $this->createCategory(['name' => 'Culture']);
        $this->assignNavSlug($culture, 'culture');

        $child = $this->createCategory(['name' => 'Celebrity', 'parent_id' => $culture->id]);
        $this->assignNavSlug($child, 'celebrity');

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('<ul class="nav-submenu">', false);
        $response->assertSee('/topic/celebrity', false);
        // Trailing quote so this matches the rendered class attribute, not the
        // stylesheet rules that legitimately mention the class name.
        $response->assertSee('nav-menu-item-has-children"', false);
    }

    public function test_header_renders_no_submenu_when_no_beat_has_children(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertDontSee('<ul class="nav-submenu">', false);
        $response->assertDontSee('nav-menu-item-has-children"', false);
    }

    public function test_agent_hub_is_collapsed_but_keeps_its_content_in_the_markup(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('<details class="ai-agent-hub" id="ai-agents">', false);
        // Collapsed by default, so readers land on news rather than agent docs.
        $response->assertDontSee('<details class="ai-agent-hub" id="ai-agents" open', false);
        $response->assertSee('ai-agent-hub-summary', false);
        // The contract and discovery files must survive the collapse for crawlers.
        $response->assertSee('/AI_INSTRUCTIONS.md', false);
        $response->assertSee('ai-agent-hub-endpoints', false);
    }

    /**
     * The category template still has to render cleanly while no longer printing
     * a pill row under the title or a duplicate "Subcategories" list in its
     * sidebar. Rendered directly rather than over HTTP: /topic/{slug} is served by
     * slug-prefix routes that only exist once the production taxonomy has booted.
     */
    public function test_category_template_no_longer_repeats_subcategories_as_page_content(): void
    {
        $culture = $this->createCategory(['name' => 'Culture']);
        $this->assignNavSlug($culture, 'culture');

        $child = $this->createCategory(['name' => 'Celebrity', 'parent_id' => $culture->id]);
        $this->assignNavSlug($child, 'celebrity');

        $html = Blade::render(
            (string) file_get_contents(base_path('platform/themes/newspaper/views/category.blade.php')),
            ['category' => $culture->fresh(), 'posts' => Post::query()->paginate(12)]
        );

        $this->assertStringContainsString('Culture', $html);
        $this->assertStringNotContainsString('child-categories', $html);
        $this->assertStringNotContainsString('child-category-link', $html);
        $this->assertStringNotContainsString('subcategory-list', $html);
        $this->assertStringNotContainsString('subcategory-link', $html);
    }
}
