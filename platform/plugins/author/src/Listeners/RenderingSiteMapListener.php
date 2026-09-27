<?php

namespace Botble\Author\Listeners;

use Botble\Author\Models\Author;
use Botble\Theme\Events\RenderingSiteMapEvent;
use Botble\Theme\Facades\SiteMapManager;

class RenderingSiteMapListener
{
    public function handle(RenderingSiteMapEvent $event): void
    {
        // Only add authors to the main sitemap (when key is null or 'pages')
        // Don't add to category, tag, or post-specific sitemaps
        if ($event->key !== null && $event->key !== 'pages') {
            return;
        }

        $authors = Author::query()
            ->wherePublished()
            ->orderByDesc('created_at')
            ->with(['slugable'])
            ->get();

        foreach ($authors as $author) {
            SiteMapManager::add($author->url, $author->updated_at, '0.8');
        }
    }
}
