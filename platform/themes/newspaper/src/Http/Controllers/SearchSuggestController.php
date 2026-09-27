<?php

namespace Theme\Newspaper\Http\Controllers;

use Botble\Blog\Models\Category;
use Botble\Blog\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Live search suggestions for the site-wide search overlay.
 *
 * Returns grouped, directly linkable results so the client can render a
 * keyboard-navigable dropdown. Everything here is public read-only data.
 */
class SearchSuggestController
{
    protected const MAX_RESULTS = 6;

    protected const MIN_QUERY_LENGTH = 2;

    public function __invoke(Request $request): JsonResponse
    {
        $query = Str::limit(
            preg_replace('/\s+/', ' ', trim(strip_tags((string) $request->input('q', '')))) ?? '',
            80,
            ''
        );

        if (mb_strlen($query) < self::MIN_QUERY_LENGTH) {
            // No query yet — offer real, data-derived destinations instead of
            // invented "popular searches".
            return response()->json([
                'query' => $query,
                'suggestions' => [],
                'trending' => $this->trendingTopics(),
            ])->header('Cache-Control', 'public, max-age=300');
        }

        $payload = cache()->remember(
            'search_suggest:' . md5(mb_strtolower($query)),
            60,
            fn (): array => [
                'posts' => $this->matchPosts($query),
                'categories' => $this->matchCategories($query),
            ]
        );

        return response()->json([
            'query' => $query,
            'suggestions' => array_merge($payload['categories'], $payload['posts']),
            'trending' => [],
        ])->header('Cache-Control', 'public, max-age=60');
    }

    protected function matchPosts(string $query): array
    {
        $like = $this->escapeLike($query);

        return Post::query()
            ->where('status', 'published')
            ->where('name', 'like', '%' . $like . '%')
            ->with(['categories'])
            // Rank by where the match lands, not just by popularity: a story
            // titled "AI News Roundup" is a better suggestion for "ai" than
            // an unrelated story that merely contains the letters.
            ->orderByRaw(
                'CASE
                    WHEN name LIKE ? THEN 0
                    WHEN name LIKE ? THEN 1
                    ELSE 2
                 END',
                [$like . '%', '% ' . $like . '%']
            )
            ->orderByDesc('views')
            ->orderByDesc('created_at')
            ->take(self::MAX_RESULTS)
            ->get()
            ->map(fn (Post $post): array => [
                'type' => 'post',
                'title' => Str::limit(trim(strip_tags((string) $post->name)), 90),
                'url' => (string) $post->url,
                'meta' => $post->categories->first()->name ?? null,
            ])
            ->values()
            ->all();
    }

    protected function matchCategories(string $query): array
    {
        return Category::query()
            ->where('status', 'published')
            ->where('name', 'like', '%' . $this->escapeLike($query) . '%')
            // Only suggest a topic the reader can actually land on and read: it
            // needs a public URL and at least one published story.
            ->whereHas('slugable')
            ->whereHas('posts', fn ($posts) => $posts->where('posts.status', 'published'))
            ->withCount(['posts' => fn ($posts) => $posts->where('posts.status', 'published')])
            ->orderByDesc('posts_count')
            ->take(2)
            ->get()
            ->map(fn (Category $category): array => [
                'type' => 'category',
                'title' => (string) $category->name,
                'url' => (string) $category->url,
                'meta' => 'Topic',
            ])
            ->values()
            ->all();
    }

    /**
     * Offer the busiest categories that actually have recent stories, so the
     * empty-state dropdown only ever links somewhere with content.
     */
    protected function trendingTopics(): array
    {
        return cache()->remember('search_suggest:trending', 900, function (): array {
            $publishedCount = fn ($posts) => $posts->where('posts.status', 'published');

            return Category::query()
                ->where('status', 'published')
                ->whereHas('slugable')
                ->withCount(['posts' => $publishedCount])
                // `has()` compiles to a correlated subquery in the WHERE
                // clause. A bare `having('posts_count', ...)` relies on MySQL
                // allowing HAVING without GROUP BY and breaks on SQLite and
                // PostgreSQL.
                ->has('posts', '>=', 3, 'and', $publishedCount)
                ->orderByDesc('posts_count')
                ->take(6)
                ->get()
                ->map(fn (Category $category): array => [
                    'type' => 'category',
                    'title' => (string) $category->name,
                    'url' => (string) $category->url,
                    'meta' => $category->posts_count . ' stories',
                ])
                ->values()
                ->all();
        });
    }

    /**
     * `%` and `_` are LIKE wildcards; a user typing them should match those
     * literal characters rather than turning into an unintended pattern.
     */
    protected function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}
