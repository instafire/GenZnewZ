<?php

namespace Theme\Newspaper\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Botble\Base\Events\CreatedContentEvent;
use Botble\Base\Events\UpdatedContentEvent;
use Botble\Theme\Events\RenderingSiteMapEvent;
use Theme\Newspaper\Listeners\RenderingSiteMapListener;
use App\Listeners\ContentPublishedListener;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        RenderingSiteMapEvent::class => [
            RenderingSiteMapListener::class,
        ],
        CreatedContentEvent::class => [
            [ContentPublishedListener::class, 'handleCreated'],
        ],
        UpdatedContentEvent::class => [
            [ContentPublishedListener::class, 'handleUpdated'],
        ],
    ];

    public function boot(): void
    {
        parent::boot();
    }
}
