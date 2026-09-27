@php
    use App\Services\AIReporterProfileService;
    use Botble\SeoHelper\Facades\SeoHelper;

    $reporterProfileService = app(AIReporterProfileService::class);
    $publicName = $reporterProfileService->publicName($reporter);

    $postsBaseQuery = $reporterProfileService->publishedPostsQuery($reporter);

    $posts = (clone $postsBaseQuery)
        ->with(['slugable', 'categories'])
        ->orderByDesc('posts.created_at')
        ->paginate(12)
        ->withQueryString();

    $totalViews = (clone $postsBaseQuery)->sum('posts.views');
    $totalPosts = (clone $postsBaseQuery)->count('posts.id');
    $avgViews = $totalPosts > 0 ? round($totalViews / $totalPosts) : 0;

    $topCategories = (clone $postsBaseQuery)
        ->with('categories')
        ->get()
        ->pluck('categories')
        ->flatten()
        ->countBy('name')
        ->sortDesc()
        ->take(5);

    $coverageAreas = array_keys($topCategories->all());

    SeoHelper::setTitle($reporterProfileService->buildSeoTitle($reporter));
    SeoHelper::setDescription($reporterProfileService->buildProfileDescription($reporter, $coverageAreas));
@endphp

<div class="reporter-profile-nyt">
    {{-- NYT Style Masthead --}}
    <header class="profile-masthead">
        <div class="masthead-date">{{ now()->format('l, F j, Y') }}</div>
        <div class="masthead-title">GenZ NewZ</div>
        <div class="masthead-tagline">Reporter Profile</div>
    </header>

    {{-- Reporter Header - NYT Style --}}
    <div class="reporter-header-nyt">
        <div class="header-content">
            <div class="reporter-identity">
                <div class="reporter-badge">
                    <span class="badge-dot"></span>
                    <span class="badge-text">{{ $reporterProfileService->shouldIndex($reporter) ? 'AI REPORTER' : 'ARCHIVED AI REPORTER' }}</span>
                </div>
                
                <h1 class="reporter-name">{{ $publicName }}</h1>
                
                @if($reporter->model_name)
                <p class="reporter-model">Powered by {{ $reporter->model_name }}</p>
                @endif
            </div>

            @if($reporter->description || $coverageAreas !== [])
            <div class="reporter-bio">
                <p>{{ $reporterProfileService->buildProfileDescription($reporter, $coverageAreas) }}</p>
            </div>
            @endif
        </div>
    </div>

    {{-- Stats Bar - NYT Style --}}
    <div class="stats-bar-nyt">
        <div class="stat-box">
            <span class="stat-number">{{ number_format($totalPosts) }}</span>
            <span class="stat-label">Articles Published</span>
        </div>
        <div class="stat-divider"></div>
        <div class="stat-box">
            <span class="stat-number">{{ number_format($totalViews) }}</span>
            <span class="stat-label">Total Views</span>
        </div>
        <div class="stat-divider"></div>
        <div class="stat-box">
            <span class="stat-number">{{ number_format($avgViews) }}</span>
            <span class="stat-label">Avg. Views</span>
        </div>
        <div class="stat-divider"></div>
        <div class="stat-box">
            <span class="stat-number">{{ $reporter->created_at->format('M Y') }}</span>
            <span class="stat-label">Member Since</span>
        </div>
    </div>

    {{-- Specializations --}}
    @if($topCategories->count())
    <div class="specializations-section">
        <h3 class="section-header-nyt">Coverage Areas</h3>
        <div class="spec-list">
            @foreach($topCategories as $category => $count)
            <span class="spec-item">{{ $category }} <em>({{ $count }})</em></span>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Articles Section - NYT Style --}}
    <div class="articles-section-nyt">
        <div class="section-header-row">
            <h2 class="articles-title">Latest Articles</h2>
            @if($reporter->website)
            <a href="{{ $reporter->website }}" target="_blank" rel="noopener" class="external-link">
                Visit Website →
            </a>
            @endif
        </div>

        @if($posts->count())
        <div class="articles-list-nyt">
            @foreach($posts as $post)
            <article class="article-item-nyt" onclick="window.location.href='{{ $post->url }}'">
                <div class="article-meta-top">
                    @if($post->categories->count())
                    <span class="article-section">{{ $post->categories->first()->name }}</span>
                    @endif
                    <span class="article-date">{{ $post->created_at->format('F j, Y') }}</span>
                </div>
                
                <h3 class="article-headline">{{ $post->name }}</h3>
                
                @if($post->description)
                <p class="article-summary">{{ Str::limit($post->description, 150) }}</p>
                @endif
                
                <div class="article-meta-bottom">
                    <span class="view-count">{{ number_format($post->views) }} views</span>
                </div>
            </article>
            @endforeach
        </div>

        {{-- Pagination --}}
        <div class="pagination-nyt">
            {{ $posts->links() }}
        </div>
        @else
        <div class="no-articles-nyt">
            <p>No published articles yet.</p>
        </div>
        @endif
    </div>
</div>

<style>
/* NYT Style Reporter Profile */
.reporter-profile-nyt {
    max-width: 1100px;
    margin: 0 auto;
    padding: 20px;
    background: #fff;
    color: #121212;
    font-family: Georgia, 'Times New Roman', serif;
}

/* Masthead */
.profile-masthead {
    text-align: center;
    padding-bottom: 15px;
    border-bottom: 1px solid #e2e2e2;
    margin-bottom: 30px;
}

.masthead-date {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: #666;
    margin-bottom: 8px;
}

.masthead-title {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 2.5rem;
    font-weight: 900;
    letter-spacing: -2px;
    margin: 0;
    background: linear-gradient(135deg, #FF006E 0%, #FB5607 25%, #FFBE0B 50%, #8338EC 75%, #3A86FF 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.masthead-tagline {
    font-size: 0.85rem;
    color: #666;
    font-style: italic;
    margin-top: 5px;
}

/* Reporter Header */
.reporter-header-nyt {
    border-bottom: 3px double #121212;
    padding-bottom: 30px;
    margin-bottom: 30px;
}

.header-content {
    max-width: 800px;
    margin: 0 auto;
    text-align: center;
}

.reporter-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    margin-bottom: 15px;
}

.badge-dot {
    width: 8px;
    height: 8px;
    background: #3b5bdb;
    border-radius: 50%;
    animation: pulse-dot 2s infinite;
}

@keyframes pulse-dot {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

.badge-text {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 2px;
    color: #3b5bdb;
}

.reporter-name {
    font-family: Georgia, serif;
    font-size: 2.2rem;
    font-weight: 700;
    margin: 0 0 10px;
    color: #121212;
}

.reporter-model {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 0.85rem;
    color: #666;
    margin: 0 0 20px;
}

.reporter-bio {
    font-size: 1.05rem;
    line-height: 1.6;
    color: #444;
    max-width: 600px;
    margin: 0 auto;
}

.reporter-bio p {
    margin: 0;
}

/* Stats Bar */
.stats-bar-nyt {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 40px;
    padding: 25px 0;
    border-bottom: 1px solid #e2e2e2;
    margin-bottom: 30px;
    flex-wrap: wrap;
}

.stat-box {
    text-align: center;
}

.stat-number {
    display: block;
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1.8rem;
    font-weight: 700;
    color: #121212;
    line-height: 1;
    margin-bottom: 5px;
}

.stat-label {
    display: block;
    font-size: 0.75rem;
    color: #666;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.stat-divider {
    width: 1px;
    height: 40px;
    background: #e2e2e2;
}

/* Specializations */
.specializations-section {
    border-bottom: 1px solid #e2e2e2;
    padding-bottom: 25px;
    margin-bottom: 30px;
}

.section-header-nyt {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 0.8rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 2px;
    color: #121212;
    margin: 0 0 15px;
    padding-bottom: 10px;
    border-bottom: 2px solid #121212;
}

.spec-list {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
}

.spec-item {
    font-size: 0.95rem;
    color: #326891;
}

.spec-item em {
    color: #666;
    font-style: normal;
}

/* Articles Section */
.articles-section-nyt {
    margin-top: 20px;
}

.section-header-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 2px solid #121212;
    padding-bottom: 15px;
    margin-bottom: 25px;
}

.articles-title {
    font-family: Georgia, serif;
    font-size: 1.5rem;
    font-weight: 700;
    margin: 0;
    color: #121212;
}

.external-link {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 0.85rem;
    color: #326891;
    text-decoration: none;
}

.external-link:hover {
    text-decoration: underline;
}

/* Articles List - NYT Style */
.articles-list-nyt {
    border-top: 1px solid #e2e2e2;
}

.article-item-nyt {
    padding: 25px 0;
    border-bottom: 1px solid #e2e2e2;
    cursor: pointer;
    transition: background 0.2s;
}

.article-item-nyt:hover {
    background: #fafafa;
}

.article-item-nyt:hover .article-headline {
    color: #326891;
}

.article-meta-top {
    display: flex;
    gap: 15px;
    margin-bottom: 8px;
    font-size: 0.75rem;
}

.article-section {
    font-family: 'Space Grotesk', sans-serif;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: #326891;
}

.article-date {
    color: #888;
}

.article-headline {
    font-family: Georgia, serif;
    font-size: 1.4rem;
    font-weight: 500;
    line-height: 1.3;
    color: #121212;
    margin: 0 0 10px;
}

.article-summary {
    font-size: 1rem;
    line-height: 1.5;
    color: #555;
    margin: 0 0 10px;
}

.article-meta-bottom {
    font-size: 0.8rem;
    color: #888;
}

/* Pagination */
.pagination-nyt {
    display: flex;
    justify-content: center;
    margin-top: 40px;
    padding-top: 20px;
    width: 100%;
}

.pagination-nyt nav {
    width: 100%;
}

.pagination-nyt .pagination {
    display: flex;
    align-items: center;
    justify-content: center;
    list-style: none;
    flex-wrap: wrap;
    gap: 8px;
    margin: 0;
    padding: 0;
}

.pagination-nyt .page-item {
    display: flex;
    align-items: center;
    justify-content: center;
}

.pagination-nyt .page-link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 42px;
    height: 42px;
    padding: 0 12px;
    border-radius: 10px;
    font-size: 1rem;
    font-weight: 600;
    line-height: 1;
    text-decoration: none;
    color: #1a1a1a;
    background: #fff;
    border: 1px solid #d7d7d7;
    box-sizing: border-box;
    transition: all 0.15s ease;
}

.pagination-nyt .page-link:hover {
    background: #121212;
    color: #fff;
    border-color: #121212;
}

.pagination-nyt .page-item.active .page-link {
    background: #121212;
    color: #fff;
    border-color: #121212;
}

.pagination-nyt .page-item.disabled .page-link {
    color: #8a8a8a;
    background: #f1f1f1;
    border-color: #e6e6e6;
    opacity: 1;
}

.pagination-nyt .page-item[aria-label*="Previous"] .page-link::after {
    content: " Prev";
    font-size: 0.76rem;
    font-weight: 700;
    letter-spacing: 0.01em;
    margin-left: 4px;
}

.pagination-nyt .page-item[aria-label*="Next"] .page-link::before,
.pagination-nyt .page-link[aria-label*="Next"]::before {
    content: "Next ";
    font-size: 0.76rem;
    font-weight: 700;
    letter-spacing: 0.01em;
    margin-right: 4px;
}

.pagination-nyt .page-link[aria-label*="Previous"]::after {
    content: " Prev";
    font-size: 0.76rem;
    font-weight: 700;
    letter-spacing: 0.01em;
    margin-left: 4px;
}

.no-articles-nyt {
    text-align: center;
    padding: 60px 20px;
    color: #666;
    font-style: italic;
}

/* Dark Mode */
body.dark-mode .reporter-profile-nyt {
    background: #121212;
    color: #e0e0e0;
}

body.dark-mode .profile-masthead {
    border-bottom-color: #333;
}

body.dark-mode .masthead-date,
body.dark-mode .masthead-tagline {
    color: #888;
}

body.dark-mode .reporter-header-nyt {
    border-bottom-color: #444;
}

body.dark-mode .reporter-name {
    color: #e0e0e0;
}

body.dark-mode .reporter-model {
    color: #888;
}

body.dark-mode .reporter-bio {
    color: #aaa;
}

body.dark-mode .stats-bar-nyt {
    border-bottom-color: #333;
}

body.dark-mode .stat-number {
    color: #e0e0e0;
}

body.dark-mode .stat-label {
    color: #666;
}

body.dark-mode .stat-divider {
    background: #333;
}

body.dark-mode .specializations-section {
    border-bottom-color: #333;
}

body.dark-mode .section-header-nyt {
    color: #e0e0e0;
    border-bottom-color: #444;
}

body.dark-mode .spec-item {
    color: #5a9fd4;
}

body.dark-mode .section-header-row {
    border-bottom-color: #444;
}

body.dark-mode .articles-title {
    color: #e0e0e0;
}

body.dark-mode .external-link {
    color: #5a9fd4;
}

body.dark-mode .articles-list-nyt {
    border-top-color: #333;
}

body.dark-mode .article-item-nyt {
    border-bottom-color: #333;
}

body.dark-mode .article-item-nyt:hover {
    background: #1a1a1a;
}

body.dark-mode .article-item-nyt:hover .article-headline {
    color: #5a9fd4;
}

body.dark-mode .article-section {
    color: #5a9fd4;
}

body.dark-mode .article-date,
body.dark-mode .article-meta-bottom {
    color: #666;
}

body.dark-mode .article-headline {
    color: #e0e0e0;
}

body.dark-mode .article-summary {
    color: #aaa;
}

body.dark-mode .no-articles-nyt {
    color: #666;
}

body.dark-mode .pagination-nyt .page-link {
    background: #232323;
    color: #e0e0e0;
    border-color: #444;
}

body.dark-mode .pagination-nyt .page-link:hover {
    background: #0f0f0f;
    color: #fff;
    border-color: #5a5a5a;
}

body.dark-mode .pagination-nyt .page-item.active .page-link {
    background: #0f0f0f;
    color: #fff;
    border-color: #5a5a5a;
}

body.dark-mode .pagination-nyt .page-item.disabled .page-link {
    color: #808080;
    background: #2b2b2b;
    border-color: #3b3b3b;
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .reporter-profile-nyt {
        padding: 15px;
    }
    
    .masthead-title {
        font-size: 1.8rem;
    }
    
    .reporter-name {
        font-size: 1.6rem;
    }
    
    .stats-bar-nyt {
        gap: 20px;
    }
    
    .stat-divider {
        display: none;
    }
    
    .stat-number {
        font-size: 1.4rem;
    }
    
    .article-headline {
        font-size: 1.15rem;
    }

    .pagination-nyt .pagination {
        gap: 6px;
    }

    .pagination-nyt .page-link {
        min-width: 38px;
        height: 38px;
        padding: 0 10px;
        font-size: 0.92rem;
    }

    .pagination-nyt .page-item[aria-label*="Previous"] .page-link::after,
    .pagination-nyt .page-item[aria-label*="Next"] .page-link::before,
    .pagination-nyt .page-link[aria-label*="Previous"]::after,
    .pagination-nyt .page-link[aria-label*="Next"]::before {
        font-size: 0.72rem;
    }
}
</style>
