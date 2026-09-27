@php
    use Botble\Blog\Models\Post;
    use Botble\SeoHelper\Facades\SeoHelper;
    
    $pageNumber = max(1, (int) request()->input('page', 1));
    $categorySeoTitle = $category->name . ' - GenZ NewZ' . ($pageNumber > 1 ? ' - Page ' . $pageNumber : '');
    
    // SEO Title for Category
    SeoHelper::setTitle($categorySeoTitle);
    SeoHelper::setDescription('Latest ' . $category->name . ' news, updates, and insights for Gen Z. Read breaking stories and trending topics on GenZ NewZ.');
    
    // Get featured posts from this category
    $featuredPosts = Post::with(['slugable', 'categories', 'author'])
        ->whereHas('categories', function($q) use ($category) {
            $q->where('categories.id', $category->id);
        })
        ->where('is_featured', true)
        ->where('status', 'published')
        ->orderByDesc('created_at')
        ->take(3)
        ->get();
    
    // Subcategories are not rendered as page content any more: they live in the
    // header navigation, hanging off their parent beat.

    // Get all posts for this category (including children)
    $allCategoryIds = array_merge([$category->id], $category->activeChildren->pluck('id')->all());
    
    // Get posts if not provided by controller
    if (!isset($posts)) {
        $posts = Post::with(['slugable', 'categories', 'author'])
            ->whereHas('categories', function($q) use ($allCategoryIds) {
                $q->whereIn('categories.id', $allCategoryIds);
            })
            ->where('status', 'published')
            ->orderByDesc('created_at')
            ->paginate(12);
    }
@endphp

<section class="category-page">
    {{-- Category Header --}}
    <div class="category-header">
        <h1 class="category-title">{{ $category->name }}</h1>
        @if ($category->description)
            <p class="category-description">{{ $category->description }}</p>
        @endif
    </div>
    
    <div class="category-layout">
            <!-- Main Content -->
            <main class="category-main">
                {{-- Featured Posts --}}
                @if ($featuredPosts->count())
                <section class="featured-section">
                    <div class="featured-grid">
                        @if ($heroPost = $featuredPosts->first())
                            <article
                                class="featured-main"
                                onclick="window.location.href='{{ $heroPost->url }}'"
                                onkeydown="if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); window.location.href='{{ $heroPost->url }}'; }"
                                role="link"
                                tabindex="0"
                            >
                                @if ($heroPost->image)
                                    <img src="{{ RvMedia::getImageUrl($heroPost->image, 'large') }}" 
                                         alt="{{ $heroPost->name }}" 
                                         loading="eager"
                                         decoding="async"
                                         width="900"
                                         height="600"
                                         class="featured-main-image">
                                @endif
                                <div class="featured-main-content">
                                    <h2 class="featured-main-title">{{ $heroPost->name }}</h2>
                                    <p class="featured-main-excerpt">{{ Str::limit($heroPost->description, 150) }}</p>
                                    <div class="featured-main-meta">
                                        <a href="{{ $heroPost->author->url }}" class="author-link" onclick="event.stopPropagation();">{{ $heroPost->author->name ?? 'Staff' }}</a>
                                        <span>•</span>
                                        <span>{{ $heroPost->created_at->format('F j, Y') }}</span>
                                    </div>
                                </div>
                            </article>
                        @endif

                        @if ($featuredPosts->count() > 1)
                            <div class="featured-secondary-column">
                                @foreach ($featuredPosts->slice(1) as $post)
                                    <article
                                        class="featured-secondary"
                                        onclick="window.location.href='{{ $post->url }}'"
                                        onkeydown="if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); window.location.href='{{ $post->url }}'; }"
                                        role="link"
                                        tabindex="0"
                                    >
                                        @if ($post->image)
                                            <img src="{{ RvMedia::getImageUrl($post->image, 'medium') }}" 
                                                 alt="{{ $post->name }}" 
                                                 loading="lazy"
                                                 decoding="async"
                                                 width="600"
                                                 height="400"
                                                 class="featured-secondary-image">
                                        @endif
                                        <div class="featured-secondary-content">
                                            <h3 class="featured-secondary-title">{{ $post->name }}</h3>
                                            <div class="featured-secondary-meta">
                                                <a href="{{ $post->author->url }}" class="author-link-small" onclick="event.stopPropagation();">{{ $post->author->name ?? 'Staff' }}</a>
                                                <span>{{ $post->created_at->diffForHumans() }}</span>
                                            </div>
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </section>
                @endif
                
                {{-- All Posts List --}}
                <section class="posts-list">
                    <div class="section-header">
                        <h2 class="section-title">{{ __('All Articles') }}</h2>
                    </div>
                    
                    <div class="posts-container">
                        @foreach ($posts as $post)
                            @if (!$featuredPosts->contains('id', $post->id))
                                
                                {{-- TEXT-ONLY POST - FULL WIDTH --}}
                                @if ($post->format_type === 'text-only' && !$post->image)
                                <article class="post-item post-item-text-only">
                                    <div class="post-content-full">
                                        @if ($post->categories->count())
                                            <span class="post-category">{{ $post->categories->first()->name }}</span>
                                        @endif
                                        <h3 class="post-title-full">
                                            <a href="{{ $post->url }}">{{ $post->name }}</a>
                                        </h3>
                                        <p class="post-excerpt-full">{{ Str::limit($post->description, 200) }}</p>
                                        <div class="post-meta-full">
                                            <a href="{{ $post->author->url }}" class="author-link">{{ $post->author->name ?? 'GenZai' }}</a>
                                            <span>•</span>
                                            <span>{{ $post->created_at->format('F j, Y') }}</span>
                                            <span>•</span>
                                            <span>{{ $post->created_at->diffForHumans() }}</span>
                                        </div>
                                    </div>
                                </article>
                                
                                @else
                                {{-- REGULAR POST WITH IMAGE --}}
                                <article class="post-item {{ !$post->image ? 'post-item-no-image' : '' }}">
                                    @if ($post->image)
                                        <a href="{{ $post->url }}" class="post-thumb">
                                            <img src="{{ RvMedia::getImageUrl($post->image, 'medium') }}" 
                                                 alt="{{ $post->name }}"
                                                 loading="lazy"
                                                 decoding="async"
                                                 width="600"
                                                 height="400">
                                        </a>
                                    @endif
                                    <div class="post-content {{ !$post->image ? 'post-content-expanded' : '' }}">
                                        @if ($post->categories->count())
                                            <span class="post-category">{{ $post->categories->first()->name }}</span>
                                        @endif
                                        <h3 class="post-title">
                                            <a href="{{ $post->url }}">{{ $post->name }}</a>
                                        </h3>
                                        @if (!$post->image)
                                            <p class="post-excerpt post-excerpt-full">{{ Str::limit($post->description, 250) }}</p>
                                        @else
                                            <p class="post-excerpt">{{ Str::limit($post->description, 120) }}</p>
                                        @endif
                                        <div class="post-meta">
                                            <a href="{{ $post->author->url }}" class="author-link-small">{{ $post->author->name ?? 'Staff' }}</a>
                                            <span>•</span>
                                            <span>{{ $post->created_at->format('M j') }}</span>
                                        </div>
                                    </div>
                                </article>
                                @endif
                                
                            @endif
                        @endforeach
                    </div>
                    
                    {{-- Pagination --}}
                    @if ($posts->hasPages())
                    <div class="pagination-wrapper">
                        {!! $posts->links() !!}
                    </div>
                    @endif
                </section>
            </main>
            
            <!-- Sidebar -->
            <aside class="category-sidebar">
                @php
                    // Cached per category: trending is identical for every visitor
                    // and this otherwise runs an unbounded ORDER BY views scan on
                    // every category page view.
                    $trending = cache()->remember('category_trending_' . $category->id, 600, function () {
                        return Post::where('status', 'published')
                            ->orderByDesc('views')
                            ->take(5)
                            ->get();
                    });
                @endphp
                
                @if ($trending->count())
                <div class="sidebar-section">
                    <h4 class="sidebar-title">{{ __('Trending Now') }}</h4>
                    <div class="trending-list">
                        @foreach ($trending as $index => $post)
                        <div class="trending-item">
                            <span class="trending-number">{{ $index + 1 }}</span>
                            <div class="trending-content">
                                <a href="{{ $post->url }}" class="trending-title">{{ $post->name }}</a>
                                <a href="{{ $post->author->url }}" class="trending-author">{{ $post->author->name ?? 'Staff' }}</a>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </aside>
    </div>
</section>

<style>
.category-page {
    padding: 30px 20px;
    width: min(100%, 1200px);
    max-width: 1200px;
    margin: 0 auto;
    box-sizing: border-box;
}

.category-header {
    text-align: center;
    padding: 36px 0 28px;
    border-bottom: 1px solid #e2e2e2;
    margin: 0 auto 20px;
    max-width: 900px;
}

.category-title {
    font-family: 'Space Grotesk', sans-serif;
    font-size: clamp(2.1rem, 4.5vw, 2.7rem);
    font-weight: 700;
    letter-spacing: -0.8px;
    margin-bottom: 12px;
    color: #121212;
    overflow-wrap: anywhere;
    word-break: break-word;
}

.category-description {
    font-size: 1.04rem;
    line-height: 1.72;
    color: #3f3f3f;
    max-width: 820px;
    margin: 0 auto;
    padding: 0 10px;
}

.category-layout {
    display: grid;
    grid-template-columns: 1fr 300px;
    gap: 40px;
    align-items: start;
}

@media (max-width: 900px) {
    .category-layout {
        grid-template-columns: 1fr;
    }
    .category-sidebar {
        display: none;
    }
    .category-header {
        padding: 30px 0 24px;
    }
    .category-description {
        max-width: 700px;
    }
}

/* Featured Section */
.featured-section {
    margin-bottom: 40px;
}

.featured-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 25px;
    align-items: start;
}

.featured-secondary-column {
    display: grid;
    gap: 20px;
    align-content: start;
}

@media (max-width: 768px) {
    .featured-grid {
        grid-template-columns: 1fr;
    }
    .category-page {
        padding: 22px 14px;
    }
    .category-header {
        padding: 22px 0 20px;
        margin-bottom: 16px;
    }
    .category-description {
        font-size: 0.99rem;
        line-height: 1.65;
        max-width: 100%;
    }
}

.featured-main {
    cursor: pointer;
    transition: transform 0.2s;
    max-width: 100%;
    box-sizing: border-box;
}

.featured-main:hover {
    transform: translateY(-4px);
}

.featured-main:focus-visible,
.featured-secondary:focus-visible {
    outline: 3px solid rgba(50, 104, 145, 0.28);
    outline-offset: 3px;
}

.featured-main-image {
    width: 100%;
    height: 350px;
    object-fit: cover;
    border-radius: 12px;
    margin-bottom: 15px;
}

.featured-main-content,
.featured-secondary-content,
.post-content,
.post-content-full,
.post-content-expanded {
    min-width: 0;
}

.featured-main-title {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1.6rem;
    font-weight: 700;
    line-height: 1.2;
    margin-bottom: 10px;
    color: #121212;
    overflow-wrap: anywhere;
    word-break: break-word;
}

.featured-main-excerpt {
    font-size: 1rem;
    color: #555;
    line-height: 1.5;
    margin-bottom: 12px;
}

.featured-main-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    font-size: 0.85rem;
    color: #888;
    font-weight: 500;
}

.featured-secondary {
    cursor: pointer;
    display: grid;
    gap: 10px;
    padding-bottom: 20px;
    border-bottom: 1px solid #e2e2e2;
    max-width: 100%;
    box-sizing: border-box;
}

.featured-secondary-column .featured-secondary:last-child {
    padding-bottom: 0;
    border-bottom: none;
}

.featured-secondary-image {
    width: 100%;
    height: 150px;
    object-fit: cover;
    border-radius: 8px;
    margin-bottom: 10px;
}

.featured-secondary-title {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1.1rem;
    font-weight: 600;
    line-height: 1.3;
    margin-bottom: 6px;
    color: #121212;
    overflow-wrap: anywhere;
    word-break: break-word;
}

.featured-secondary-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    font-size: 0.8rem;
    color: #888;
}

/* Posts List */
.posts-list {
    margin-top: 40px;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
    padding-bottom: 12px;
    border-bottom: 3px solid #121212;
}

.section-title {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1.2rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.posts-container {
    display: flex;
    flex-direction: column;
    gap: 0;
}

/* REGULAR POST WITH IMAGE */
.post-item {
    display: grid;
    grid-template-columns: 200px 1fr;
    gap: 20px;
    padding: 25px 0;
    border-bottom: 1px solid #e2e2e2;
    align-items: start;
}

@media (max-width: 600px) {
    .post-item {
        grid-template-columns: 1fr;
    }
}

.post-thumb img {
    width: 100%;
    height: 130px;
    object-fit: cover;
    border-radius: 8px;
    transition: transform 0.2s;
}

.post-thumb:hover img {
    transform: scale(1.03);
}

.post-category {
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: #326891;
    margin-bottom: 8px;
    display: block;
}

.post-title {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1.2rem;
    font-weight: 600;
    line-height: 1.3;
    margin-bottom: 8px;
    overflow-wrap: anywhere;
    word-break: break-word;
}

.post-title a {
    color: #121212;
    text-decoration: none;
}

.post-title a:hover {
    color: #326891;
}

.post-excerpt {
    font-size: 0.95rem;
    color: #555;
    line-height: 1.5;
    margin-bottom: 10px;
}

.post-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    font-size: 0.8rem;
    color: #888;
    font-weight: 500;
}

/* TEXT-ONLY POST - FULL WIDTH */
.post-item-text-only {
    display: block;
    width: 100%;
    padding: 24px 20px;
    border: 1px solid #e6eaf1;
    border-radius: 18px;
    background: linear-gradient(180deg, #fafafa 0%, #fff 100%);
    margin: 0;
    box-sizing: border-box;
}

.post-content-full {
    max-width: 100%;
}

.text-only-badge {
    display: inline-block;
    padding: 4px 10px;
    background: linear-gradient(135deg, #FF006E 0%, #8338EC 100%);
    color: white;
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1px;
    border-radius: 12px;
    margin-bottom: 10px;
}

.post-title-full {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1.4rem;
    font-weight: 700;
    line-height: 1.25;
    margin-bottom: 12px;
    overflow-wrap: anywhere;
    word-break: break-word;
}

.post-title-full a {
    color: #121212;
    text-decoration: none;
}

.post-title-full a:hover {
    color: #326891;
}

.post-excerpt-full {
    font-size: 1.05rem;
    color: #444;
    line-height: 1.6;
    margin-bottom: 15px;
    max-width: 800px;
}

.post-meta-full {
    font-size: 0.9rem;
    color: #666;
    display: flex;
    gap: 12px;
    align-items: center;
    flex-wrap: wrap;
}

/* POST WITHOUT IMAGE - EXPANDED CONTENT */
.post-item-no-image {
    display: block;
    width: 100%;
}

.post-item-no-image .post-content-expanded {
    width: 100%;
    max-width: 100%;
}

.post-item-no-image .post-title {
    font-size: 1.25rem;
    margin-bottom: 12px;
}

.post-item-no-image .post-excerpt-full {
    font-size: 0.77rem;
    color: #444;
    line-height: 1.6;
    margin-bottom: 15px;
    max-width: 100%;
    display: -webkit-box;
    -webkit-line-clamp: 8;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Author Links */
.author-link {
    color: #326891;
    text-decoration: none;
    font-weight: 600;
}

.author-link:hover {
    text-decoration: underline;
}

.author-link-small {
    color: #326891;
    text-decoration: none;
    font-weight: 500;
}

.author-link-small:hover {
    text-decoration: underline;
}

/* Sidebar */
.category-sidebar {
    padding-left: 20px;
    border-left: 1px solid #e2e2e2;
    min-width: 0;
}

.sidebar-section {
    margin-bottom: 30px;
}

.sidebar-title {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 0.9rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 15px;
}

.trending-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.trending-item {
    display: flex;
    gap: 12px;
    align-items: flex-start;
}

.trending-number {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1.8rem;
    font-weight: 800;
    background: linear-gradient(135deg, #FF006E 0%, #FB5607 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    line-height: 1;
    min-width: 25px;
}

.trending-content {
    display: flex;
    flex-direction: column;
    gap: 4px;
    min-width: 0;
}

.trending-title {
    font-size: 0.9rem;
    line-height: 1.4;
    color: #121212;
    text-decoration: none;
    font-weight: 500;
    overflow-wrap: anywhere;
}

.trending-title:hover {
    color: #326891;
    text-decoration: underline;
}

.trending-author {
    font-size: 0.75rem;
    color: #666;
    text-decoration: none;
}

.trending-author:hover {
    color: #326891;
    text-decoration: underline;
}

/* Pagination - Fixed Styles */
.pagination-wrapper {
    margin-top: 40px;
    display: flex;
    justify-content: center;
}

.pagination-wrapper nav {
    display: flex;
}

.pagination-wrapper .pagination {
    display: flex;
    list-style: none;
    padding: 0;
    margin: 0;
    gap: 8px;
    flex-wrap: wrap;
    justify-content: center;
}

.pagination-wrapper .page-item {
    display: inline-block;
}

.pagination-wrapper .page-link {
    display: block;
    padding: 10px 16px;
    border-radius: 6px;
    font-size: 0.9rem;
    font-weight: 500;
    text-decoration: none;
    background: #f5f5f5;
    color: #333;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
}

.pagination-wrapper .page-link:hover {
    background: #121212;
    color: #fff;
}

.pagination-wrapper .page-item.active .page-link {
    background: #121212;
    color: #fff;
}

.pagination-wrapper .page-item.disabled .page-link {
    color: #ccc;
    background: #f5f5f5;
    cursor: not-allowed;
}

body.dark-mode .category-header,
body.dark-mode .featured-secondary,
body.dark-mode .post-item {
    border-color: #333;
}

body.dark-mode .category-title,
body.dark-mode .featured-main-title,
body.dark-mode .featured-secondary-title,
body.dark-mode .post-title a,
body.dark-mode .post-title-full a,
body.dark-mode .trending-title,
body.dark-mode .sidebar-title,
body.dark-mode .section-title {
    color: #f3f4f6;
}

body.dark-mode .category-description,
body.dark-mode .featured-main-excerpt,
body.dark-mode .post-excerpt,
body.dark-mode .post-excerpt-full,
body.dark-mode .featured-main-meta,
body.dark-mode .featured-secondary-meta,
body.dark-mode .post-meta,
body.dark-mode .post-meta-full,
body.dark-mode .trending-author {
    color: #aeb4bf;
}

body.dark-mode .pagination-wrapper .page-link {
    background: #1d1d1d;
    color: #e5e7eb;
}

body.dark-mode .pagination-wrapper .page-link:hover,
body.dark-mode .pagination-wrapper .page-item.active .page-link {
    background: #2f5e94;
    color: #fff;
}

body.dark-mode .post-item-text-only {
    background: linear-gradient(180deg, #181818 0%, #111 100%);
    border-color: #333;
}

body.dark-mode .category-sidebar {
    border-left-color: #333;
}

@media (max-width: 600px) {
    .featured-main-image {
        height: clamp(220px, 62vw, 300px);
    }

    .post-item-text-only {
        width: 100%;
        padding: 18px 16px;
    }

    .section-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }

    .featured-main-title {
        font-size: 1.35rem;
    }

    .post-title,
    .post-title-full {
        font-size: 1.12rem;
    }
}
</style>
