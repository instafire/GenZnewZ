<?php

namespace App\Services;

use App\Models\UserBehaviorLog;
use Botble\Blog\Models\Post;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;

class RecommendationService
{
    /**
     * Get posts related to the given post based on shared categories and tags.
     */
    public function getRelatedPosts(Post $post, int $limit = 4): Collection
    {
        $post->loadMissing(['categories', 'tags', 'slugable']);

        $categoryIds = $post->categories->pluck('id')->filter()->values()->all();
        $tagIds = $post->tags->pluck('id')->filter()->values()->all();

        $cacheKey = sprintf(
            'recommendations:related:%d:%s:%s:%d',
            $post->id,
            implode(',', $categoryIds) ?: 'none',
            implode(',', $tagIds) ?: 'none',
            $limit
        );

        return Cache::remember($cacheKey, 3600, function () use ($post, $categoryIds, $tagIds, $limit) {
            $relatedByCategory = collect();
            $relatedByTag = collect();

            if (! empty($categoryIds)) {
                $relatedByCategory = Post::query()
                    ->select(['id', 'name', 'description', 'image', 'views', 'created_at'])
                    ->with(['slugable', 'categories:id,name'])
                    ->where('status', 'published')
                    ->where('id', '!=', $post->id)
                    ->whereHas('categories', function ($query) use ($categoryIds): void {
                        $query->whereIn('categories.id', $categoryIds);
                    })
                    ->orderByDesc('created_at')
                    ->limit(20)
                    ->get();
            }

            if (! empty($tagIds)) {
                $relatedByTag = Post::query()
                    ->select(['id', 'name', 'description', 'image', 'views', 'created_at'])
                    ->with(['slugable', 'categories:id,name'])
                    ->where('status', 'published')
                    ->where('id', '!=', $post->id)
                    ->whereHas('tags', function ($query) use ($tagIds): void {
                        $query->whereIn('tags.id', $tagIds);
                    })
                    ->orderByDesc('created_at')
                    ->limit(20)
                    ->get();
            }

            $merged = $relatedByCategory
                ->merge($relatedByTag)
                ->unique('id')
                ->sortByDesc('created_at')
                ->values();

            if ($merged->count() < $limit) {
                $existingIds = $merged->pluck('id')->merge([$post->id])->all();
                $fallback = Post::query()
                    ->select(['id', 'name', 'description', 'image', 'views', 'created_at'])
                    ->with(['slugable', 'categories:id,name'])
                    ->where('status', 'published')
                    ->whereNotIn('id', $existingIds)
                    ->orderByDesc('created_at')
                    ->limit($limit - $merged->count())
                    ->get();

                $merged = $merged->merge($fallback)->unique('id')->values();
            }

            return $merged->take($limit)->values();
        });
    }

    /**
     * Alias for getRelatedPosts with a more reader-friendly name.
     */
    public function getMoreLikeThis(Post $post, int $limit = 4): Collection
    {
        return $this->getRelatedPosts($post, $limit);
    }

    /**
     * Get trending posts ordered by views, optionally excluding some IDs.
     */
    public function getTrendingPosts(int $limit = 5, array $excludeIds = []): Collection
    {
        $cacheKey = sprintf('recommendations:trending:%d:%s', $limit, implode(',', $excludeIds) ?: 'none');

        return Cache::remember($cacheKey, 1800, function () use ($limit, $excludeIds) {
            return Post::query()
                ->select(['id', 'name', 'description', 'image', 'views', 'created_at'])
                ->with(['slugable', 'categories:id,name'])
                ->where('status', 'published')
                ->when(! empty($excludeIds), function ($query) use ($excludeIds): void {
                    $query->whereNotIn('id', $excludeIds);
                })
                ->orderByDesc('views')
                ->orderByDesc('created_at')
                ->limit($limit)
                ->get();
        });
    }

    /**
     * Get a mixed recommendation list for the homepage.
     * Combines trending and latest posts.
     */
    public function getRecommendedForHomepage(int $limit = 6, array $excludeIds = []): Collection
    {
        $cacheKey = sprintf('recommendations:homepage:%d:%s', $limit, implode(',', $excludeIds) ?: 'none');

        return Cache::remember($cacheKey, 1800, function () use ($limit, $excludeIds) {
            $half = (int) ceil($limit / 2);

            $trending = $this->getTrendingPosts($half, $excludeIds);
            $trendingIds = $trending->pluck('id')->all();

            $latest = Post::query()
                ->select(['id', 'name', 'description', 'image', 'views', 'created_at'])
                ->with(['slugable', 'categories:id,name'])
                ->where('status', 'published')
                ->whereNotIn('id', array_merge($excludeIds, $trendingIds))
                ->orderByDesc('created_at')
                ->limit($limit - $trending->count())
                ->get();

            return $trending
                ->merge($latest)
                ->unique('id')
                ->take($limit)
                ->values();
        });
    }

    /**
     * Clear recommendation caches for a post.
     * Clears the cache keys for the given limit and a set of common limits.
     */
    public function clearCacheForPost(Post $post, int $limit = 4): void
    {
        $post->loadMissing(['categories', 'tags']);
        $categoryIds = $post->categories->pluck('id')->filter()->values()->all();
        $tagIds = $post->tags->pluck('id')->filter()->values()->all();

        $limits = array_unique([$limit, 4, 6, 8]);

        foreach ($limits as $limitValue) {
            $cacheKey = sprintf(
                'recommendations:related:%d:%s:%s:%d',
                $post->id,
                implode(',', $categoryIds) ?: 'none',
                implode(',', $tagIds) ?: 'none',
                $limitValue
            );

            Cache::forget($cacheKey);
        }
    }

    /**
     * Get personalized recommendations based on the current visitor's behavior history.
     * Falls back to related posts or homepage recommendations when no behavior exists.
     *
     * @param  Post|null  $currentPost  The post currently being viewed, if any.
     * @param  int  $limit  Number of recommendations to return.
     * @param  string|null  $visitorId  Optional visitor identifier. If null, the cookie is read.
     */
    public function getPersonalizedRecommendations(?Post $currentPost = null, int $limit = 4, ?string $visitorId = null): Collection
    {
        $visitorId = $visitorId ?? Request::cookie('gz_visitor_id');
        $userId = auth()->id();

        if (! $visitorId && ! $userId) {
            return $currentPost
                ? $this->getRelatedPosts($currentPost, $limit)
                : $this->getRecommendedForHomepage($limit);
        }

        $cacheKey = sprintf(
            'recommendations:personalized:%s:%d:%s',
            $this->hashIdentifier($visitorId, $userId),
            $limit,
            $currentPost ? 'post_' . $currentPost->id : 'homepage'
        );

        return Cache::remember($cacheKey, 900, function () use ($visitorId, $userId, $currentPost, $limit) {
            $recentPostIds = $this->getRecentBehaviorPostIds($visitorId, $userId, 30);

            if ($recentPostIds->isEmpty()) {
                return $currentPost
                    ? $this->getRelatedPosts($currentPost, $limit)
                    : $this->getRecommendedForHomepage($limit);
            }

            $preferredCategoryIds = $this->getPreferredCategoryIds($recentPostIds, 5);
            $preferredTagIds = $this->getPreferredTagIds($recentPostIds, 5);

            $excludeIds = $recentPostIds->all();
            if ($currentPost) {
                $excludeIds[] = $currentPost->id;
            }

            $personalized = $this->buildPersonalizedQuery(
                $preferredCategoryIds,
                $preferredTagIds,
                $excludeIds,
                $limit
            )->get();

            if ($personalized->count() < $limit) {
                $needed = $limit - $personalized->count();
                $trending = $this->getTrendingPosts($needed, array_merge($excludeIds, $personalized->pluck('id')->all()));
                $personalized = $personalized->merge($trending)->unique('id')->values();
            }

            return $personalized->take($limit)->values();
        });
    }

    /**
     * Get the IDs of posts the visitor has recently viewed or clicked.
     */
    protected function getRecentBehaviorPostIds(?string $visitorId, ?int $userId, int $daysBack): Collection
    {
        $query = UserBehaviorLog::query()
            ->where('created_at', '>=', now()->subDays($daysBack));

        if ($userId) {
            $query->where('user_id', $userId);
        } else {
            $query->where('visitor_id', $visitorId);
        }

        return $query
            ->orderByDesc('created_at')
            ->limit(50)
            ->pluck('post_id')
            ->unique()
            ->values();
    }

    /**
     * Hash the visitor/user identifier to keep cache keys short and avoid leaking IDs.
     */
    protected function hashIdentifier(?string $visitorId, ?int $userId): string
    {
        $raw = $userId ? 'user:' . $userId : 'visitor:' . $visitorId;

        return hash('sha256', $raw);
    }

    /**
     * Determine the visitor's most frequently interacted categories.
     */
    protected function getPreferredCategoryIds(Collection $postIds, int $limit): Collection
    {
        if ($postIds->isEmpty()) {
            return collect();
        }

        return DB::table('post_categories')
            ->whereIn('post_id', $postIds->all())
            ->select('category_id')
            ->groupBy('category_id')
            ->orderByRaw('COUNT(*) DESC')
            ->limit($limit)
            ->pluck('category_id');
    }

    /**
     * Determine the visitor's most frequently interacted tags.
     */
    protected function getPreferredTagIds(Collection $postIds, int $limit): Collection
    {
        if ($postIds->isEmpty()) {
            return collect();
        }

        return DB::table('post_tags')
            ->whereIn('post_id', $postIds->all())
            ->select('tag_id')
            ->groupBy('tag_id')
            ->orderByRaw('COUNT(*) DESC')
            ->limit($limit)
            ->pluck('tag_id');
    }

    /**
     * Build a query for posts matching the visitor's preferred categories and tags.
     */
    protected function buildPersonalizedQuery(Collection $categoryIds, Collection $tagIds, array $excludeIds, int $limit)
    {
        $query = Post::query()
            ->select(['id', 'name', 'description', 'image', 'views', 'created_at'])
            ->with(['slugable', 'categories:id,name'])
            ->where('status', 'published')
            ->whereNotIn('id', $excludeIds);

        $hasCategories = $categoryIds->isNotEmpty();
        $hasTags = $tagIds->isNotEmpty();

        if ($hasCategories && $hasTags) {
            $query->where(function ($q) use ($categoryIds, $tagIds): void {
                $q->whereHas('categories', function ($q) use ($categoryIds): void {
                    $q->whereIn('categories.id', $categoryIds->all());
                })->orWhereHas('tags', function ($q) use ($tagIds): void {
                    $q->whereIn('tags.id', $tagIds->all());
                });
            });
        } elseif ($hasCategories) {
            $query->whereHas('categories', function ($q) use ($categoryIds): void {
                $q->whereIn('categories.id', $categoryIds->all());
            });
        } elseif ($hasTags) {
            $query->whereHas('tags', function ($q) use ($tagIds): void {
                $q->whereIn('tags.id', $tagIds->all());
            });
        }

        return $query
            ->orderByDesc('created_at')
            ->limit($limit * 3);
    }
}
