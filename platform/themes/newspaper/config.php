<?php

use Botble\Base\Facades\BaseHelper;
use Botble\Shortcode\View\View;
use Botble\Theme\Theme;

return [
    'inherit' => null,

    'events' => [
        'before' => function (Theme $theme): void {
        },

        'beforeRenderTheme' => function (Theme $theme): void {
            $version = get_cms_version();

            // newspaper.css and newspaper.js used to be versioned with the static
            // CMS version (7.5.8). Their URLs therefore never changed, so an edit
            // shipped the new file but every browser and the CDN kept serving the
            // old copy - cached at the edge for a year. Version them by mtime like
            // the other assets so a change is always picked up.
            $newspaperCssPath = __DIR__ . '/public/css/newspaper.css';
            $newspaperJsPath = __DIR__ . '/public/js/newspaper.js';
            $newspaperCssVersion = file_exists($newspaperCssPath) ? (string) filemtime($newspaperCssPath) : $version;
            $newspaperJsVersion = file_exists($newspaperJsPath) ? (string) filemtime($newspaperJsPath) : $version;

            $clarityJsPath = public_path('themes/newspaper/public/js/clarity.js');
            $performanceCssPath = __DIR__ . '/public/css/performance.css';
            $performanceJsPath = __DIR__ . '/public/js/performance.js';
            $searchSuggestJsPath = __DIR__ . '/public/js/search-suggest.js';
            $articleToolsJsPath = __DIR__ . '/public/js/article-tools.js';
            $clarityJsVersion = file_exists($clarityJsPath) ? (string) filemtime($clarityJsPath) : $version;
            $performanceCssVersion = file_exists($performanceCssPath) ? (string) filemtime($performanceCssPath) : $version;
            $performanceJsVersion = file_exists($performanceJsPath) ? (string) filemtime($performanceJsPath) : $version;
            $searchSuggestJsVersion = file_exists($searchSuggestJsPath) ? (string) filemtime($searchSuggestJsPath) : $version;
            $articleToolsJsVersion = file_exists($articleToolsJsPath) ? (string) filemtime($articleToolsJsPath) : $version;
            // article-tools.js is registered per-view in views/post.blade.php:
            // it only serves article pages (progress bar, outline, save-for-later)
            // and was dead weight (15 KB) on every other page.

            // Add custom CSS/JS
            // Fonts load first so the @font-face rules are available before any
            // other stylesheet can trigger a font fetch.
            $fontsCssPath = __DIR__ . '/public/css/fonts.css';
            $fontsCssVersion = file_exists($fontsCssPath) ? (string) filemtime($fontsCssPath) : $version;

            $theme
                ->asset()
                ->usePath()
                ->add('fonts-css', 'css/fonts.css', [], [], $fontsCssVersion);

            // Base + component styles extracted from the old inline header CSS.
            // Loads BEFORE newspaper.css so page-specific sheets keep winning ties.
            $themeHeaderCssPath = __DIR__ . '/public/css/theme-header.css';
            $themeHeaderCssVersion = file_exists($themeHeaderCssPath) ? (string) filemtime($themeHeaderCssPath) : $version;
            $theme
                ->asset()
                ->usePath()
                ->add('theme-header-css', 'css/theme-header.css', [], [], $themeHeaderCssVersion);

            $theme
                ->asset()
                ->usePath()
                ->add('newspaper-css', 'css/newspaper.css', ['theme-header-css'], [], $newspaperCssVersion);

            $theme
                ->asset()
                ->usePath()
                ->add('performance-css', 'css/performance.css', ['newspaper-css'], [], $performanceCssVersion);

            // Dark mode styles extracted from the old inline header CSS. Must load
            // AFTER performance.css so dark-mode overrides keep winning the cascade
            // (19 dark selectors also appear in performance.css).
            $themeDarkCssPath = __DIR__ . '/public/css/theme-dark.css';
            $themeDarkCssVersion = file_exists($themeDarkCssPath) ? (string) filemtime($themeDarkCssPath) : $version;
            $theme
                ->asset()
                ->usePath()
                ->add('theme-dark-css', 'css/theme-dark.css', ['performance-css', 'theme-components-css'], [], $themeDarkCssVersion);

            // Component styles (origin badge, footer, news chat widget, .sr-only)
            // extracted from inline <style> blocks. Must load AFTER performance.css
            // and BEFORE theme-dark.css (which has no selector overlap with these,
            // verified 2026-09) to preserve the original DOM cascade order.
            $themeComponentsCssPath = __DIR__ . '/public/css/theme-components.css';
            $themeComponentsCssVersion = file_exists($themeComponentsCssPath) ? (string) filemtime($themeComponentsCssPath) : $version;
            $theme
                ->asset()
                ->usePath()
                ->add('theme-components-css', 'css/theme-components.css', ['performance-css'], [], $themeComponentsCssVersion);

            if (BaseHelper::isRtlEnabled()) {
                $theme
                    ->asset()
                    ->usePath()
                    ->add('rtl-css', 'css/rtl.css', [], [], $version);
            }

            // jQuery was removed from the frontend (2026-09-19): comment.js,
            // language-public.js and contact-public.js are vanilla now. The
            // featured-posts-slider shortcode (owl-carousel) still declares a
            // jquery dependency — re-register jQuery before using that shortcode.

            $theme
                ->asset()
                ->container('footer')
                ->usePath()
                ->add('clarity-js', 'js/clarity.js', [], [], $clarityJsVersion);

            $theme
                ->asset()
                ->container('footer')
                ->usePath()
                ->add('newspaper-js', 'js/newspaper.js', [], [], $newspaperJsVersion);

            $theme
                ->asset()
                ->container('footer')
                ->usePath()
                ->add('performance-js', 'js/performance.js', ['newspaper-js'], [], $performanceJsVersion);

            // Core Web Vitals → GA4 dataLayer capture (LCP/CLS/INP). Dormant until
            // a GA4 tag is present (gtag.js or via Cloudflare Zaraz); pushes are
            // no-ops otherwise. GA4 docs: web.dev/articles/vitals-ga4
            $webVitalsJsPath = public_path('themes/newspaper/public/js/web-vitals.js');
            $webVitalsJsVersion = file_exists($webVitalsJsPath) ? (string) filemtime($webVitalsJsPath) : $version;
            $theme
                ->asset()
                ->container('footer')
                ->usePath()
                ->add('web-vitals-js', 'js/web-vitals.js', [], [], $webVitalsJsVersion);

            // News chat widget handler, extracted from the former inline <script>
            // block (2026-09) to keep it off the main thread during load. Vanilla
            // JS, self-contained (reads its DOM root + data-endpoint at runtime).
            $newsChatJsPath = __DIR__ . '/public/js/news-chat.js';
            $newsChatJsVersion = file_exists($newsChatJsPath) ? (string) filemtime($newsChatJsPath) : $version;
            $theme
                ->asset()
                ->container('footer')
                ->usePath()
                ->add('news-chat-js', 'js/news-chat.js', [], [], $newsChatJsVersion);

            // Live search suggestions. Depends on newspaper-js, which defines
            // openSearch() and emits the event that primes this panel.
            $theme
                ->asset()
                ->container('footer')
                ->usePath()
                ->add('search-suggest-js', 'js/search-suggest.js', ['newspaper-js'], [], $searchSuggestJsVersion);

            // (article-tools-js moved to views/post.blade.php — article-only.)

            if (function_exists('shortcode')) {
                $theme->composer(
                    ['page', 'post', 'category', 'tag', 'gallery'],
                    function (View $view): void {
                        $view->withShortcodes();
                    }
                );
            }
        },

        'beforeRenderLayout' => [
            'default' => function (Theme $theme): void {
            },
        ],
    ],
];
