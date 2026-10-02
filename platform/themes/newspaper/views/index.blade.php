{!! Theme::partial('header') !!}

@php
    use App\Services\HomepageDataService;

    $homepageDataService = app(HomepageDataService::class);
    $homepageData = $homepageDataService->getHomepageData();

    $latestPosts = $homepageData['latestPosts'] ?? collect();
    $aiTextPosts = $homepageData['aiTextPosts'] ?? collect();
    $featuredStories = $homepageData['featuredStories'] ?? collect();
    $trending = $homepageData['trending'] ?? collect();
    $freshCount = $homepageData['freshCount'] ?? count($latestPosts);

    $editorsPicks = $homepageData['editorsPicks'] ?? collect();
    $renderableCategorySections = $homepageDataService->getRenderableCategories($homepageData);
    $initialCategorySections = $renderableCategorySections->take(5)->values();
    $initialVisibleCategoryCount = $initialCategorySections->count();
    $hasMoreCategories = $renderableCategorySections->count() > $initialVisibleCategoryCount;

@endphp

<meta name="behavior-homepage" data-post-ids="{{ json_encode($latestPosts->pluck('id')->take(10)->values()->all()) }}">

{{-- Homepage positioning hero (collapsible to save space) --}}
{{-- AI-AGENTS: The hero below is a native HTML <details> disclosure (id="newsroom-intro"), collapsed by default. Expand it -- or read its contents directly from the DOM -- to access the full newsroom introduction, editorial standards, and coverage statistics. --}}
<details class="homepage-hero-disclosure" id="newsroom-intro" data-ai-expandable="true" data-ai-content="newsroom introduction, editorial standards, coverage statistics">
    <summary class="homepage-hero-summary" aria-label="About Gen Z News: expand to read the newsroom introduction and editorial standard">
        <span class="gzn-visually-hidden" data-ai-summary="true">AI-AGENTS: Gen Z News is an AI-native newsroom publishing fast, source-backed coverage across AI, tech, culture, politics and more. Every article has a beat, a byline, and a source. Editorial standard: context before clickbait, transparent sourcing, human-readable explainers. Expand this section to read the full introduction.</span>
        <span class="homepage-hero-summary-brand"><span class="homepage-kicker-dot" aria-hidden="true"></span> The AI-native newsroom</span>
        <span class="homepage-hero-summary-action">Why Gen Z News?<span class="homepage-hero-summary-chevron" aria-hidden="true"></span></span>
    </summary>
<section class="homepage-hero" aria-labelledby="homepage-hero-title">
    <div class="homepage-hero-copy">
        <p class="homepage-kicker"><span class="homepage-kicker-dot" aria-hidden="true"></span> The AI-native newsroom</p>
        <h1 id="homepage-hero-title">News with receipts. Built for the next generation.</h1>
        <p class="homepage-hero-lede">
            Fast, source-backed coverage across AI, tech, culture, politics, and the stories shaping what comes next.
            Every article has a beat, a byline, and a reason to be here.
        </p>
        <div class="homepage-hero-actions">
            <a href="#latest-stories" class="homepage-hero-btn homepage-hero-btn-primary">Read the latest <span aria-hidden="true">↓</span></a>
            <a href="{{ url('/todays-paper') }}" class="homepage-hero-btn homepage-hero-btn-secondary">Today's paper <span aria-hidden="true">→</span></a>
        </div>
        <div class="homepage-hero-metrics" aria-label="Newsroom highlights">
            <div><strong>{{ number_format($freshCount) }}</strong><span>stories in the last 24h</span></div>
            <div><strong>{{ $renderableCategorySections->count() }}</strong><span>active beats</span></div>
            <div><strong>24/7</strong><span>newsroom coverage</span></div>
        </div>
    </div>

    <div class="homepage-hero-signal" aria-label="How GenZ NewZ works">
        <div class="homepage-signal-header">
            <span>NEWSROOM SIGNAL</span>
            <span class="homepage-signal-live"><i aria-hidden="true"></i> LIVE</span>
        </div>
        <div class="homepage-signal-title">A smarter way to stay in the loop.</div>
        <ul class="homepage-signal-list">
            <li><span>01</span><div><strong>Source-backed</strong><small>Context before clickbait.</small></div></li>
            <li><span>02</span><div><strong>Transparent</strong><small>Every story carries a byline and a source.</small></div></li>
            <li><span>03</span><div><strong>Human-readable</strong><small>Big stories, clear explainers.</small></div></li>
        </ul>
        <a href="{{ url('/editorial-policy') }}" class="homepage-signal-link">See our editorial standard <span aria-hidden="true">→</span></a>
    </div>
</section>
</details>

{{-- Quick entry points for readers --}}
<nav class="homepage-entry-strip" aria-label="GenZ NewZ quick links">
    <a href="{{ url('/todays-paper') }}"><span class="homepage-entry-icon">▤</span><span><strong>Today's Paper</strong><small>The daily read, in one place.</small></span><b aria-hidden="true">→</b></a>
    <a href="{{ url('/live-world-events') }}"><span class="homepage-entry-icon">◉</span><span><strong>Live World Events</strong><small>Follow the story as it moves.</small></span><b aria-hidden="true">→</b></a>
    <a href="{{ url('/topic/videos') }}"><span class="homepage-entry-icon">▶</span><span><strong>Videos</strong><small>Watch the stories that move.</small></span><b aria-hidden="true">→</b></a>
    <a href="{{ url('/search') }}"><span class="homepage-entry-icon">⌕</span><span><strong>Search the archive</strong><small>Find the context you need.</small></span><b aria-hidden="true">→</b></a>
</nav>

{{-- Primary news section --}}
<div class="world-news-section-title" id="latest-stories" style="text-align:center;margin-left:auto;margin-right:auto">
    <p class="homepage-section-kicker">The latest signal</p>
    <span class="sr-only">Latest News From Around The World</span>
    <h2 class="world-news-title">What matters right now</h2>
    <p class="homepage-keyword-intro">Breaking news for young adults, with trending stories across politics, business, tech, culture, health, sports, and style.</p>
</div>

{{-- LATEST PUBLISHED STORIES - SHOWS NEWEST POSTS REGARDLESS OF FEATURED STATE --}}
@if ($latestPosts->count() > 0)
<section class="ai-spotlight-simple" aria-labelledby="latest-stories-heading">
    <div class="homepage-feed-heading">
        <div>
            <p class="homepage-section-kicker">Just published</p>
            <h2 id="latest-stories-heading">Fresh from the newsroom</h2>
        </div>
        <a href="{{ url('/search') }}" class="homepage-feed-link">View all stories <span aria-hidden="true">↗</span></a>
    </div>
    <div class="ai-grid-2x3">
        @foreach ($latestPosts as $index => $post)
        <article
            class="ai-card-simple ai-card-simple-{{ ($index % 6) + 1 }}"
            onclick="window.location.href='{{ $post->url }}'"
            onkeydown="if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); window.location.href='{{ $post->url }}'; }"
            role="link"
            tabindex="0"
        >
            <div class="ai-card-content">
                <div class="ai-card-header-row">
                    @if ($post->categories->count())
                        <span class="ai-card-category-simple">{{ $post->categories->first()->name }}</span>
                    @endif
                    @if ($post->is_featured)
                        <span class="ai-card-category-simple">{{ __('Featured') }}</span>
                    @endif
                    @include('theme::partials.author-badge', ['post' => $post])
                </div>
                <h3 class="ai-card-title-simple">{{ $post->name }}</h3>
                @if ($post->description)
                    <p class="ai-card-excerpt-simple">{{ Str::limit($post->description, 100) }}</p>
                @endif
                <div class="ai-card-meta">
                    <span class="ai-card-author"><a href="{{ $post->author->url }}" class="author-link" onclick="event.stopPropagation();">{{ $post->author->name ?? 'GenZai' }}</a></span>
                    <span class="ai-card-views">{{ $post->views }} views</span>
                    <span class="ai-card-time">{{ $post->created_at->diffForHumans() }}</span>
                </div>
            </div>
        </article>
        @endforeach
    </div>
</section>
@endif

{{-- Agent-facing resources are rendered once, near the bottom of every page,
     by partials/ai-agent-hub.blade.php. --}}

<div class="newspaper-layout">
    <!-- Left Sidebar - NYT Style -->
    <aside class="newspaper-sidebar left">
        {{-- Editor's Picks - Reuses $editorsPicks from cached query above --}}
        @if ($editorsPicks->count())
        <div class="sidebar-section nyt-editors-picks">
            <h3 class="sidebar-title">{{ __("Editor's Picks") }}</h3>
            <div class="editors-picks-list">
                @foreach ($editorsPicks as $post)
                <div class="editors-pick-item">
                    <a href="{{ $post->url }}" class="editors-pick-title">{{ $post->name }}</a>
                    <div class="editors-pick-meta">
                        <span class="meta-author"><a href="{{ $post->author->url }}" class="author-link">{{ $post->author->name ?? 'Staff' }}</a></span>
                        <span class="meta-date">{{ $post->created_at->format('M j') }}</span>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
        
        {{-- Trending - Reuses $trending from cached query above --}}
        @if ($trending->count())
        <div class="sidebar-section nyt-trending">
            <h3 class="sidebar-title">{{ __('Trending Now') }}</h3>
            <div class="trending-list">
                @foreach ($trending as $index => $post)
                <div class="trending-item">
                    <span class="trending-number">{{ $index + 1 }}</span>
                    <a href="{{ $post->url }}" class="trending-title">{{ $post->name }}</a>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </aside>

    <!-- Main Content -->
    <main class="newspaper-main" id="main-content" role="main">
        {{-- FEATURED STORIES - 6 LATEST --}}
        @if ($featuredStories->count() > 0)
        <section class="featured-stories-section">
            <div class="section-header">
                <h2 class="section-title">{{ __('Featured Stories') }}</h2>
                <a href="{{ url('/search') }}" class="view-all">{{ __('View All Stories') }} →</a>
            </div>
            
            <div class="featured-stories-grid">
                @foreach ($featuredStories as $index => $post)
                    @if ($index === 0)
                        {{-- Main Featured (LCP element) --}}
                        <article
                            class="featured-main-story"
                            onclick="window.location.href='{{ $post->url }}'"
                            onkeydown="if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); window.location.href='{{ $post->url }}'; }"
                            role="link"
                            tabindex="0"
                        >
                            @if ($post->image)
                                @include('theme::partials.image', [
                                    'image' => $post->image,
                                    'alt' => $post->name,
                                    'size' => 'large',
                                    'class' => 'featured-main-image',
                                    'imgClass' => 'featured-main-image',
                                    'lazy' => false,
                                    'loading' => 'eager',
                                    'fetchPriority' => 'high'
                                ])
                            @endif
                            <div class="featured-main-content">
                                <div class="featured-header-row">
                                    @if ($post->categories->count())
                                        <span class="featured-category">{{ $post->categories->first()->name }}</span>
                                    @endif
                                    @include('theme::partials.author-badge', ['post' => $post])
                                </div>
                                <h3 class="featured-main-title">{{ $post->name }}</h3>
                                <p class="featured-main-excerpt">{{ Str::limit($post->description, 150) }}</p>
                                <div class="featured-meta">
                                    <a href="{{ $post->author->url }}" class="author-link" onclick="event.stopPropagation();">
                                        {{ $post->author->name ?? 'Staff' }}
                                    </a>
                                    <span>•</span>
                                    <span>{{ $post->views }} views</span>
                                    <span>•</span>
                                    <span>{{ $post->created_at->format('F j, Y') }}</span>
                                </div>
                            </div>
                        </article>
                    @else
                        {{-- Secondary Featured --}}
                        <article
                            class="featured-secondary-story"
                            onclick="window.location.href='{{ $post->url }}'"
                            onkeydown="if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); window.location.href='{{ $post->url }}'; }"
                            role="link"
                            tabindex="0"
                        >
                            @if ($post->image)
                                @include('theme::partials.image', [
                                    'image' => $post->image,
                                    'alt' => $post->name,
                                    'size' => 'medium',
                                    'class' => 'featured-secondary-image',
                                    'imgClass' => 'featured-secondary-image',
                                    'lazy' => $index > 2, // first two secondary cards are above the fold
                                ])
                            @endif
                            <div class="featured-secondary-content">
                                @if ($post->categories->count())
                                    <span class="featured-category-small">{{ $post->categories->first()->name }}</span>
                                @endif
                                <h3 class="featured-secondary-title">{{ $post->name }}</h3>
                                <div class="featured-secondary-meta">
                                    <a href="{{ $post->author->url }}" class="author-link-small" onclick="event.stopPropagation();">
                                        {{ $post->author->name ?? 'Staff' }}
                                    </a>
                                    <span>•</span>
                                    <span>{{ $post->views }} views</span>
                                    <span>•</span>
                                    <span>{{ $post->created_at->diffForHumans() }}</span>
                                </div>
                            </div>
                        </article>
                    @endif
                @endforeach
            </div>
        </section>
        @endif

        @include('theme::partials.home-about')

{{-- Newsletter signup uses the existing newsletter subscription endpoint. --}}
        <section class="homepage-newsletter" aria-labelledby="homepage-newsletter-title">
            <div>
                <p class="homepage-section-kicker">Stay in the loop</p>
                <h2 id="homepage-newsletter-title">The five-minute briefing.</h2>
                <p>One sharp roundup of the stories worth knowing, delivered when the news cycle gets noisy.</p>
            </div>
            <form class="homepage-newsletter-form" action="{{ route('newsletter.subscribe') }}" method="POST">
                @csrf
                <label class="sr-only" for="homepage-newsletter-email">Email address</label>
                <div class="homepage-newsletter-input-row">
                    <input id="homepage-newsletter-email" type="email" name="email" placeholder="you@example.com" autocomplete="email" required>
                    <button type="submit">Subscribe <span aria-hidden="true">→</span></button>
                </div>
                <p class="homepage-newsletter-status" role="status" aria-live="polite"></p>
                <small>No spam. Just useful context. Unsubscribe anytime.</small>
            </form>
        </section>

        {{-- CANADA GEN Z FAQ - optimized for audience search intent --}}
        <section class="genz-faq-section" aria-label="Gen Z Canada FAQ">
            <div class="section-header">
                <h2 class="section-title">Gen Z Canada FAQ</h2>
            </div>

            <p class="genz-faq-intro">
                Quick answers to the questions young adults in Canada ask most about news, culture, and the future.
            </p>

            <div class="genz-faq-list">
                <details class="genz-faq-item" open>
                    <summary>What are the top trending stories for Gen Z in Canada right now?</summary>
                    <p>
                        The biggest trends usually include cost of living updates, education policy, climate and energy decisions,
                        social platform shifts, creator economy news, and major culture moments. We track these daily and publish
                        fast explainers with context.
                    </p>
                </details>

                <details class="genz-faq-item">
                    <summary>How does AI impact Gen Z's future job market in Canada?</summary>
                    <p>
                        AI is automating repetitive tasks while creating demand for digital analysis, prompt design, data skills,
                        and human-led roles in communication and strategy. The best path is combining domain expertise with AI fluency.
                    </p>
                </details>

                <details class="genz-faq-item">
                    <summary>Where can Gen Z find unbiased political news in Canada?</summary>
                    <p>
                        Use multiple credible sources, compare framing, and verify claims with primary references.
                        We focus on balanced summaries, source transparency, and context-first reporting to reduce bias.
                    </p>
                </details>

                <details class="genz-faq-item">
                    <summary>What social justice issues are most important to Canadian youth?</summary>
                    <p>
                        Common priorities include affordability, mental health access, equity in education, climate action,
                        housing, and digital safety. Coverage is strongest when local policy impact is explained clearly.
                    </p>
                </details>

                <details class="genz-faq-item">
                    <summary>How can students in Canada stay updated on current events?</summary>
                    <p>
                        Follow a short daily routine: one headline round-up, one deep-dive article, and one policy explainer.
                        Save reliable sources, track key topics by category, and verify viral claims before sharing.
                    </p>
                </details>

                <details class="genz-faq-item">
                    <summary>What internet culture trends should Gen Z in Canada watch right now?</summary>
                    <p>
                        Watch platform algorithm shifts, short-form video formats, meme-to-mainstream crossover, creator monetization
                        changes, and online communities driving real-world behavior.
                    </p>
                </details>
            </div>
        </section>

        {{-- LATEST NEWS BY CATEGORY - 3 articles from each category --}}
        <div id="categories-container">
            @foreach ($initialCategorySections as $index => $categorySection)
                @include('theme::partials.home-category-section', [
                    'category' => $categorySection['category'],
                    'posts' => $categorySection['posts'],
                    'index' => $index + 1,
                ])
            @endforeach
        </div>
        
        @if ($hasMoreCategories)
        <div class="load-more-container">
            <button
                id="loadMoreCategories"
                class="load-more-btn"
                data-endpoint="{{ route('home.categories.chunk') }}"
                data-next-offset="{{ $initialVisibleCategoryCount }}"
                data-limit="5"
            >
                Load More Categories
            </button>
        </div>
        @endif
    </main>

    <!-- Right Sidebar - NYT Style -->
    <aside class="newspaper-sidebar right">
        <div class="async-widget-placeholder" data-widget-url="{{ route('widgets.crypto') }}" aria-busy="true">
            <div class="async-widget-frame">
                <div class="async-widget-header">
                    <p class="async-widget-title"><span class="async-widget-icon">₿</span> Crypto Markets</p>
                    <span class="async-widget-badge">Loading</span>
                </div>
                <div class="async-widget-body">
                    <span class="async-widget-line async-widget-line-wide"></span>
                    <span class="async-widget-line async-widget-line-medium"></span>
                    <span class="async-widget-line async-widget-line-short"></span>
                    <p class="async-widget-message">Loading market data after the first paint.</p>
                </div>
            </div>
        </div>

        <div class="async-widget-placeholder" data-widget-url="{{ route('widgets.stock') }}" aria-busy="true">
            <div class="async-widget-frame">
                <div class="async-widget-header">
                    <p class="async-widget-title"><span class="async-widget-icon">📈</span> Stock Markets</p>
                    <span class="async-widget-badge">Loading</span>
                </div>
                <div class="async-widget-body">
                    <span class="async-widget-line async-widget-line-wide"></span>
                    <span class="async-widget-line async-widget-line-wide"></span>
                    <span class="async-widget-line async-widget-line-medium"></span>
                    <p class="async-widget-message">Loading market data after the first paint.</p>
                </div>
            </div>
        </div>

        <noscript>
            <div class="async-widget-frame">
                <div class="async-widget-body">
                    <p class="async-widget-message">Enable JavaScript to load live market widgets.</p>
                </div>
            </div>
        </noscript>

        {{-- Recommended For You --}}
        @php
            $recommendedPosts = app(\App\Services\RecommendationService::class)->getPersonalizedRecommendations(null, 5);
        @endphp
        @if ($recommendedPosts->count())
            <div class="sidebar-section nyt-recommended">
                <h3 class="sidebar-title">{{ __('Recommended For You') }}</h3>
                <div class="recommended-list">
                    @foreach ($recommendedPosts as $index => $post)
                        <div class="recommended-item">
                            <span class="recommended-number">{{ $index + 1 }}</span>
                            <a href="{{ $post->url }}" class="recommended-title" data-track-post="{{ $post->id }}" data-track-source="homepage_recommended_sidebar">{{ $post->name }}</a>
                            <div class="recommended-meta">
                                <span>{{ number_format($post->views ?? 0) }} views</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Additional NYT-style sidebar content can go here --}}
    </aside>
</div>





{!! Theme::partial('footer') !!}
