<?php

namespace Theme\Newspaper\Listeners;

use Botble\Base\Facades\BaseHelper;
use Botble\Theme\Events\RenderingSiteMapEvent;
use Botble\Theme\Facades\SiteMapManager;
use Carbon\Carbon;

class RenderingSiteMapListener
{
    public function handle(RenderingSiteMapEvent $event): void
    {
        // Only add to main sitemap (when key is null or 'pages')
        if ($event->key !== null && $event->key !== 'pages') {
            return;
        }

        $now = Carbon::now()->toDateTimeString();

        try {
            // Home Page - Highest priority
            SiteMapManager::add(
                BaseHelper::getHomepageUrl() ?: url('/'),
                $now,
                '1.0',
                'daily'
            );
        } catch (\Exception $e) {
            // Fallback to root URL
            SiteMapManager::add(url('/'), $now, '1.0', 'daily');
        }

        // Custom Theme Pages - wrapped in try-catch for safety
        $this->addSafeRoute('todays.paper', $now, '0.9', 'daily');
        $this->addSafeRoute('live.world.events', $now, '0.8', 'hourly');
        $this->addSafeRoute('about.us', $now, '0.4', 'monthly');
        $this->addSafeRoute('contact.page', $now, '0.4', 'monthly');
        $this->addSafeRoute('privacy.policy', $now, '0.3', 'yearly');
        $this->addSafeRoute('terms.of.service', $now, '0.3', 'yearly');
        $this->addSafeRoute('cookie.policy', $now, '0.3', 'yearly');
        $this->addSafeRoute('our.team', $now, '0.3', 'monthly');

        // AI reporter profiles are deliberately NOT advertised in the Google-facing
        // sitemap. They are agent-entity pages (a name, a bio and an article list),
        // so thirty-odd near-identical profiles sitting alongside real reporting
        // reads as thin content and dilutes the crawl-quality signal of this file.
        // They stay crawlable and indexable, and are advertised to agents instead
        // through /sitemap-ai.xml. See GenerateSitemap::generateAiSitemap().
    }

    /**
     * Safely add a route to sitemap with error handling
     */
    protected function addSafeRoute(string $routeName, string $lastmod, string $priority, string $changefreq): void
    {
        try {
            if (\Route::has($routeName)) {
                SiteMapManager::add(
                    route($routeName),
                    $lastmod,
                    $priority,
                    $changefreq
                );
            }
        } catch (\Exception $e) {
            // Silently skip if route is not available
        }
    }

}
