<?php

namespace App\Services;

use Botble\Blog\Models\Category;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Primary site navigation, with one level of subcategories underneath each beat.
 *
 * The header used to hardcode its links, which meant subcategories had nowhere to
 * live except as page furniture (a row of pills under the category title and a
 * duplicate list in the category sidebar). This builds the same top-level beats
 * from the database so children can hang off their parent as a real dropdown.
 *
 * Rendering the header sits on every single request, so the result is cached and
 * every failure path degrades to working static links rather than a broken page.
 */
class NavigationService
{
    public const CACHE_KEY = 'theme_primary_navigation_v1';

    public const CACHE_TTL = 600;

    /**
     * Top-level beats shown in the site header, in reading order.
     *
     * @var array<int, array{label: string, slug: string|null}>
     */
    public const PRIMARY_ITEMS = [
        ['label' => 'Home', 'slug' => null],
        ['label' => 'AI News', 'slug' => 'ai-news'],
        ['label' => 'Politics', 'slug' => 'politics'],
        ['label' => 'World', 'slug' => 'world-society'],
        ['label' => 'Business', 'slug' => 'business'],
        ['label' => 'Tech', 'slug' => 'tech-games'],
        ['label' => 'Science', 'slug' => 'science'],
        ['label' => 'Health', 'slug' => 'health'],
        ['label' => 'Sports', 'slug' => 'sports'],
        ['label' => 'Culture', 'slug' => 'culture'],
        ['label' => 'Style', 'slug' => 'fashion'],
        ['label' => 'Opinion', 'slug' => 'opinion'],
    ];

    /**
     * @return array<int, array{label: string, url: string, children: array<int, array{label: string, url: string}>}>
     */
    public function getPrimaryNavigation(): array
    {
        try {
            return cache()->remember(self::CACHE_KEY, self::CACHE_TTL, fn (): array => $this->buildFromDatabase());
        } catch (Throwable) {
            // A dead cache store should not cost us the navigation.
            try {
                return $this->buildFromDatabase();
            } catch (Throwable $exception) {
                logger()->warning('Primary navigation fell back to static links.', [
                    'message' => $exception->getMessage(),
                ]);

                return $this->staticFallback();
            }
        }
    }

    /**
     * Drop the cached navigation so taxonomy edits appear without waiting out the TTL.
     */
    public function flush(): void
    {
        try {
            cache()->forget(self::CACHE_KEY);
        } catch (Throwable) {
            // Nothing to do: the TTL will expire on its own.
        }
    }

    /**
     * @return array<int, array{label: string, url: string, children: array<int, array{label: string, url: string}>}>
     */
    protected function buildFromDatabase(): array
    {
        $slugs = array_values(array_filter(array_column(self::PRIMARY_ITEMS, 'slug')));

        if ($slugs === [] || ! Schema::hasTable('categories') || ! Schema::hasTable('slugs')) {
            return $this->staticFallback();
        }

        $parents = Category::query()
            ->where('status', 'published')
            ->whereHas('slugable', fn ($query) => $query->whereIn('key', $slugs))
            ->with('slugable')
            ->get();

        $parentsBySlug = [];

        foreach ($parents as $parent) {
            $key = $parent->slugable?->key;

            if ($key) {
                $parentsBySlug[$key] = $parent;
            }
        }

        $childrenByParentId = [];

        if ($parentsBySlug !== []) {
            $childrenByParentId = Category::query()
                ->where('status', 'published')
                ->whereIn('parent_id', collect($parentsBySlug)->pluck('id')->all())
                ->with('slugable')
                ->orderBy('name')
                ->get()
                ->groupBy('parent_id')
                ->all();
        }

        $items = [];

        foreach (self::PRIMARY_ITEMS as $definition) {
            $slug = $definition['slug'];

            if ($slug === null) {
                $items[] = ['label' => $definition['label'], 'url' => url('/'), 'children' => []];

                continue;
            }

            $parent = $parentsBySlug[$slug] ?? null;

            $children = [];

            foreach ($childrenByParentId[$parent?->id] ?? [] as $child) {
                $childName = (string) $child->name;

                if ($childName === '') {
                    continue;
                }

                $children[] = [
                    'label' => $childName,
                    'url' => (string) $child->url,
                ];
            }

            $items[] = [
                'label' => $definition['label'],
                'url' => $parent ? (string) $parent->url : url('/topic/' . $slug),
                'children' => $children,
            ];
        }

        return $items;
    }

    /**
     * @return array<int, array{label: string, url: string, children: array<int, array{label: string, url: string}>}>
     */
    protected function staticFallback(): array
    {
        return array_map(static function (array $definition): array {
            return [
                'label' => $definition['label'],
                'url' => $definition['slug'] === null
                    ? url('/')
                    : url('/topic/' . $definition['slug']),
                'children' => [],
            ];
        }, self::PRIMARY_ITEMS);
    }
}
