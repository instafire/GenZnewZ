<?php

namespace Botble\Shortcode\Http\Controllers;

use Botble\Base\Facades\Html;
use Botble\Base\Forms\FormAbstract;
use Botble\Base\Http\Controllers\BaseController;
use Botble\Shortcode\Events\ShortcodeAdminConfigRendering;
use Botble\Shortcode\Facades\Shortcode;
use Botble\Shortcode\Forms\ShortcodeForm;
use Botble\Shortcode\Http\Requests\GetShortcodeDataRequest;
use Botble\Shortcode\Http\Requests\RenderBlockUiRequest;
use Carbon\Carbon;
use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;

class ShortcodeController extends BaseController
{
    public function ajaxGetAdminConfig(?string $key, GetShortcodeDataRequest $request)
    {
        ShortcodeAdminConfigRendering::dispatch();

        $registered = shortcode()->getAll();

        $key = $key ?: $request->input('key');

        $data = Arr::get($registered, $key . '.admin_config');

        $attributes = [];
        $content = null;

        if ($code = $request->input('code')) {
            $compiler = shortcode()->getCompiler();
            $attributes = $compiler->getAttributes(html_entity_decode($code));
            $content = $compiler->getContent();
        }

        if ($data instanceof Closure || is_callable($data)) {
            $data = call_user_func($data, $attributes, $content);

            if ($modifier = Arr::get($registered, $key . '.admin_config_modifier')) {
                $data = call_user_func($modifier, $data, $attributes, $content);
            }

            // If it's a ShortcodeForm, add cache warning and caching options
            if ($data instanceof ShortcodeForm) {
                $data->withCacheWarning($key)->withCaching();
                $data = $data->renderForm();
            } elseif ($data instanceof FormAbstract) {
                $data = $data->renderForm();
            }
        }

        $data = apply_filters(SHORTCODE_REGISTER_CONTENT_IN_ADMIN, $data, $key, $attributes);

        if (! $data) {
            $data = Html::tag('code', Shortcode::generateShortcode($key, $attributes))->toHtml();
        }

        return $this
            ->httpResponse()
            ->setData($data);
    }

    public function ajaxRenderUiBlock(RenderBlockUiRequest $request)
    {
        $name = $request->input('name');

        if (! in_array($name, array_keys(Shortcode::getAll()))) {
            return $this
                ->httpResponse()
                ->setData(null);
        }

        $attributes = $request->input('attributes', []);

        // Create a cache key based on the shortcode name, attributes, and current locale
        $locale = app()->getLocale();
        $cacheKey = 'shortcode_' . md5($name . serialize($attributes) . $locale);

        if (! setting('shortcode_cache_enabled', false)) {
            $code = Shortcode::generateShortcode($name, $attributes);
            $content = Shortcode::compile($code, true)->toHtml();

            return $this->httpResponse()->setData($content);
        }

        // Check if this shortcode should be cached for longer
        $cacheable = $this->isShortcodeCacheable($name);

        // Get cache durations from settings
        $defaultTtl = (int) setting('shortcode_cache_ttl_default', 5);
        $cacheableTtl = (int) setting('shortcode_cache_ttl_cacheable', 1800);

        // Set cache duration based on whether the shortcode is cacheable
        $cacheDuration = $cacheable
            ? Carbon::now()->addSeconds($cacheableTtl)
            : Carbon::now()->addSeconds($defaultTtl);

        $content = Cache::remember($cacheKey, $cacheDuration, function () use ($name, $attributes) {
            $code = Shortcode::generateShortcode($name, $attributes);

            return Shortcode::compile($code, true)->toHtml();
        });

        return $this
            ->httpResponse()
            ->setData($content);
    }

    protected function isShortcodeCacheable(string $name): bool
    {
        // List of shortcodes that should be cached for longer periods
        // These are typically shortcodes that don't change frequently or don't contain dynamic content
        $cacheableShortcodes = [
            'static-block',
            'featured-posts',
            'gallery',
            'youtube-video',
            'google-map',
            'contact-form',
            'image',
        ];

        return in_array($name, $cacheableShortcodes);
    }
}
