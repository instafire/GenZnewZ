<?php

namespace App\Services;

use Botble\Author\Models\Author;
use Botble\Blog\Models\Category;
use Botble\Blog\Models\Post;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Collection;

class HomepageDataService
{
    public const CACHE_KEY = 'homepage_data_v5';

    /**
     * Homepage category sections render into a fixed three-column grid, so a
     * section holding one or two posts leaves visible holes beside it and reads
     * as a broken/empty block. Only sections that can fill a row are rendered;
     * stories in sparse categories still surface through the latest feed, the
     * featured rail, trending and search.
     */
    public const MIN_POSTS_PER_CATEGORY_SECTION = 3;

    public function getHomepageData(): array
    {
        // Keep this longer than the five-minute scheduler interval so the
        // in-process prewarm avoids a network request on every scheduler tick.
        return cache()->remember(self::CACHE_KEY, 600, function () {
            if (! Schema::hasTable('posts') || ! Schema::hasTable('categories')) {
                return [
                    'latestPosts' => collect(),
                    'aiTextPosts' => collect(),
                    'featuredStories' => collect(),
                    'editorsPicks' => collect(),
                    'categories' => collect(),
                    'postsByCategory' => [],
                    'trending' => collect(),
                    'freshCount' => 0,
                ];
            }

            $homepagePostColumns = [
                'id',
                'name',
                'description',
                'image',
                'status',
                'author_id',
                'author_type',
                'format_type',
                'is_featured',
                'views',
                'created_at',
                'updated_at',
            ];

            $homepagePostQuery = function () use ($homepagePostColumns) {
                return Post::query()
                    ->select($homepagePostColumns)
                    ->with([
                        'slugable',
                        // Load full categories; Botble's Category model uses traits/casts
                        // (HasTreeCategory, BaseStatusEnum, etc.) that need all columns.
                        // Selecting only id/name can silently break BelongsToMany hydration.
                        'categories',
                        'author',
                    ]);
            };

            $latestPosts = $homepagePostQuery()
                ->where('status', 'published')
                ->orderByDesc('created_at')
                ->take(6)
                ->get();

            $aiAuthor = Schema::hasTable('authors')
                ? Author::query()->where('email', 'genzai@genznewz.com')->first()
                : null;

            $aiTextPosts = collect();
            if ($aiAuthor) {
                $aiTextPosts = $homepagePostQuery()
                    ->where('format_type', 'text-only')
                    ->where('author_id', $aiAuthor->id)
                    ->where('status', 'published')
                    ->orderByDesc('created_at')
                    ->take(6)
                    ->get();
            }

            if ($aiTextPosts->count() < 6) {
                $additional = $homepagePostQuery()
                    ->where('format_type', 'text-only')
                    ->where('status', 'published')
                    ->whereNotIn('id', $aiTextPosts->pluck('id'))
                    ->orderByDesc('created_at')
                    ->take(6 - $aiTextPosts->count())
                    ->get();

                $aiTextPosts = $aiTextPosts->merge($additional);
            }

            $featuredStories = $homepagePostQuery()
                ->where('is_featured', true)
                ->where('status', 'published')
                ->whereNotIn('id', $aiTextPosts->pluck('id'))
                ->whereNotNull('image')
                ->where('image', '!=', '')
                ->orderByDesc('created_at')
                ->take(7)
                ->get();

            if ($featuredStories->count() < 7) {
                $featuredFallback = $homepagePostQuery()
                    ->where('status', 'published')
                    ->whereNotIn('id', $aiTextPosts->pluck('id'))
                    ->whereNotIn('id', $featuredStories->pluck('id'))
                    ->whereNotNull('image')
                    ->where('image', '!=', '')
                    ->orderByDesc('created_at')
                    ->take(7 - $featuredStories->count())
                    ->get();

                $featuredStories = $featuredStories->merge($featuredFallback);
            }

            $editorsPicks = $homepagePostQuery()
                ->where('status', 'published')
                ->whereNotIn('id', $aiTextPosts->pluck('id'))
                ->whereNotIn('id', $featuredStories->pluck('id'))
                ->orderByDesc('updated_at')
                ->take(5)
                ->get();

            $categories = Category::query()
                ->select(['id', 'name', 'parent_id', 'order'])
                ->with('slugable')
                ->where('status', 'published')
                ->where('parent_id', 0)
                ->orderBy('order')
                ->get();

            $categoryIds = $categories->pluck('id');

            $excludeFromCategories = $aiTextPosts
                ->pluck('id')
                ->merge($featuredStories->pluck('id'))
                ->unique()
                ->values();

            $allCategoryPosts = $homepagePostQuery()
                ->whereHas('categories', function ($query) use ($categoryIds) {
                    $query->whereIn('categories.id', $categoryIds);
                })
                ->where('status', 'published')
                ->whereNotIn('id', $excludeFromCategories->all())
                ->orderByDesc('created_at')
                ->get();

            $postsByCategory = $categories
                ->mapWithKeys(fn ($category) => [$category->id => collect()])
                ->all();

            $filledCategories = [];
            $categoryTarget = 3;

            foreach ($allCategoryPosts as $post) {
                foreach ($post->categories as $postCategory) {
                    $categoryId = $postCategory->id;

                    if (! isset($postsByCategory[$categoryId])) {
                        continue;
                    }

                    if ($postsByCategory[$categoryId]->count() >= $categoryTarget) {
                        continue;
                    }

                    $postsByCategory[$categoryId]->push($post);

                    if ($postsByCategory[$categoryId]->count() === $categoryTarget) {
                        $filledCategories[$categoryId] = true;
                    }
                }

                if (count($filledCategories) === $categoryIds->count()) {
                    break;
                }
            }

            $homepageVisibleIds = $aiTextPosts->pluck('id')
                ->merge($featuredStories->pluck('id'))
                ->merge($editorsPicks->pluck('id'))
                ->unique()
                ->values();

            $trending = $homepagePostQuery()
                ->where('status', 'published')
                ->whereNotIn('id', $homepageVisibleIds->all())
                ->orderByDesc('views')
                ->take(5)
                ->get();

            // Stories published in the last 24 hours. The hero previously showed
            // $latestPosts->count(), which is capped at six by the take(6) above
            // and so always read "6 fresh stories" regardless of real output.
            $freshCount = Post::query()
                ->where('status', 'published')
                ->where('created_at', '>=', now()->subDay())
                ->count();

            return compact('latestPosts', 'aiTextPosts', 'featuredStories', 'editorsPicks', 'categories', 'postsByCategory', 'trending', 'freshCount');
        });
    }

    public function getRenderableCategories(array $homepageData): Collection
    {
        $categories = $homepageData['categories'] ?? collect();
        $postsByCategory = $homepageData['postsByCategory'] ?? [];

        return $categories
            ->filter(function ($category) use ($postsByCategory) {
                $posts = $postsByCategory[$category->id] ?? collect();

                if ($posts->count() < self::MIN_POSTS_PER_CATEGORY_SECTION) {
                    return false;
                }

                // A published category with no slug resolves to the site root, so its
                // "View all" link silently points at the homepage. Skip those rather
                // than render a section that cannot be browsed.
                return ! empty(optional($category->slugable)->key);
            })
            ->map(function ($category) use ($postsByCategory) {
                return [
                    'category' => $category,
                    'posts' => $postsByCategory[$category->id] ?? collect(),
                ];
            })
            ->values();
    }
}
