<?php

namespace Botble\Sitemap\Providers;

use Botble\Base\Events\CreatedContentEvent;
use Botble\Base\Events\DeletedContentEvent;
use Botble\Base\Events\UpdatedContentEvent;
use Botble\Base\Facades\PanelSectionManager;
use Botble\Base\PanelSections\PanelSectionItem;
use Botble\Base\Services\ClearCacheService;
use Botble\Base\Supports\ServiceProvider;
use Botble\Base\Traits\LoadAndPublishDataTrait;
use Botble\Setting\PanelSections\SettingCommonPanelSection;
use Botble\Sitemap\Commands\IndexNowSubmissionCommand;
use Botble\Sitemap\Events\SitemapUpdatedEvent;
use Botble\Sitemap\Listeners\IndexNowSubmissionListener;
use Botble\Sitemap\Services\IndexNowService;
use Botble\Sitemap\Sitemap;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Foundation\Application;

class SitemapServiceProvider extends ServiceProvider
{
    use LoadAndPublishDataTrait;

    protected bool $defer = true;

    public function register(): void
    {
        $this->app->bind('sitemap', function (Application $app) {
            $config = $app['config']->get('packages.sitemap.config', []);

            return new Sitemap(
                $config,
                $app[Repository::class],
                $app['config'],
                $app['files'],
                $app[ResponseFactory::class],
                $app['view']
            );
        });

        $this->app->alias('sitemap', Sitemap::class);

        $this->app->singleton(IndexNowService::class);
    }

    public function boot(): void
    {
        $this
            ->setNamespace('packages/sitemap')
            ->loadAndPublishConfigurations(['config', 'permissions'])
            ->loadAndPublishViews()
            ->loadAndPublishTranslations()
            ->loadRoutes()
            ->publishAssets();

        $this->app['events']->listen([
            CreatedContentEvent::class,
            UpdatedContentEvent::class,
            DeletedContentEvent::class,
        ], function (): void {
            ClearCacheService::make()->clearFrameworkCache();

            // Fire sitemap updated event to trigger search engine pings
            event(new SitemapUpdatedEvent());
        });

        $this->app['events']->listen(SitemapUpdatedEvent::class, IndexNowSubmissionListener::class);

        if ($this->app->runningInConsole()) {
            $this->commands([
                IndexNowSubmissionCommand::class,
            ]);
        }

        PanelSectionManager::default()->beforeRendering(function (): void {
            PanelSectionManager::registerItem(
                SettingCommonPanelSection::class,
                function () {
                    return PanelSectionItem::make('sitemap')
                        ->setTitle(trans('packages/sitemap::sitemap.settings.title'))
                        ->withIcon('ti ti-sitemap')
                        ->withDescription(trans('packages/sitemap::sitemap.settings.description'))
                        ->withPriority(1000)
                        ->withRoute('sitemap.settings');
                }
            );
        });
    }

    public function provides(): array
    {
        return ['sitemap', Sitemap::class];
    }
}
