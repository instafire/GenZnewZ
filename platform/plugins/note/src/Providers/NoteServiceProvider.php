<?php

namespace Botble\Note\Providers;

use Botble\Base\Supports\ServiceProvider;
use Botble\Base\Traits\LoadAndPublishDataTrait;

/**
 * @since 07/02/2016 09:50 AM
 */
class NoteServiceProvider extends ServiceProvider
{
    use LoadAndPublishDataTrait;

    public function boot(): void
    {
        $this
            ->setNamespace('plugins/note')
            ->loadAndPublishViews()
            ->loadAndPublishTranslations()
            ->loadMigrations();

        $this->app->register(EventServiceProvider::class);

        $this->app->booted(fn () => $this->app->register(HookServiceProvider::class));
    }
}
