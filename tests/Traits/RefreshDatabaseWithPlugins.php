<?php

namespace Tests\Traits;

use Botble\Blog\Models\Category;
use Botble\Blog\Models\Post;
use Botble\Slug\Models\Slug;
use Botble\Slug\Providers\SlugServiceProvider;
use Botble\Slug\SlugHelper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;

/**
 * Extends Laravel's RefreshDatabase to also migrate Botble plugin migrations.
 *
 * The standard RefreshDatabase trait only runs migrations from database/migrations.
 * Botble CMS plugins keep their migrations under platform/plugins/<plugin>/database/migrations
 * and platform/packages/<package>/database/migrations, so tests that depend on plugin
 * tables (posts, categories, tags, slugs, etc.) need this trait.
 */
trait RefreshDatabaseWithPlugins
{
    use RefreshDatabase {
        RefreshDatabase::refreshDatabase as baseRefreshDatabase;
    }

    /**
     * Refresh the database and run plugin migrations.
     */
    protected function refreshDatabase(): void
    {
        $this->baseRefreshDatabase();
        $this->runPluginMigrations();
        $this->bootSlugProvider();
        $this->registerSlugableRelation();
    }

    /**
     * Force the slug package to boot so its model accessors exist in tests.
     *
     * Two things have to line up for `$post->url` / `$category->slug` to work,
     * and neither happens under the test lifecycle:
     *
     *  1. Plugins declare their slug-supported models from inside a
     *     `SlugHelper::registering()` callback that only fires once the plugin
     *     boots. Left alone, only `Page` is supported, so Post and Category
     *     never get an accessor.
     *  2. SlugServiceProvider adds those accessors from an `app->booted()`
     *     callback, which is queued after the application has already booted
     *     and therefore never runs.
     *
     * Without this, `$post->url` and `$category->slug` are silently null in
     * every test, so any assertion about a URL passes or fails for the wrong
     * reason.
     */
    protected function bootSlugProvider(): void
    {
        if (! class_exists(SlugHelper::class) || ! class_exists(SlugServiceProvider::class)) {
            return;
        }

        // Mirrors BlogServiceProvider's own registration for the two models
        // this suite depends on, applied straight to the resolved singleton.
        $helper = $this->app->make(SlugHelper::class);
        $helper->registerModule([Post::class, Category::class]);
        $helper->setPrefix(Post::class, null, true);
        $helper->setPrefix(Category::class, null, true);

        // Re-running the provider's boot re-queues its `booted()` callback.
        // The application is already booted, so that callback fires at once
        // and creates the accessors for the models registered above. Calling
        // boot() rather than register() matters: register() would rebind the
        // SlugHelper singleton and discard the registration we just made.
        $provider = $this->app->getProvider(SlugServiceProvider::class);

        if ($provider) {
            $provider->boot();
        }
    }

    /**
     * Run migrations from the plugin directories required by the test suite.
     *
     * Add more paths here as tests need tables from additional plugins.
     */
    protected function runPluginMigrations(): void
    {
        $paths = [
            base_path('platform/packages/slug/database/migrations'),
            base_path('platform/plugins/blog/database/migrations'),
        ];

        foreach ($paths as $path) {
            if (! File::isDirectory($path)) {
                continue;
            }

            $this->artisan('migrate', [
                '--path' => $path,
                '--realpath' => true,
            ]);
        }
    }

    /**
     * Ensure the slugable morph relation is registered for Post and Category models in tests.
     *
     * The SlugServiceProvider is deferred and may not have registered the dynamic
     * relation when tests run. We register it manually only if it is not already
     * defined.
     */
    protected function registerSlugableRelation(): void
    {
        if (! class_exists(Slug::class)) {
            return;
        }

        if (class_exists(Post::class)) {
            Post::resolveRelationUsing('slugable', $this->slugableRelationResolver());
        }

        if (class_exists(Category::class)) {
            Category::resolveRelationUsing('slugable', $this->slugableRelationResolver());
        }
    }

    /**
     * Build the slugable morph relation definition used by Post and Category models.
     */
    protected function slugableRelationResolver(): \Closure
    {
        return function (Post|Category $model) {
            return $model->morphOne(Slug::class, 'reference')->select([
                'id',
                'key',
                'reference_type',
                'reference_id',
                'prefix',
            ]);
        };
    }
}
