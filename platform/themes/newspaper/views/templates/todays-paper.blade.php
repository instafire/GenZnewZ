@php
    use Botble\SeoHelper\Facades\SeoHelper;
    use Botble\Blog\Models\Post;
    use Botble\Blog\Models\Category;
    use App\Services\MarketTickerService;

    // Get today's date
    $today = now()->format('l, F j, Y');
    $edition = now()->format('F j, Y');
    
    // Get live market data
    $marketService = app(MarketTickerService::class);
    $marketSnapshot = $marketService->getMarketSnapshot();
    $marketTickers = $marketService->getMarketTickers();

    // Get all latest posts (last 24 hours first, then fill with latest if needed)
    $latestPosts = Post::with(['slugable', 'categories', 'author'])
        ->where('status', 'published')
        ->where('created_at', '>=', now()->subDay())
        ->orderByDesc('created_at')
        ->take(20)
        ->get();

    // If not enough posts from last 24h, get more
    if ($latestPosts->count() < 20) {
        $additionalPosts = Post::with(['slugable', 'categories', 'author'])
            ->where('status', 'published')
            ->whereNotIn('id', $latestPosts->pluck('id'))
            ->orderByDesc('created_at')
            ->take(20 - $latestPosts->count())
            ->get();
        $latestPosts = $latestPosts->merge($additionalPosts);
    }

    // Get featured post (main headline)
    $mainStory = $latestPosts->first();
    $otherStories = $latestPosts->skip(1)->take(19);

    // Group stories by category for sections
    $storiesByCategory = [];
    foreach ($otherStories as $post) {
        $categoryName = $post->categories->first()->name ?? 'General';
        if (!isset($storiesByCategory[$categoryName])) {
            $storiesByCategory[$categoryName] = [];
        }
        if (count($storiesByCategory[$categoryName]) < 3) {
            $storiesByCategory[$categoryName][] = $post;
        }
    }

    SeoHelper::setTitle("Today's Paper | GenZ NewZ");
    SeoHelper::setDescription("Read today's GenZ NewZ front page with the latest headlines, market snapshot, culture coverage, and major stories across the newsroom.");
@endphp

<div class="newspaper-container">
    <!-- Newspaper Header -->
    <header class="newspaper-header">
        <div class="newspaper-masthead">
            <div class="masthead-date">{{ $today }}</div>
            <div class="masthead-title">GenZ NewZ</div>
            <div class="masthead-edition">Today's Paper Edition • {{ $edition }}</div>
        </div>
        <div class="newspaper-tagline">"All The News That's Fit To Print"</div>
        <h1 class="visually-hidden">Today's Paper</h1>
        <nav class="newspaper-nav">
            <a href="{{ route('public.single') }}">Home</a>
            <a href="{{ url('/topic/world-society') }}">World</a>
            <a href="{{ url('/topic/politics') }}">Politics</a>
            <a href="{{ url('/topic/business') }}">Business</a>
            <a href="{{ url('/topic/tech-games') }}">Technology</a>
            <a href="{{ url('/topic/sports') }}">Sports</a>
            <a href="{{ url('/topic/culture') }}">Culture</a>
            <a href="{{ url('/topic/ai-news') }}">AI News</a>
        </nav>
    </header>

    <!-- Main Headline -->
    @if ($mainStory)
    <section class="main-headline">
        <div class="headline-section-label">TOP STORY</div>
        <h2 class="headline-title">
            <a href="{{ $mainStory->url }}">{{ $mainStory->name }}</a>
        </h2>
        <div class="headline-meta">
            <span class="byline">By {{ $mainStory->author->name ?? 'Staff Writer' }}</span>
            <span class="dateline">{{ $mainStory->created_at->format('g:i A') }}</span>
        </div>
        <p class="headline-lead">{{ $mainStory->description }}</p>
        <div class="continue-reading">
            <a href="{{ $mainStory->url }}">Continue Reading →</a>
        </div>
    </section>
    @endif

    <!-- Breaking News Banner -->
    <div class="breaking-banner">
        <span class="breaking-label">BREAKING</span>
        <span class="breaking-text">Latest updates throughout the day at GenZNewz.com</span>
    </div>

    <!-- News Grid -->
    <div class="news-columns">
        <!-- Left Column -->
        <div class="news-column">
            <h3 class="column-header">World News</h3>
            @foreach ($otherStories->take(5) as $post)
                @if ($post->categories->first()->name ?? '' === 'The World' || $post->categories->first()->name ?? '' === 'World')
                <article class="news-item">
                    <h4 class="news-item-title">
                        <a href="{{ $post->url }}">{{ $post->name }}</a>
                    </h4>
                    <p class="news-item-excerpt">{{ Str::limit($post->description, 120) }}</p>
                    <div class="news-item-meta">{{ $post->created_at->diffForHumans() }}</div>
                </article>
                @endif
            @endforeach
            
            @php $worldCount = 0; @endphp
            @foreach ($otherStories as $post)
                @if (($post->categories->first()->name ?? '') !== 'The World' && ($post->categories->first()->name ?? '') !== 'World' && $worldCount < 3)
                @php $worldCount++; @endphp
                <article class="news-item">
                    <h4 class="news-item-title">
                        <a href="{{ $post->url }}">{{ $post->name }}</a>
                    </h4>
                    <p class="news-item-excerpt">{{ Str::limit($post->description, 120) }}</p>
                    <div class="news-item-meta">{{ $post->created_at->diffForHumans() }}</div>
                </article>
                @endif
            @endforeach
        </div>

        <!-- Middle Column -->
        <div class="news-column">
            <h3 class="column-header">Technology & AI</h3>
            @php $techCount = 0; @endphp
            @foreach ($otherStories as $post)
                @if ((($post->categories->first()->name ?? '') === 'AI News' || ($post->categories->first()->name ?? '') === 'Tech & Games') && $techCount < 4)
                @php $techCount++; @endphp
                <article class="news-item featured-item">
                    <h4 class="news-item-title">
                        <a href="{{ $post->url }}">{{ $post->name }}</a>
                    </h4>
                    <p class="news-item-excerpt">{{ Str::limit($post->description, 150) }}</p>
                    <div class="news-item-meta">By <a href="{{ $post->author->url }}" class="author-link">{{ $post->author->name ?? 'Staff' }}</a> • {{ $post->created_at->format('g:i A') }}</div>
                </article>
                @endif
            @endforeach

            <h3 class="column-header" style="margin-top: 30px;">Culture & Lifestyle</h3>
            @php $cultureCount = 0; @endphp
            @foreach ($otherStories as $post)
                @if ((($post->categories->first()->name ?? '') === 'Culture' || ($post->categories->first()->name ?? '') === 'Fashion' || ($post->categories->first()->name ?? '') === 'Life Hacks') && $cultureCount < 3)
                @php $cultureCount++; @endphp
                <article class="news-item">
                    <h4 class="news-item-title">
                        <a href="{{ $post->url }}">{{ $post->name }}</a>
                    </h4>
                    <p class="news-item-excerpt">{{ Str::limit($post->description, 100) }}</p>
                </article>
                @endif
            @endforeach
        </div>

        <!-- Right Column -->
        <div class="news-column">
            <h3 class="column-header">Briefs</h3>
            <div class="briefs-list">
                @foreach ($otherStories->take(10) as $index => $post)
                    @if ($index > 0)
                    <div class="brief-item">
                        <span class="brief-category">{{ $post->categories->first()->name ?? 'News' }}</span>
                        <a href="{{ $post->url }}" class="brief-title">{{ $post->name }}</a>
                    </div>
                    @endif
                @endforeach
            </div>

            <div class="market-box">
                <h4>Market Snapshot</h4>
                @foreach ($marketSnapshot as $key => $data)
                    <div class="market-item">
                        <span class="market-label">{{ $data['label'] }}</span>
                        <span class="market-value {{ $data['change'] >= 0 ? 'bullish' : 'bearish' }}">
                            {{ $data['trend'] }} {{ $data['value'] }}
                        </span>
                    </div>
                @endforeach
                <div class="last-update">Updated: {{ now()->format('g:i A') }} UTC</div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="paper-edition-footer">
        <div class="paper-edition-rule"></div>
        <p>© {{ now()->year }} GenZ NewZ. All rights reserved. | <a href="{{ route('public.single') }}">Back to Homepage</a></p>
    </footer>
</div>

<style>
.newspaper-container {
    max-width: 1100px;
    margin: 0 auto;
    padding: 20px;
    background: #fff;
    color: #121212;
    font-family: 'Times New Roman', Georgia, serif;
}

/* Header */
.newspaper-header {
    text-align: center;
    border-bottom: 3px double #121212;
    padding-bottom: 15px;
    margin-bottom: 20px;
}

.newspaper-masthead {
    position: relative;
    margin-bottom: 10px;
}

.masthead-date {
    position: absolute;
    left: 0;
    top: 50%;
    transform: translateY(-50%);
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: #666;
}

.masthead-title {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 3.5rem;
    font-weight: 900;
    letter-spacing: -2px;
    margin: 0;
    background: linear-gradient(135deg, #FF006E 0%, #FB5607 25%, #FFBE0B 50%, #8338EC 75%, #3A86FF 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.masthead-edition {
    position: absolute;
    right: 0;
    top: 50%;
    transform: translateY(-50%);
    font-size: 0.75rem;
    text-transform: uppercase;
    color: #666;
}

.newspaper-tagline {
    font-size: 0.8rem;
    font-style: italic;
    color: #666;
    margin-bottom: 15px;
    letter-spacing: 2px;
}

.newspaper-nav {
    display: flex;
    justify-content: center;
    gap: 25px;
    flex-wrap: wrap;
    border-top: 1px solid #e2e2e2;
    border-bottom: 1px solid #e2e2e2;
    padding: 10px 0;
}

.newspaper-nav a {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: #121212;
    text-decoration: none;
    transition: color 0.2s;
}

.newspaper-nav a:hover {
    color: #326891;
}

/* Main Headline */
.main-headline {
    text-align: center;
    padding: 30px 0;
    border-bottom: 2px solid #121212;
    margin-bottom: 20px;
}

.headline-section-label {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 0.65rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 3px;
    color: #FF006E;
    margin-bottom: 15px;
}

.headline-title {
    font-family: Georgia, serif;
    font-size: 2.5rem;
    font-weight: 700;
    line-height: 1.1;
    margin: 0 0 15px 0;
}

.headline-title a {
    color: #121212;
    text-decoration: none;
}

.headline-title a:hover {
    color: #326891;
}

.headline-meta {
    font-size: 0.8rem;
    color: #666;
    margin-bottom: 20px;
}

.byline {
    font-weight: 600;
}

.dateline {
    margin-left: 15px;
    text-transform: uppercase;
}

.headline-lead {
    font-size: 1.1rem;
    line-height: 1.6;
    color: #444;
    max-width: 700px;
    margin: 0 auto 20px;
    text-align: left;
}

.continue-reading {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.continue-reading a {
    color: #326891;
    text-decoration: none;
}

.continue-reading a:hover {
    text-decoration: underline;
}

/* Breaking Banner */
.breaking-banner {
    background: #121212;
    color: #fff;
    padding: 10px 20px;
    margin-bottom: 25px;
    text-align: center;
}

.breaking-label {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 2px;
    background: #FF006E;
    padding: 3px 10px;
    margin-right: 15px;
}

.breaking-text {
    font-size: 0.85rem;
    font-style: italic;
}

/* News Columns */
.news-columns {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 30px;
    margin-bottom: 30px;
}

@media (max-width: 900px) {
    .news-columns {
        grid-template-columns: 1fr 1fr;
    }
}

@media (max-width: 600px) {
    .news-columns {
        grid-template-columns: 1fr;
    }
    /* Stack the masthead: absolute date/edition overlap the title on narrow screens. */
    .newspaper-masthead {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 4px;
        text-align: center;
    }
    .masthead-date,
    .masthead-edition {
        position: static;
        transform: none;
    }
    .masthead-title {
        font-size: 2.6rem;
        letter-spacing: -1px;
    }
}

.news-column {
    border-right: 1px solid #e2e2e2;
    padding-right: 20px;
}

.news-column:last-child {
    border-right: none;
    padding-right: 0;
}

.column-header {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 0.9rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1px;
    border-bottom: 2px solid #121212;
    padding-bottom: 8px;
    margin-bottom: 20px;
}

/* News Items */
.news-item {
    margin-bottom: 25px;
    padding-bottom: 20px;
    border-bottom: 1px solid #e2e2e2;
}

.news-item:last-child {
    border-bottom: none;
}

.news-item-title {
    font-family: Georgia, serif;
    font-size: 1.1rem;
    font-weight: 700;
    line-height: 1.3;
    margin: 0 0 10px 0;
}

.news-item-title a {
    color: #121212;
    text-decoration: none;
}

.news-item-title a:hover {
    color: #326891;
    text-decoration: underline;
}

.news-item-excerpt {
    font-size: 0.85rem;
    line-height: 1.5;
    color: #555;
    margin: 0 0 8px 0;
}

.news-item-meta {
    font-size: 0.7rem;
    color: #888;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.featured-item {
    background: #fafafa;
    padding: 15px;
    border-left: 3px solid #FF006E;
}

/* Briefs */
.briefs-list {
    border-top: 2px solid #121212;
}

.brief-item {
    padding: 12px 0;
    border-bottom: 1px dotted #ccc;
}

.brief-category {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 0.65rem;
    font-weight: 700;
    text-transform: uppercase;
    color: #FF006E;
    display: block;
    margin-bottom: 4px;
}

.brief-title {
    font-size: 0.85rem;
    font-weight: 600;
    color: #121212;
    text-decoration: none;
    line-height: 1.4;
}

.brief-title:hover {
    color: #326891;
}

/* Market Box */
.market-box {
    margin-top: 30px;
    padding: 15px;
    background: #f5f5f5;
    border: 1px solid #e2e2e2;
}

.market-box h4 {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1px;
    border-bottom: 1px solid #ccc;
    padding-bottom: 8px;
    margin-bottom: 12px;
}

.market-item {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid #e2e2e2;
    font-size: 0.8rem;
}

.market-item:last-child {
    border-bottom: none;
}

.market-label {
    font-weight: 600;
}

.market-value {
    font-weight: 600;
}

.market-value.bullish {
    color: #27ae60;
}

.market-value.bearish {
    color: #e74c3c;
}

.last-update {
    text-align: center;
    font-size: 0.65rem;
    color: #888;
    margin-top: 10px;
    padding-top: 8px;
    border-top: 1px solid #ddd;
}

/* Footer */
.paper-edition-footer {
    text-align: center;
    padding-top: 20px;
    margin-top: 20px;
}

.paper-edition-rule {
    border-top: 3px double #121212;
    margin-bottom: 15px;
}

.paper-edition-footer p {
    font-size: 0.75rem;
    color: #666;
}

.paper-edition-footer a {
    color: #326891;
    text-decoration: none;
}

/* Dark Mode Support */
body.dark-mode .newspaper-container {
    background: #121212;
    color: #e0e0e0;
}

body.dark-mode .newspaper-header {
    border-bottom-color: #444;
}

body.dark-mode .masthead-title {
    -webkit-text-fill-color: initial;
    background: linear-gradient(135deg, #FF6B9D 0%, #FC8B4A 50%, #FFD93D 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

body.dark-mode .masthead-date,
body.dark-mode .masthead-edition,
body.dark-mode .newspaper-tagline {
    color: #888;
}

body.dark-mode .newspaper-nav {
    border-color: #333;
}

body.dark-mode .newspaper-nav a {
    color: #e0e0e0;
}

body.dark-mode .newspaper-nav a:hover {
    color: #5a9fd4;
}

body.dark-mode .main-headline {
    border-bottom-color: #444;
}

body.dark-mode .headline-title a {
    color: #e0e0e0;
}

body.dark-mode .headline-title a:hover {
    color: #5a9fd4;
}

body.dark-mode .headline-lead {
    color: #aaa;
}

body.dark-mode .news-column {
    border-right-color: #333;
}

body.dark-mode .column-header {
    color: #e0e0e0;
    border-bottom-color: #444;
}

body.dark-mode .news-item {
    border-bottom-color: #333;
}

body.dark-mode .news-item-title a {
    color: #e0e0e0;
}

body.dark-mode .news-item-title a:hover {
    color: #5a9fd4;
}

body.dark-mode .news-item-excerpt {
    color: #aaa;
}

body.dark-mode .news-item-meta {
    color: #666;
}

body.dark-mode .featured-item {
    background: #1a1a1a;
}

body.dark-mode .briefs-list {
    border-top-color: #444;
}

body.dark-mode .brief-item {
    border-bottom-color: #333;
}

body.dark-mode .brief-title {
    color: #e0e0e0;
}

body.dark-mode .brief-title:hover {
    color: #5a9fd4;
}

body.dark-mode .market-box {
    background: #1a1a1a;
    border-color: #333;
}

body.dark-mode .market-box h4 {
    border-bottom-color: #444;
}

body.dark-mode .market-item {
    border-bottom-color: #333;
}

body.dark-mode .paper-edition-rule {
    border-color: #444;
}

body.dark-mode .paper-edition-footer p {
    color: #888;
}
</style>
