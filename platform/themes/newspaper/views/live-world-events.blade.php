@php
    use App\Services\LiveWorldEventsServiceOptimized;
    use Botble\SeoHelper\Facades\SeoHelper;
    use Illuminate\Support\Str;

    SeoHelper::setTitle('World Events GenZ - Real-time Global News Feed');
    SeoHelper::setDescription('World Events GenZ brings you real-time news feeds from around the world. Breaking news, technology, sports, entertainment, and more.');

    $rssService = app(LiveWorldEventsServiceOptimized::class);
    $categories = $rssService->getCategories();

    // Dispatch background jobs to fetch feeds (non-blocking)
    $rssService->dispatchFeedFetchJobs();

    // Get initial cached data (instant, may be empty)
    $allHeadlines = $rssService->getAllCachedHeadlines();
    $progress = $rssService->getLoadingProgress();
@endphp

<div class="live-events-page">
    <!-- Page Header -->
    <div class="live-events-header">
        <h1 class="live-events-title">World Events GenZ</h1>
        <p class="live-events-subtitle">Real-time news feeds from around the globe</p>

        <!-- Loading Progress Bar -->
        @if(!$progress['is_complete'])
        <div class="loading-progress" id="loadingProgress">
            <div class="progress-bar-container">
                <div class="progress-bar" style="width: {{ $progress['percentage'] }}%"></div>
            </div>
            <div class="progress-text">
                Loading feeds... {{ $progress['loaded'] }}/{{ $progress['total'] }} categories
            </div>
        </div>
        @else
        <span class="last-updated">Updated: {{ now()->format('F j, Y g:i A') }} UTC</span>
        @endif
    </div>

    {{-- GLOBAL RSS NEWS TICKER --}}
    <section class="global-rss-ticker-section" id="globalRssTicker">
        <div class="rss-ticker-loading">
            <span>Loading international headlines...</span>
        </div>
    </section>

    <!-- News Categories -->
    <div class="live-events-content" id="liveEventsContent">
        @foreach ($categories as $category)
            <section class="news-category-section" id="category-{{ Str::slug($category) }}" data-category="{{ $category }}">
                <div class="category-header">
                    <h2 class="category-title">{{ $category }}</h2>
                    @if(isset($allHeadlines[$category]) && !empty($allHeadlines[$category]))
                        <span class="headline-count">{{ count($allHeadlines[$category]) }} stories</span>
                    @else
                        <span class="loading-spinner">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <circle cx="12" cy="12" r="10" stroke-width="2" stroke-dasharray="32" stroke-linecap="round">
                                    <animateTransform attributeName="transform" type="rotate" dur="1s" repeatCount="indefinite" from="0 12 12" to="360 12 12"/>
                                </circle>
                            </svg>
                            Loading...
                        </span>
                    @endif
                </div>

                @if(isset($allHeadlines[$category]) && !empty($allHeadlines[$category]))
                    <!-- Ticker (only if data available) -->
                    <div class="ticker-container">
                        <div class="ticker-row">
                            <div class="ticker-marquee">
                                <div class="ticker-content">
                                    @foreach ($allHeadlines[$category] as $headline)
                                        <a href="{{ $headline['link'] }}" target="_blank" rel="nofollow noopener" class="ticker-headline">
                                            <span class="headline-source">{{ $headline['source'] }}</span>
                                            <span class="headline-text">{{ $headline['title'] }}</span>
                                            <span class="headline-time">{{ \Carbon\Carbon::parse($headline['pubDate'])->diffForHumans() }}</span>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Headlines Grid -->
                    <div class="headlines-grid">
                        @foreach ($allHeadlines[$category] as $index => $headline)
                            <article class="headline-card" style="animation-delay: {{ $index * 0.05 }}s">
                                <a href="{{ $headline['link'] }}" target="_blank" rel="nofollow noopener" class="headline-link">
                                    <span class="card-source">{{ $headline['source'] }}</span>
                                    <h3 class="card-title">{{ $headline['title'] }}</h3>
                                    <span class="card-time">{{ \Carbon\Carbon::parse($headline['pubDate'])->diffForHumans() }}</span>
                                </a>
                            </article>
                        @endforeach
                    </div>
                @else
                    <!-- Skeleton Loader -->
                    <div class="skeleton-loader">
                        <div class="skeleton-ticker"></div>
                        <div class="skeleton-grid">
                            @for ($i = 0; $i < 6; $i++)
                                <div class="skeleton-card">
                                    <div class="skeleton-source"></div>
                                    <div class="skeleton-title"></div>
                                    <div class="skeleton-title short"></div>
                                    <div class="skeleton-time"></div>
                                </div>
                            @endfor
                        </div>
                    </div>
                @endif
            </section>
        @endforeach
    </div>
</div>

<style>
/* Live Events Page Styles */
.live-events-page {
    max-width: 1400px;
    margin: 0 auto;
    padding: 30px 20px;
}

/* Header */
.live-events-header {
    text-align: center;
    padding: 40px 20px;
    border-bottom: 3px double #121212;
    margin-bottom: 30px;
}

.live-events-title {
    font-family: Georgia, 'Times New Roman', Times, serif;
    font-size: 2.5rem;
    font-weight: 700;
    color: #121212;
    margin: 0 0 10px;
}

.live-events-subtitle {
    font-size: 1.1rem;
    color: #666;
    margin: 0 0 15px;
}

/* Loading Progress */
.loading-progress {
    margin: 20px auto;
    max-width: 400px;
}

.progress-bar-container {
    width: 100%;
    height: 8px;
    background: #e0e0e0;
    border-radius: 4px;
    overflow: hidden;
    margin-bottom: 10px;
}

.progress-bar {
    height: 100%;
    background: linear-gradient(90deg, #326891, #5a9fd4);
    transition: width 0.5s ease;
    animation: progress-pulse 2s ease-in-out infinite;
}

@keyframes progress-pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.8; }
}

.progress-text {
    font-size: 0.9rem;
    color: #666;
    text-align: center;
}

.last-updated {
    font-size: 0.85rem;
    color: #888;
    font-style: italic;
}

/* Loading Spinner */
.loading-spinner {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 0.85rem;
    color: #666;
}

.loading-spinner svg {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* Category Sections */
.news-category-section {
    margin-bottom: 50px;
    padding-bottom: 30px;
    border-bottom: 1px solid #e2e2e2;
}

.news-category-section:last-child {
    border-bottom: none;
}

.category-header {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 15px;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 2px solid #121212;
}

.category-title {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1.4rem;
    font-weight: 700;
    color: #121212;
    margin: 0;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.headline-count {
    font-size: 0.85rem;
    color: #666;
    background: #f0f0f0;
    padding: 4px 10px;
    border-radius: 12px;
}

/* Skeleton Loader */
.skeleton-loader {
    animation: fadeIn 0.3s ease;
}

.skeleton-ticker {
    height: 50px;
    background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
    background-size: 200% 100%;
    animation: skeleton-loading 1.5s infinite;
    border-radius: 8px;
    margin-bottom: 25px;
}

.skeleton-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 20px;
}

.skeleton-card {
    background: #fff;
    border: 1px solid #e2e2e2;
    border-radius: 8px;
    padding: 20px;
}

.skeleton-source {
    width: 80px;
    height: 16px;
    background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
    background-size: 200% 100%;
    animation: skeleton-loading 1.5s infinite;
    border-radius: 4px;
    margin-bottom: 12px;
}

.skeleton-title {
    width: 100%;
    height: 18px;
    background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
    background-size: 200% 100%;
    animation: skeleton-loading 1.5s infinite;
    border-radius: 4px;
    margin-bottom: 8px;
}

.skeleton-title.short {
    width: 70%;
}

.skeleton-time {
    width: 100px;
    height: 14px;
    background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
    background-size: 200% 100%;
    animation: skeleton-loading 1.5s infinite;
    border-radius: 4px;
    margin-top: 12px;
}

@keyframes skeleton-loading {
    0% { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

/* Ticker Style */
.ticker-container {
    background: #f8f9fa;
    border: 1px solid #e2e2e2;
    border-radius: 8px;
    margin-bottom: 25px;
    overflow: hidden;
}

.ticker-row {
    padding: 12px 0;
}

.ticker-marquee {
    overflow: hidden;
    position: relative;
    mask-image: linear-gradient(to right, transparent, black 30px, black calc(100% - 30px), transparent);
}

.ticker-content {
    display: inline-flex;
    white-space: nowrap;
    animation: ticker-scroll 120s linear infinite;
}

.ticker-content:hover {
    animation-play-state: paused;
}

@keyframes ticker-scroll {
    0% { transform: translateX(0); }
    100% { transform: translateX(-50%); }
}

.ticker-headline {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 0 25px;
    color: #121212;
    text-decoration: none;
    border-right: 1px solid #ddd;
}

.ticker-headline:hover {
    color: #326891;
}

.headline-source {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    color: #326891;
    background: rgba(50, 104, 145, 0.1);
    padding: 3px 8px;
    border-radius: 3px;
}

.headline-text {
    font-family: Georgia, 'Times New Roman', serif;
    font-size: 0.95rem;
}

.headline-time {
    font-size: 0.75rem;
    color: #888;
}

/* Headlines Grid */
.headlines-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 20px;
}

.headline-card {
    background: #fff;
    border: 1px solid #e2e2e2;
    border-radius: 8px;
    transition: all 0.2s;
    animation: fadeInUp 0.5s ease forwards;
    opacity: 0;
}

.headline-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    border-color: #326891;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

.headline-link {
    display: block;
    padding: 20px;
    text-decoration: none;
    color: inherit;
}

.card-source {
    display: inline-block;
    font-family: 'Space Grotesk', sans-serif;
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    color: #326891;
    margin-bottom: 10px;
}

.card-title {
    font-family: Georgia, 'Times New Roman', serif;
    font-size: 1.05rem;
    font-weight: 500;
    line-height: 1.4;
    color: #121212;
    margin: 0 0 12px;
}

.card-time {
    font-size: 0.8rem;
    color: #888;
}

/* Dark Mode Support */
body.dark-mode .live-events-header {
    border-bottom-color: #444;
}

body.dark-mode .live-events-title {
    color: #f0f0f0;
}

body.dark-mode .live-events-subtitle {
    color: #aaa;
}

body.dark-mode .progress-bar-container {
    background: #333;
}

body.dark-mode .progress-text {
    color: #aaa;
}

body.dark-mode .last-updated {
    color: #888;
}

body.dark-mode .auto-refresh-indicator {
    color: #666 !important;
}

body.dark-mode .loading-spinner {
    color: #888;
}

body.dark-mode .news-category-section {
    border-bottom-color: #333;
}

body.dark-mode .category-header {
    border-bottom-color: #444;
}

body.dark-mode .category-title {
    color: #f0f0f0;
}

body.dark-mode .headline-count {
    color: #888;
    background: #2a2a2a;
}

/* Skeleton dark mode */
body.dark-mode .skeleton-ticker,
body.dark-mode .skeleton-source,
body.dark-mode .skeleton-title,
body.dark-mode .skeleton-time {
    background: linear-gradient(90deg, #2a2a2a 25%, #333 50%, #2a2a2a 75%);
    background-size: 200% 100%;
}

body.dark-mode .skeleton-card {
    background: #1a1a1a;
    border-color: #333;
}

/* Ticker dark mode */
body.dark-mode .ticker-container {
    background: #1a1a1a;
    border-color: #333;
}

body.dark-mode .ticker-headline {
    color: #e0e0e0;
    border-right-color: #444;
}

body.dark-mode .ticker-headline:hover {
    color: #5a9fd4;
}

body.dark-mode .headline-source {
    color: #5a9fd4;
    background: rgba(90, 159, 212, 0.15);
}

body.dark-mode .headline-time {
    color: #666;
}

/* Headlines Grid dark mode */
body.dark-mode .headline-card {
    background: #1a1a1a;
    border-color: #333;
}

body.dark-mode .headline-card:hover {
    border-color: #5a9fd4;
    box-shadow: 0 8px 25px rgba(0,0,0,0.3);
}

body.dark-mode .card-source {
    color: #5a9fd4;
}

body.dark-mode .card-title {
    color: #e0e0e0;
}

body.dark-mode .card-time {
    color: #666;
}

/* Responsive */
@media (max-width: 768px) {
    .live-events-title {
        font-size: 1.8rem;
    }

    .category-title {
        font-size: 1.1rem;
    }

    .headlines-grid, .skeleton-grid {
        grid-template-columns: 1fr;
    }
}

/* ============================================
   GLOBAL RSS TICKER STYLES (Moved from Homepage)
   ============================================ */
.global-rss-ticker-section {
    background: linear-gradient(180deg, #f8f9fa 0%, #ffffff 100%);
    border-top: 2px solid #121212;
    border-bottom: 2px solid #121212;
    margin: 0 0 30px;
    padding: 15px 0;
}

.global-rss-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 20px;
}

.global-rss-rows {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.global-rss-row {
    display: flex;
    align-items: center;
    padding: 6px 0;
    border-bottom: 1px dotted #ddd;
}

.global-rss-row:last-child {
    border-bottom: none;
}

.global-rss-flag {
    flex-shrink: 0;
    width: 28px;
    height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 12px;
    border-radius: 50%;
    font-family: 'Space Grotesk', sans-serif;
    font-size: 0.6rem;
    font-weight: 700;
    color: #fff;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

/* Country Flags */
.flag-russia {
    background: linear-gradient(180deg, #ffffff 0%, #ffffff 33.33%, #0039a6 33.33%, #0039a6 66.66%, #d52b1e 66.66%, #d52b1e 100%);
    color: #0039a6 !important;
    text-shadow: 0 0 1px rgba(255,255,255,0.9);
}

.flag-united-states, .flag-united-states {
    background: linear-gradient(180deg, #b22234 0%, #b22234 7.69%, #ffffff 7.69%, #ffffff 15.38%, #b22234 15.38%, #b22234 23.07%, #ffffff 23.07%, #ffffff 30.76%, #b22234 30.76%, #b22234 38.45%, #ffffff 38.45%, #ffffff 46.14%, #b22234 46.14%, #b22234 53.83%, #ffffff 53.83%, #ffffff 61.52%, #b22234 61.52%, #b22234 69.21%, #ffffff 69.21%, #ffffff 76.9%, #b22234 76.9%, #b22234 84.59%, #ffffff 84.59%, #ffffff 92.28%, #b22234 92.28%, #b22234 100%);
    color: #b22234 !important;
}

.flag-china {
    background: #de2910;
    color: #ffde00 !important;
}

.flag-india {
    background: linear-gradient(180deg, #ff9932 0%, #ff9932 33.33%, #ffffff 33.33%, #ffffff 66.66%, #138808 66.66%, #138808 100%);
    color: #000080 !important;
}

.flag-brazil {
    background: linear-gradient(180deg, #009739 0%, #009739 33.33%, #fedd00 33.33%, #fedd00 66.66%, #002776 66.66%, #002776 100%);
    color: #fedd00 !important;
}

.global-rss-marquee {
    flex: 1;
    overflow: hidden;
    position: relative;
    mask-image: linear-gradient(to right, transparent, black 20px, black calc(100% - 20px), transparent);
    -webkit-mask-image: linear-gradient(to right, transparent, black 20px, black calc(100% - 20px), transparent);
}

.global-rss-content {
    display: inline-flex;
    align-items: center;
    gap: 0;
    white-space: nowrap;
    animation: global-rss-scroll 120s linear infinite;
}

.global-rss-content:hover {
    animation-play-state: paused;
}

@keyframes global-rss-scroll {
    0% { transform: translateX(0); }
    100% { transform: translateX(-50%); }
}

.global-rss-link {
    display: inline-flex;
    align-items: center;
    padding: 4px 20px;
    color: #121212;
    text-decoration: none;
    font-size: 0.9rem;
    border-right: 1px solid #e2e2e2;
    transition: color 0.2s;
}

.global-rss-link:hover {
    color: #326891;
}

/* Loading State */
.rss-ticker-loading {
    text-align: center;
    padding: 20px;
    color: #666;
    font-size: 0.9rem;
}

.rss-ticker-loading::after {
    content: '';
    display: inline-block;
    width: 16px;
    height: 16px;
    border: 2px solid #e2e2e2;
    border-top-color: #326891;
    border-radius: 50%;
    animation: rss-spinner 0.8s linear infinite;
    margin-left: 8px;
    vertical-align: middle;
}

@keyframes rss-spinner {
    to { transform: rotate(360deg); }
}

/* Dark Mode */
body.dark-mode .global-rss-ticker-section {
    background: linear-gradient(180deg, #1a1a1a 0%, #121212 100%);
    border-color: #333;
}

body.dark-mode .global-rss-row {
    border-color: #333;
}

body.dark-mode .global-rss-link {
    color: #e0e0e0;
    border-color: #333;
}

body.dark-mode .global-rss-link:hover {
    color: #5a9fd4;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Duplicate ticker content for seamless loop
    const tickerContents = document.querySelectorAll('.ticker-content');
    tickerContents.forEach(content => {
        const originalHTML = content.innerHTML;
        content.innerHTML = originalHTML + originalHTML;
    });

    // Progressive loading: Poll for updates
    let pollAttempts = 0;
    const maxPollAttempts = 30; // 30 attempts × 2 seconds = 60 seconds max

    function pollForUpdates() {
        if (pollAttempts >= maxPollAttempts) {
            console.log('Max poll attempts reached');
            return;
        }

        pollAttempts++;

        fetch('/api/live-events/progress')
            .then(response => response.json())
            .then(data => {
                if (data.is_complete) {
                    console.log('All feeds loaded, reloading page...');
                    setTimeout(() => location.reload(), 1000);
                    return;
                }

                // Update progress bar
                const progressBar = document.querySelector('.progress-bar');
                const progressText = document.querySelector('.progress-text');

                if (progressBar) {
                    progressBar.style.width = data.percentage + '%';
                }

                if (progressText) {
                    progressText.textContent = `Loading feeds... ${data.loaded}/${data.total} categories`;
                }

                // Continue polling
                setTimeout(pollForUpdates, 2000);
            })
            .catch(error => {
                console.error('Poll error:', error);
                setTimeout(pollForUpdates, 5000); // Retry after longer delay
            });
    }

    // Start polling if not complete
    const loadingProgress = document.getElementById('loadingProgress');
    if (loadingProgress) {
        pollForUpdates();
    }

    // Auto-refresh data every 5 minutes (refresh cache, not page).
    // The endpoint is reachable without a token — a browser cannot hold a secret
    // without publishing it — and it answers 403 for cross-origin callers, so check
    // the status rather than assuming the trigger succeeded.
    setInterval(function() {
        fetch('/api/live-events/refresh', {
            method: 'POST',
            headers: { 'Accept': 'application/json' }
        })
            .then(function(response) {
                if (!response.ok) {
                    console.warn('Background refresh rejected:', response.status);
                    return null;
                }

                return response.json().then(function(data) {
                    console.log(data && data.skipped
                        ? 'Background refresh skipped (cache still fresh)'
                        : 'Background refresh triggered');
                });
            })
            .catch(function(error) {
                console.error('Refresh error:', error);
            });
    }, 300000); // 5 minutes

    // Load Global RSS Ticker asynchronously
    loadGlobalRssTicker();
});

// Global RSS Ticker Functionality
function loadGlobalRssTicker() {
    const container = document.getElementById('globalRssTicker');
    if (!container) return;
    
    fetch('/api/rss-ticker')
        .then(response => response.json())
        .then(data => {
            if (data.headlines && Object.keys(data.headlines).length > 0) {
                renderGlobalRssTicker(data.headlines);
            } else {
                container.style.display = 'none';
            }
        })
        .catch(err => {
            console.log('Global RSS ticker load failed:', err);
            container.style.display = 'none';
        });
}

function renderGlobalRssTicker(headlines) {
    const container = document.getElementById('globalRssTicker');
    const countryFlags = {
        'Russia': 'RU', 'United States': 'US', 'China': 'CN', 
        'India': 'IN', 'Brazil': 'BR'
    };
    
    let html = '<div class="global-rss-container"><div class="global-rss-rows">';
    for (const [country, items] of Object.entries(headlines)) {
        if (items.length === 0) continue;
        
        const flag = countryFlags[country] || country.substring(0, 2).toUpperCase();
        const countryClass = 'flag-' + country.toLowerCase().replace(' ', '-');
        
        // Build row HTML
        let rowHtml = '<div class="global-rss-row" data-country="' + country + '">';
        rowHtml += '<div class="global-rss-flag ' + countryClass + '">' + flag + '</div>';
        rowHtml += '<div class="global-rss-marquee"><div class="global-rss-content">';
        
        // Add headlines
        items.forEach(function(h) {
            rowHtml += '<a href="' + h.link + '" target="_blank" rel="nofollow noopener sponsored" class="global-rss-link">';
            rowHtml += h.title;
            rowHtml += '</a>';
        });
        
        rowHtml += '</div></div></div>';
        html += rowHtml;
    }
    html += '</div></div>';
    container.innerHTML = html;
    
    // Duplicate content for seamless loop
    document.querySelectorAll('.global-rss-content').forEach(function(content) {
        content.innerHTML += content.innerHTML;
    });
}
</script>
