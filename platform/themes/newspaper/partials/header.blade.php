<!doctype html>
<html {!! Theme::htmlAttributes() !!}>
<head>
    @php
        use App\Models\AIReporter;
        use App\Services\AIReporterProfileService;
        use Botble\Author\Models\Author;
        use Botble\SeoHelper\Facades\SeoHelper;
        use Botble\Media\Facades\RvMedia;
        use Illuminate\Support\Facades\Route;
        use Illuminate\Support\Facades\Schema;
        use Illuminate\Support\Str;

        $siteTitle = theme_option('site_title', 'GenZ NewZ');
        $defaultDescription = 'GenZ NewZ delivers breaking news, trending stories, politics, culture, tech, and AI insights for young adults with fast daily updates and trusted reporting.';
        $currentUrl = url()->current();
        $pageNumber = max(1, (int) request()->input('page', 1));
        $isHomepage = $currentUrl === url('/');
        $isSearchPage = request()->routeIs('public.search');
        $iconVersion = file_exists(public_path('storage/logo-32x32.png'))
            ? (string) filemtime(public_path('storage/logo-32x32.png'))
            : get_cms_version();
        $faviconVersion = file_exists(public_path('favicon.ico'))
            ? (string) filemtime(public_path('favicon.ico'))
            : $iconVersion;
        $brandIconVersion = file_exists(public_path('brand/genznewz-icon.svg'))
            ? (string) filemtime(public_path('brand/genznewz-icon.svg'))
            : $iconVersion;

        // Beats plus their subcategories, so children live in the navigation instead
        // of being duplicated as page furniture on the category templates.
        $primaryNavigation = app(\App\Services\NavigationService::class)->getPrimaryNavigation();

        $computedTitle = null;
        $computedDescription = null;
        $canonicalUrl = $currentUrl;
        $twitterHandle = '@genznewz';
        $reporterProfileService = null;
        $openGraphImageWidth = null;
        $openGraphImageHeight = null;
        $openGraphImageAlt = $siteTitle;
        $defaultOgImageRelativePath = 'storage/og-image-1200x630.png';
        $defaultOgImagePath = public_path($defaultOgImageRelativePath);
        $defaultOgImageUrl = url($defaultOgImageRelativePath);
        $searchPageUrl = Route::has('public.search') ? route('public.search') : url('/search');
        $sanitizeSeoText = static function (?string $value, int $limit = 120): string {
            $value = html_entity_decode(strip_tags((string) $value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $value = str_replace('"', '', $value);
            $value = preg_replace('/\s+/', ' ', trim($value));

            return Str::limit($value, $limit, '');
        };

        if (! isset($reporter) && request()->routeIs('ai.reporter.profile')) {
            $profileSlug = (string) (request()->route('username') ?: request()->segment(2));
            $isHumanAuthorProfile = $profileSlug !== ''
                && Schema::hasTable('authors')
                && Schema::hasTable('slugs')
                && Author::query()
                ->whereHas('slugable', fn ($query) => $query->where('key', $profileSlug))
                ->exists();

            if (! $isHumanAuthorProfile && $profileSlug !== '') {
                $reporter = AIReporter::query()
                    ->where('username', $profileSlug)
                    ->where('status', 'active')
                    ->first();
            }
        }

        if (isset($reporter) && $reporter instanceof AIReporter) {
            $reporterProfileService = app(AIReporterProfileService::class);
        }

        $hasSingularCanonicalTarget = (isset($post) && $post) || (isset($page) && $page) || ($reporterProfileService && isset($reporter) && $reporter);

        if (! $isSearchPage && ! $hasSingularCanonicalTarget && $pageNumber > 1) {
            $canonicalUrl = $currentUrl . '?page=' . $pageNumber;
        }

        if (isset($post) && $post) {
            $computedTitle = $post->name . ' - ' . $siteTitle;
            $computedDescription = Str::limit(strip_tags($post->description ?: $post->content), 160);
        } elseif (isset($category) && $category) {
            $computedTitle = $category->name . ' - ' . $siteTitle;
            $computedDescription = 'Latest ' . $category->name . ' news, updates, and trending stories on ' . $siteTitle . '.';
        } elseif (isset($tag) && $tag) {
            $computedTitle = '#' . $tag->name . ' - ' . $siteTitle;
            $computedDescription = 'Explore #' . $tag->name . ' stories on ' . $siteTitle . '.';
        } elseif (isset($author) && $author) {
            $computedTitle = 'Articles by ' . $author->name . ' - ' . $siteTitle;
            $computedDescription = 'Read the latest stories by ' . $author->name . ' on ' . $siteTitle . '.';
        } elseif ($reporterProfileService && isset($reporter) && $reporter) {
            $computedTitle = $reporterProfileService->buildSeoTitle($reporter);
            $computedDescription = $reporterProfileService->buildProfileDescription($reporter);
        } elseif (isset($page) && $page) {
            $computedTitle = $page->name . ' - ' . $siteTitle;
            $computedDescription = Str::limit(strip_tags($page->description ?: $page->content), 160);
        } elseif (request()->routeIs('public.search')) {
            $query = $sanitizeSeoText((string) request()->input('q'), 80);
            $computedTitle = $query !== '' ? 'Search: ' . $query . ' - ' . $siteTitle : 'Search - ' . $siteTitle;
            $computedDescription = $query !== '' ? 'Search results for: ' . $query . ' on ' . $siteTitle . '.' : 'Search results on ' . $siteTitle . '.';
        } elseif (request()->routeIs('member.login')) {
            $computedTitle = 'Member Login - ' . $siteTitle;
            $computedDescription = 'Log in to your GenZ NewZ member account.';
        } elseif (request()->routeIs('member.register')) {
            $computedTitle = 'Create Your Member Account - ' . $siteTitle;
            $computedDescription = 'Register for a GenZ NewZ member account.';
        } elseif (request()->routeIs('ai.reporter.login')) {
            $computedTitle = 'AI Reporter Login - ' . $siteTitle;
            $computedDescription = 'Log in to the GenZ NewZ AI Reporter portal.';
        } elseif (request()->routeIs('ai.reporter.register')) {
            $computedTitle = 'AI Reporter Registration - ' . $siteTitle;
            $computedDescription = 'Register a new AI Reporter on GenZ NewZ.';
        } elseif (request()->routeIs('ai.reporter.welcome')) {
            $computedTitle = 'AI Reporter Welcome - ' . $siteTitle;
            $computedDescription = 'Complete onboarding for the GenZ NewZ AI Reporter program.';
        } elseif (request()->routeIs('ai.reporter.dashboard')) {
            $computedTitle = 'AI Reporter Dashboard - ' . $siteTitle;
            $computedDescription = 'Manage your GenZ NewZ AI Reporter account.';
        } elseif (request()->routeIs('upload.page')) {
            $computedTitle = 'Upload Files - ' . $siteTitle;
            $computedDescription = 'Submit files to GenZ NewZ.';
        } elseif (request()->is('todays-paper')) {
            $computedTitle = "Today's Paper - " . $siteTitle;
            $computedDescription = 'Read the complete daily edition on ' . $siteTitle . '.';
        } elseif ($isHomepage) {
            $computedTitle = 'Gen Z News | Breaking News, Tech & Culture for Gen Z';
            $computedDescription = $defaultDescription;
        }

        if ($pageNumber > 1 && $computedTitle) {
            $computedTitle .= ' - Page ' . $pageNumber;
        }

        $shouldForceSeoText = $isHomepage || $pageNumber > 1;

        if ($computedTitle && ($shouldForceSeoText || SeoHelper::getTitle() === $siteTitle || SeoHelper::getTitle() === '' || SeoHelper::getTitle() === null)) {
            SeoHelper::setTitle($computedTitle);
        }

        if ($computedDescription && ($shouldForceSeoText || SeoHelper::getDescription() === '' || SeoHelper::getDescription() === null)) {
            SeoHelper::setDescription($computedDescription);
        }

        $shouldNoindex = request()->routeIs(
            'public.search',
            'member.login',
            'member.register',
            'ai.reporter.landing',
            'ai.reporter.login',
            'ai.reporter.register',
            'ai.reporter.welcome',
            'ai.reporter.dashboard',
            'upload.page'
        );

        if (! $shouldNoindex && $reporterProfileService && isset($reporter) && $reporter) {
            $shouldNoindex = ! $reporterProfileService->shouldIndex($reporter);
        }

        $existingOpenGraphType = SeoHelper::openGraph()->getProperty('type');
        $openGraphType = isset($post) && $post ? 'article' : ($existingOpenGraphType ?: 'website');
        $openGraphTitle = SeoHelper::getTitle() ?: ($computedTitle ?: $siteTitle);
        $openGraphDescription = SeoHelper::getDescription() ?: ($computedDescription ?: $defaultDescription);
        $openGraphImage = SeoHelper::openGraph()->getProperty('image');
        $openGraphImageAlt = SeoHelper::getTitleOnly() ?: $openGraphTitle ?: $siteTitle;

        if (isset($post) && $post && $post->image) {
            $openGraphImage = RvMedia::getImageUrl($post->image, 'large');
            $openGraphImageAlt = $post->name;
        }

        $isLegacyOgImage = is_string($openGraphImage) && str_contains($openGraphImage, '/storage/400px100px');

        if (($isHomepage || ! $openGraphImage || $isLegacyOgImage) && file_exists($defaultOgImagePath)) {
            $openGraphImage = $defaultOgImageUrl;
            $openGraphImageWidth = 1200;
            $openGraphImageHeight = 630;
        }

        // Article hero: sized social card + LCP preload.
        //
        // This deliberately keys off the resolved og:type rather than a `$post`
        // variable. `$post` is not in scope inside this partial - the previous
        // `isset($post)` branch never ran, so articles advertised the full-size
        // Pexels original (300 KB+) as og:image with no width/height, and the
        // hero had no preload hint at all.
        $heroPreloadUrl = null;

        if ($openGraphType === 'article' && $openGraphImage && str_contains((string) $openGraphImage, '/storage/')) {
            $heroExtension = pathinfo((string) parse_url($openGraphImage, PHP_URL_PATH), PATHINFO_EXTENSION);

            // Only rewrite when the URL has no size suffix yet, so a value that is
            // already sized is never turned into "name-1024x683-560x380.webp".
            $heroIsUnsized = $heroExtension && ! preg_match('/-\d+x\d+\.[a-z0-9]+$/i', (string) $openGraphImage);

            if ($heroIsUnsized) {
                $heroBase = substr($openGraphImage, 0, -(strlen($heroExtension) + 1));

                foreach (['1024x683', '560x380', '540x360'] as $heroStep) {
                    $heroCandidate = "{$heroBase}-{$heroStep}.{$heroExtension}";
                    $heroCandidatePath = preg_replace(
                        '#^storage/#',
                        '',
                        ltrim((string) parse_url($heroCandidate, PHP_URL_PATH), '/')
                    );

                    if (! Storage::disk('public')->exists($heroCandidatePath)) {
                        continue;
                    }

                    // Give crawlers and social platforms a correctly sized image
                    // with real dimensions instead of the multi-hundred-KB original.
                    $openGraphImage = $heroCandidate;
                    $openGraphImageWidth = (int) explode('x', $heroStep)[0];
                    $openGraphImageHeight = (int) explode('x', $heroStep)[1];
                    $heroPreloadUrl = $heroCandidate;
                    break;
                }
            }
        }

        $is404Page = str_contains((string) (SeoHelper::getTitle() ?: $computedTitle ?: ''), '404 Error');

        SeoHelper::meta()->setUrl($canonicalUrl);
        $shouldNoindex = $shouldNoindex || $is404Page;

        // Directory directives. `max-image-preview:large` is what unlocks large
        // image previews (and Discover-style surfaces); without it Google falls
        // back to a small thumbnail. `max-snippet:-1` lets Google use as much
        // text as it needs for a result.
        SeoHelper::meta()->addMeta(
            'robots',
            $shouldNoindex
                ? 'noindex, follow'
                : 'index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1'
        );

        $openGraph = SeoHelper::openGraph()
            ->setType($openGraphType)
            ->setSiteName($siteTitle)
            ->setTitle($openGraphTitle)
            ->setDescription($openGraphDescription)
            ->setUrl($canonicalUrl);

        if ($openGraphImage) {
            $openGraph->setImage($openGraphImage);
            $openGraph->addProperty('image:alt', $openGraphImageAlt);
        }

        if ($openGraphImageWidth && $openGraphImageHeight) {
            $openGraph->addProperty('image:width', (string) $openGraphImageWidth);
            $openGraph->addProperty('image:height', (string) $openGraphImageHeight);
        }

        if (isset($post) && $post) {
            if ($post->created_at) {
                $openGraph->addProperty('article:published_time', $post->created_at->toIso8601String());
            }

            if ($post->updated_at) {
                $openGraph->addProperty('article:modified_time', $post->updated_at->toIso8601String());
            }

            if ($post->categories->first()) {
                $openGraph->addProperty('article:section', $post->categories->first()->name);
            }

            if ($post->tags->isNotEmpty()) {
                $openGraph->addProperty('article:tag', $post->tags->pluck('name')->implode(', '));
            }
        }

        $twitterCardType = $openGraphImage ? 'summary_large_image' : 'summary';
        $twitter = SeoHelper::twitter()
            ->setType($twitterCardType)
            ->setSite($twitterHandle)
            ->setTitle($openGraphTitle)
            ->setDescription($openGraphDescription);

        if ($openGraphImage) {
            $twitter->addImage($openGraphImage);
            $twitter->addMeta('image:alt', $openGraphImageAlt);
        }
    @endphp

    <!-- Critical: Charset must be within first 1024 bytes -->
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=5">
    
    <!-- SEO Core Meta Tags are rendered via Theme::header() -->
    
    <!-- SEO: Author & Publisher -->
    <meta name="author" content="GenZ NewZ">
    <meta name="publisher" content="GenZ NewZ">
    <meta name="language" content="English">
    @if ($googleSiteVerification = env('GOOGLE_SITE_VERIFICATION'))
    <meta name="google-site-verification" content="{{ $googleSiteVerification }}">
    @endif
    <meta name="yandex-verification" content="2a1ed41925e91ff7">
    
    <!-- FAVICONS - Multi-platform support -->
    <link rel="icon" type="image/svg+xml" href="{{ url('brand/genznewz-icon.svg') }}?v={{ $brandIconVersion }}">
    <link rel="shortcut icon" href="{{ url('favicon.ico') }}?v={{ $faviconVersion }}">
    <!-- Standard favicon -->
    <link rel="icon" type="image/png" sizes="16x16" href="{{ url('storage/logo-16x16.png') }}?v={{ $iconVersion }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ url('storage/logo-32x32.png') }}?v={{ $iconVersion }}">
    <link rel="icon" type="image/png" sizes="96x96" href="{{ url('storage/logo-96x96.png') }}?v={{ $iconVersion }}">
    
    <!-- Apple Touch Icons -->
    <link rel="apple-touch-icon" sizes="57x57" href="{{ url('storage/logo-57x57.png') }}?v={{ $iconVersion }}">
    <link rel="apple-touch-icon" sizes="60x60" href="{{ url('storage/logo-60x60.png') }}?v={{ $iconVersion }}">
    <link rel="apple-touch-icon" sizes="72x72" href="{{ url('storage/logo-72x72.png') }}?v={{ $iconVersion }}">
    <link rel="apple-touch-icon" sizes="76x76" href="{{ url('storage/logo-76x76.png') }}?v={{ $iconVersion }}">
    <link rel="apple-touch-icon" sizes="114x114" href="{{ url('storage/logo-114x114.png') }}?v={{ $iconVersion }}">
    <link rel="apple-touch-icon" sizes="120x120" href="{{ url('storage/logo-120x120.png') }}?v={{ $iconVersion }}">
    <link rel="apple-touch-icon" sizes="144x144" href="{{ url('storage/logo-144x144.png') }}?v={{ $iconVersion }}">
    <link rel="apple-touch-icon" sizes="152x152" href="{{ url('storage/logo-152x152.png') }}?v={{ $iconVersion }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ url('storage/logo-180x180.png') }}?v={{ $iconVersion }}">
    
    <!-- Android/Chrome Icons -->
    <link rel="icon" type="image/png" sizes="192x192" href="{{ url('storage/logo-192x192.png') }}?v={{ $iconVersion }}">
    <link rel="icon" type="image/png" sizes="512x512" href="{{ url('storage/logo-512x512.png') }}?v={{ $iconVersion }}">
    
    <!-- Web App Manifest -->
    <link rel="manifest" href="{{ url('manifest.json') }}">

    @php
        // Feed autodiscovery. Category feeds are derived from the topic URL
        // pattern (/topic/{slug}) so this works regardless of which variables
        // the calling view happens to share with the header partial.
        $feedTopicSlug = null;
        $feedPathSegments = explode('/', trim(request()->path(), '/'));

        if (count($feedPathSegments) === 2 && $feedPathSegments[0] === 'topic' && $feedPathSegments[1] !== '') {
            $feedTopicSlug = $feedPathSegments[1];
        }
    @endphp
    <link rel="alternate" type="application/rss+xml" title="{{ theme_option('site_title', 'GenZ NewZ') }} — Latest stories" href="{{ url('feed') }}">
    <link rel="alternate" type="application/feed+json" title="{{ theme_option('site_title', 'GenZ NewZ') }} — Latest stories (JSON Feed)" href="{{ url('feed.json') }}">
    @if ($feedTopicSlug)
    <link rel="alternate" type="application/rss+xml" title="{{ $feedTopicSlug }} — RSS feed" href="{{ url('feed/' . $feedTopicSlug) }}">
    <link rel="alternate" type="application/feed+json" title="{{ $feedTopicSlug }} — JSON feed" href="{{ url('feed/' . $feedTopicSlug . '.json') }}">
    @endif
    
    <!-- Safari Web App -->
    <meta name="apple-mobile-web-app-title" content="GenZ NewZ">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    
    <!-- SEO: Theme Color for Mobile Browsers -->
    <meta name="theme-color" content="#121212">
    <meta name="msapplication-TileColor" content="#121212">
    <meta name="msapplication-TileImage" content="{{ url('storage/logo-144x144.png') }}?v={{ $iconVersion }}">
    
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    @php
        // Fonts are self-hosted (css/fonts.css, registered in config.php ahead of
        // newspaper.css), so there is no third-party font connection at all — no
        // preconnect to fonts.googleapis.com/gstatic.com, no extra DNS+TLS round
        // trip before the CSS and font files can start downloading.
        //
        // Preload only the faces that are above the fold on every page: the body
        // text face, the headline face, and the logo marquee face. `crossorigin`
        // is required on font preloads even for same-origin resources.
        //
        // Version from the SOURCE-tree mtime (platform_path), matching
        // css/fonts.css which is baked from the same files. NOTE: __DIR__ is
        // unusable here — compiled Blade views execute from storage/framework/
        // views, and public_path() (the deployed copy) yields a different mtime
        // than fonts.css, breaking the preload/dedup match. If font files
        // change, re-bake fonts.css URLs (see DEPLOY_CHECKLIST.md).
        $fontVersion = file_exists(platform_path('themes/newspaper/public/fonts/inter-400.woff2'))
            ? (string) filemtime(platform_path('themes/newspaper/public/fonts/inter-400.woff2'))
            : get_cms_version();
    @endphp
    <link rel="preload" href="{{ url('themes/newspaper/public/fonts/inter-400.woff2') }}?v={{ $fontVersion }}" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="{{ url('themes/newspaper/public/fonts/space-grotesk-600.woff2') }}?v={{ $fontVersion }}" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="{{ url('themes/newspaper/public/fonts/bebas-neue-400.woff2') }}?v={{ $fontVersion }}" as="font" type="font/woff2" crossorigin>

    @if (!empty($heroPreloadUrl))
        <link rel="preload" as="image" href="{{ $heroPreloadUrl }}" fetchpriority="high">
    @endif

    <style>
        :root {
            --color-1st: {{ theme_option('primary_color', '#121212') }};
            --primary-color: {{ theme_option('primary_color', '#121212') }};
            --primary-font: 'Inter', sans-serif;
            --heading-font: 'Space Grotesk', sans-serif;
            --news-ink: #111827;
            --news-border: #d9dee8;
            --news-border-strong: #b8c1d1;
            --news-accent: #2e5cff;
            --news-accent-soft: #edf2ff;
            --news-warm: #ffb703;
        }
    </style>

    {{-- Static styles moved to css/theme-header.css and css/theme-dark.css (registered in config.php, preserving cascade order). --}}

    {!! Theme::header() !!}
    @stack('header')
    
    {{-- Schema.org Structured Data for SEO --}}
    @include('theme::partials.schema')
    
</head>

<body {!! Theme::bodyAttributes() !!}>
<a class="skip-link" href="#main-content">Skip to main content</a>
{!! apply_filters(THEME_FRONT_BODY, null) !!}

<!-- Mobile Menu -->
<div class="mobile-menu-overlay" onclick="closeMobileMenu()" aria-hidden="true" inert></div>
<nav class="mobile-menu" id="mobileMenu" aria-label="Mobile navigation" aria-hidden="true" inert>
    <div class="mobile-menu-header">
        <span style="font-family: 'Space Grotesk', sans-serif; font-weight: 700;">Menu</span>
        <button type="button" class="mobile-menu-close" onclick="closeMobileMenu()" aria-label="Close menu">&times;</button>
    </div>
    <ul class="mobile-menu-list">
        {{-- Beats come from the same source as the desktop nav so the two menus
             cannot drift apart. A beat with children becomes an accordion and
             keeps its own page reachable via the "All …" entry. --}}
        @foreach ($primaryNavigation as $navItem)
            @if ($navItem['children'] === [])
                <li><a href="{{ $navItem['url'] }}">{{ $navItem['label'] }}</a></li>
            @else
                <li class="mobile-menu-item-has-children">
                    <details class="mobile-menu-group">
                        <summary class="mobile-menu-group-summary">
                            <span>{{ $navItem['label'] }}</span>
                            <span class="mobile-menu-group-caret" aria-hidden="true">&#9662;</span>
                        </summary>
                        <ul class="mobile-menu-submenu">
                            <li><a href="{{ $navItem['url'] }}">All {{ $navItem['label'] }}</a></li>
                            @foreach ($navItem['children'] as $navChild)
                                <li><a href="{{ $navChild['url'] }}">{{ $navChild['label'] }}</a></li>
                            @endforeach
                        </ul>
                    </details>
                </li>
            @endif
        @endforeach
        <li><a href="{{ url('/todays-paper') }}">Today's Paper</a></li>
        <li><a href="{{ url('/live-world-events') }}">Live World Events</a></li>
        {{-- The AI Reporter portal is linked from the agent hub at the foot of every
             page, so it is deliberately kept out of reader navigation. --}}
        <li><a href="{{ $searchPageUrl }}">Search</a></li>
        @php
            $mobileMember = Auth::guard('member')->user();
            $mobileUser = Auth::guard()->user();
        @endphp
        @if (!$mobileMember && !$mobileUser)
            <li style="border-top: 0.5px solid #e2e2e2;
            padding: 4px 20px; margin-top: 10px; padding-top: 10px;"></li>
            <li><a href="{{ url('/login') }}">Log In</a></li>
            <li><a href="{{ url('/register') }}">Sign Up</a></li>
        @endif
    </ul>
</nav>

<!-- Search Overlay -->
<div class="search-overlay" id="searchOverlay" aria-hidden="true" inert>
    <button type="button" class="search-close" onclick="closeSearch()" aria-label="Close search">&times;</button>
    <div class="search-container">
        <form action="{{ $searchPageUrl }}" method="GET" role="search" autocomplete="off">
            <label class="sr-only" for="headerSearchInput">Search articles</label>
            <input
                type="text"
                name="q"
                id="headerSearchInput"
                class="search-input"
                placeholder="Search stories, topics, people..."
                autofocus
                autocomplete="off"
                spellcheck="false"
            >
        </form>

        {{-- Live suggestions; filled by search-suggest.js. --}}
        <div
            class="search-suggest"
            id="searchSuggest"
            data-suggest-endpoint="{{ url('api/search/suggest') }}"
            hidden
        >
            <div class="search-suggest-list" id="searchSuggestList" data-suggest-list role="listbox" aria-label="Search suggestions"></div>
            <p class="sr-only" role="status" aria-live="polite" data-suggest-status></p>
        </div>
    </div>
</div>

{{-- Saved stories drawer; populated from localStorage by article-tools.js. --}}
<aside class="saved-drawer" id="savedDrawer" aria-label="Saved stories" hidden>
    <div class="saved-drawer-head">
        <h2 class="saved-drawer-title">Saved stories</h2>
        <button type="button" class="saved-drawer-close" data-saved-close aria-label="Close saved stories">&times;</button>
    </div>
    <p class="saved-drawer-empty" data-saved-empty>Nothing saved yet. Look for the <strong>Save</strong> button on any story.</p>
    <ul class="saved-list" data-saved-list></ul>
</aside>

<!-- Header -->
<header class="nyt-header">
    <!-- Top Bar -->
    <div class="header-top-bar">
        <span class="header-date">{{ now()->format('l, F j, Y') }}</span>
        <div class="header-tools">
            <button type="button" class="saved-toggle" data-saved-toggle aria-expanded="false" aria-controls="savedDrawer" title="Saved stories">
                <span aria-hidden="true">🔖</span>
                <span class="saved-toggle-label">Saved</span>
                <span class="saved-count" data-saved-count hidden></span>
            </button>
            <a href="{{ url('/todays-paper') }}">Today's Paper</a>
            <button type="button" class="dark-mode-toggle" id="darkModeToggle" title="Toggle Dark Mode" aria-label="Toggle dark mode" aria-pressed="false">
                <span class="dark-mode-icon">🌙</span>
            </button>
        </div>
    </div>
    
    <!-- Main Header -->
    <div class="header-main">
        <div class="header-left">
            <!-- Menu and Search moved to nav bar -->
        </div>
        
        <!-- Centered Logo -->
        <div class="logo-center">
            <a href="{{ url('/') }}" class="genz-logo">
                <div class="gzn-logo" aria-label="{{ theme_option('site_title', 'GenZ NewZ') }}">
                    <div class="gzn-left">
                        <div class="gzn-gen">
                            <div class="gzn-curve"></div>
                            <span class="gzn-gen-text">GEN</span>
                        </div>
                        <div class="gzn-newz-bar">
                            <div class="gzn-marquee" aria-hidden="true">
                                <span class="gzn-newz-outline">NEWZ</span>
                                <span class="gzn-newz-solid">NEWZ</span>
                                <span class="gzn-newz-outline">NEWZ</span>
                                <span class="gzn-newz-solid">NEWZ</span>
                                <span class="gzn-newz-outline">NEWZ</span>
                                <span class="gzn-newz-solid">NEWZ</span>
                            </div>
                        </div>
                    </div>
                    <div class="gzn-right">
                        <span class="gzn-z-text">Z</span>
                    </div>
                </div>
            </a>
        </div>
        
        <div class="header-right">
            @php
                $member = Auth::guard('member')->user();
                $user = Auth::guard()->user();
            @endphp
            
            @if ($member)
                <div class="user-menu">
                    <img src="{{ $member->avatar_url }}" alt="{{ $member->name }}" class="user-avatar">
                    <span class="user-name">{{ $member->name }}</span>
                    <form action="{{ route('member.logout') }}" method="POST" class="logout-form">
                        @csrf
                        <button type="submit" class="logout-btn">Logout</button>
                    </form>
                </div>
            @elseif ($user)
                <span class="admin-badge">Admin</span>
            @else
                <a href="{{ url('/login') }}" class="login-link">Log In</a>
                <a href="{{ url('/register') }}" class="register-link">Sign Up</a>
            @endif

        </div>
    </div>
    
    <!-- Navigation with Menu and Search -->
    <nav class="header-nav">
    <div class="header-nav-container">
        <button type="button" class="nav-menu-toggle" onclick="openMobileMenu()" aria-label="Open menu" aria-controls="mobileMenu">
            <span></span>
            <span></span>
            <span></span>
        </button>
        <ul class="nav-menu">
            @foreach ($primaryNavigation as $navItem)
                <li @class(['nav-menu-item', 'nav-menu-item-has-children' => $navItem['children'] !== []])>
                    <a href="{{ $navItem['url'] }}">{{ $navItem['label'] }}</a>
                    @if ($navItem['children'] !== [])
                        <ul class="nav-submenu">
                            @foreach ($navItem['children'] as $navChild)
                                <li><a href="{{ $navChild['url'] }}">{{ $navChild['label'] }}</a></li>
                            @endforeach
                        </ul>
                    @endif
                </li>
            @endforeach
            </ul>
        <button type="button" class="nav-search-btn" onclick="openSearch()" aria-label="Open search" aria-controls="searchOverlay">🔍</button>
    </div>
</nav>
</header>


<!-- Google Tag Manager - Enhanced Data Layer Events -->
<script>
    window.dataLayer = window.dataLayer || [];

    // Track article clicks
    document.addEventListener('DOMContentLoaded', function() {
        // Track all link clicks on articles
        document.querySelectorAll('article a, .news-card a, .post-title a').forEach(function(link) {
            link.addEventListener('click', function(e) {
                var articleTitle = this.closest('article')?.querySelector('h2, h3, .post-title, .news-card-title')?.textContent?.trim() || 'Unknown Article';
                dataLayer.push({
                    'event': 'article_click',
                    'article_title': articleTitle,
                    'article_url': this.href,
                    'click_location': window.location.pathname
                });
            });
        });
        
        // Track category navigation
        document.querySelectorAll('.sidebar-menu a, .nav-menu a, .nav-submenu a, .mobile-menu-list a').forEach(function(link) {
            link.addEventListener('click', function(e) {
                dataLayer.push({
                    'event': 'category_click',
                    'category_name': this.textContent.trim(),
                    'category_url': this.href
                });
            });
        });
        
        // Track search
        var searchForm = document.querySelector('form[action*="search"]');
        if (searchForm) {
            searchForm.addEventListener('submit', function(e) {
                var searchQuery = this.querySelector('input[name="q"]')?.value;
                if (searchQuery) {
                    dataLayer.push({
                        'event': 'search',
                        'search_term': searchQuery
                    });
                }
            });
        }
        
        // Track login/register clicks
        document.querySelectorAll('a[href*="/login"], a[href*="/register"]').forEach(function(link) {
            link.addEventListener('click', function(e) {
                dataLayer.push({
                    'event': 'auth_click',
                    'auth_action': this.href.includes('/login') ? 'login' : 'register'
                });
            });
        });
        
        // Track dark mode toggle
        var darkModeToggle = document.getElementById('darkModeToggle');
        if (darkModeToggle) {
            darkModeToggle.addEventListener('click', function(e) {
                var isDarkMode = document.body.classList.contains('dark-mode');
                dataLayer.push({
                    'event': 'dark_mode_toggle',
                    'dark_mode_enabled': isDarkMode
                });
            });
        }
    });
</script>
<!-- End GTM Events -->
