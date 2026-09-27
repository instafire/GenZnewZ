<?php

namespace Botble\Blog\Listeners;

use Botble\Blog\Models\Category;
use Botble\Blog\Models\Post;
use Botble\Blog\Models\Tag;
use Botble\Theme\Events\RenderingSiteMapEvent;
use Botble\Theme\Facades\SiteMapManager;
use Illuminate\Support\Arr;

class RenderingSiteMapListener
{
    public function handle(RenderingSiteMapEvent $event): void
    {
        if ($key = $event->key) {
            switch ($key) {
                case 'blog-categories':
                    $categories = Category::query()
                        ->with('slugable')
                        ->wherePublished()
                        ->select(['id', 'name', 'updated_at'])->latest()
                        ->get();

                    foreach ($categories as $category) {
                        SiteMapManager::add($category->url, $category->updated_at, '0.8');
                    }

                    break;
                case 'blog-tags':
                    $tags = Tag::query()
                        ->with('slugable')
                        ->wherePublished()->latest()
                        ->select(['id', 'name', 'updated_at'])
                        ->get();

                    foreach ($tags as $tag) {
                        SiteMapManager::add($tag->url, $tag->updated_at, '0.3', 'weekly');
                    }

                    break;
            }

            // Handle posts with pagination using new standardized pattern
            $paginationData = SiteMapManager::extractPaginationDataByPattern($key, 'blog-posts', 'monthly-archive');

            if ($paginationData) {
                $matches = $paginationData['matches'];
                $year = Arr::get($matches, 1);
                $month = Arr::get($matches, 2);

                if ($year && $month) {
                    $posts = Post::query()
                        ->wherePublished()
                        ->whereYear('created_at', $year)
                        ->whereMonth('created_at', $month)
                        ->latest('updated_at')
                        ->select(['id', 'name', 'updated_at'])
                        ->with(['slugable'])
                        ->skip($paginationData['offset'])
                        ->take($paginationData['limit'])
                        ->get();

                    foreach ($posts as $post) {
                        if (! $post->slugable) {
                            continue;
                        }

                        SiteMapManager::add($post->url, $post->updated_at, '0.8');
                    }
                }
            }

            return;
        }

        // Register blog-posts key for route matching
        SiteMapManager::registerKey('blog-posts');

        // Generate sitemap indexes using the new SiteMapManager pagination functionality
        $posts = Post::query()
            ->selectRaw('YEAR(created_at) as created_year, MONTH(created_at) as created_month, MAX(created_at) as created_at, COUNT(*) as post_count')
            ->wherePublished()
            ->groupBy('created_year', 'created_month')
            ->orderByDesc('created_year')
            ->orderByDesc('created_month')
            ->get();

        if ($posts->isNotEmpty()) {
            foreach ($posts as $post) {
                $formattedMonth = str_pad($post->created_month, 2, '0', STR_PAD_LEFT);
                $baseKey = sprintf('blog-posts-%s-%s', $post->created_year, $formattedMonth);
                
                // Register the key for route matching
                SiteMapManager::registerKey($baseKey);

                // Use the new createPaginatedSitemaps method
                SiteMapManager::createPaginatedSitemaps($baseKey, $post->post_count, $post->created_at);
            }
        }

        // Register and add categories sitemap
        SiteMapManager::registerKey('blog-categories');
        $categoryLastUpdated = Category::query()
            ->wherePublished()
            ->latest('updated_at')
            ->value('updated_at');

        if ($categoryLastUpdated) {
            SiteMapManager::addSitemap(SiteMapManager::route('blog-categories'), $categoryLastUpdated);
        }

        // Register and add tags sitemap
        SiteMapManager::registerKey('blog-tags');
        $tagLastUpdated = Tag::query()
            ->wherePublished()
            ->latest('updated_at')
            ->value('updated_at');

        if ($tagLastUpdated) {
            SiteMapManager::addSitemap(SiteMapManager::route('blog-tags'), $tagLastUpdated);
        }
    }
}
