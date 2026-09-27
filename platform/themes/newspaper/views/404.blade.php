@php
    SeoHelper::setTitle('Page Not Found - 404 Error | GenZ NewZ');
    SeoHelper::setDescription('Sorry, the page you are looking for could not be found. Explore our latest news and articles.');
    $searchPageUrl = \Illuminate\Support\Facades\Route::has('public.search') ? route('public.search') : url('/search');
    
    // Get popular posts for suggestions. Cached: this template also renders on
    // scrapers' random-URL scans, so an uncached query here turns every 404 into
    // two database hits.
    $popularPosts = cache()->remember('error404_popular_posts', 600, function () {
        return Botble\Blog\Models\Post::where('status', 'published')
            ->orderByDesc('views')
            ->take(6)
            ->get();
    });
    
    // Get categories
    $categories = cache()->remember('error404_categories', 600, function () {
        return Botble\Blog\Models\Category::where('status', 'published')
            ->take(8)
            ->get();
    });
@endphp

@extends(Theme::getThemeNamespace('layouts.default'))

@section('content')
<div class="error-page-container">
    <div class="error-content">
        <div class="error-code">404</div>
        <h1 class="error-title">Page Not Found</h1>
        <p class="error-message">
            Sorry, the page you are looking for doesn't exist or has been moved.
        </p>
        
        <div class="error-actions">
            <a href="{{ route('public.single') }}" class="btn-home">
                <i class="fa fa-home"></i> Back to Homepage
            </a>
            <a href="{{ $searchPageUrl }}" class="btn-search">
                <i class="fa fa-search"></i> Search Articles
            </a>
        </div>
        
        <div class="search-box-404">
            <form action="{{ $searchPageUrl }}" method="GET">
                <input type="text" name="q" placeholder="Search for articles..." class="search-input" required>
                <button type="submit" class="search-btn">
                    <i class="fa fa-search"></i>
                </button>
            </form>
        </div>
    </div>
    
    @if($popularPosts->count() > 0)
    <div class="suggested-content">
        <h2>Popular Articles You Might Like</h2>
        <div class="suggested-grid">
            @foreach($popularPosts as $post)
            <article class="suggested-card">
                @if($post->image)
                <a href="{{ $post->url }}" class="suggested-image">
                    @include('theme::partials.image', [
                        'image' => $post->image,
                        'alt' => $post->name,
                        'size' => 'medium',
                        'class' => 'suggested-image',
                        'imgClass' => 'suggested-image',
                        'lazy' => true
                    ])
                </a>
                @endif
                <div class="suggested-body">
                    @if($post->categories->first())
                    <span class="suggested-category">{{ $post->categories->first()->name }}</span>
                    @endif
                    <h3><a href="{{ $post->url }}">{{ $post->name }}</a></h3>
                    <span class="suggested-meta">{{ $post->views }} views</span>
                </div>
            </article>
            @endforeach
        </div>
    </div>
    @endif
    
    @if($categories->count() > 0)
    <div class="browse-categories">
        <h2>Browse by Category</h2>
        <div class="category-list">
            @foreach($categories as $category)
            <a href="{{ $category->url }}" class="category-link">
                {{ $category->name }}
            </a>
            @endforeach
        </div>
    </div>
    @endif
</div>

<style>
.error-page-container {
    max-width: 1000px;
    margin: 0 auto;
    padding: 60px 20px;
    text-align: center;
}

.error-content {
    margin-bottom: 60px;
}

.error-code {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 8rem;
    font-weight: 800;
    background: linear-gradient(135deg, #FF006E 0%, #FB5607 50%, #FFBE0B 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    line-height: 1;
    margin-bottom: 20px;
}

.error-title {
    font-family: Georgia, 'Times New Roman', serif;
    font-size: 2.5rem;
    font-weight: 700;
    color: #121212;
    margin-bottom: 15px;
}

.error-message {
    font-size: 1.1rem;
    color: #666;
    margin-bottom: 30px;
    max-width: 500px;
    margin-left: auto;
    margin-right: auto;
}

.error-actions {
    display: flex;
    gap: 15px;
    justify-content: center;
    margin-bottom: 40px;
    flex-wrap: wrap;
}

.btn-home, .btn-search {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 24px;
    border-radius: 6px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s;
}

.btn-home {
    background: #121212;
    color: #fff;
}

.btn-home:hover {
    background: #326891;
    transform: translateY(-2px);
}

.btn-search {
    background: #f5f5f5;
    color: #121212;
    border: 2px solid #121212;
}

.btn-search:hover {
    background: #121212;
    color: #fff;
}

.search-box-404 {
    max-width: 500px;
    margin: 0 auto;
}

.search-box-404 form {
    display: flex;
    gap: 10px;
}

.search-input {
    flex: 1;
    padding: 14px 18px;
    border: 2px solid #e2e2e2;
    border-radius: 6px;
    font-size: 1rem;
    transition: border-color 0.2s;
}

.search-input:focus {
    outline: none;
    border-color: #326891;
}

.search-btn {
    padding: 14px 20px;
    background: #121212;
    color: #fff;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    transition: background 0.2s;
}

.search-btn:hover {
    background: #326891;
}

/* Suggested Content */
.suggested-content {
    margin-bottom: 60px;
}

.suggested-content h2 {
    font-family: Georgia, 'Times New Roman', serif;
    font-size: 1.5rem;
    margin-bottom: 25px;
    color: #121212;
}

.suggested-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 25px;
    text-align: left;
}

.suggested-card {
    background: #fff;
    border: 1px solid #e2e2e2;
    border-radius: 8px;
    overflow: hidden;
    transition: all 0.2s;
}

.suggested-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
}

.suggested-image img {
    width: 100%;
    height: 160px;
    object-fit: cover;
}

.suggested-body {
    padding: 15px;
}

.suggested-category {
    display: inline-block;
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    color: #326891;
    margin-bottom: 8px;
}

.suggested-body h3 {
    font-size: 1rem;
    font-weight: 600;
    line-height: 1.4;
    margin: 0 0 10px;
}

.suggested-body h3 a {
    color: #121212;
    text-decoration: none;
}

.suggested-body h3 a:hover {
    color: #326891;
}

.suggested-meta {
    font-size: 0.8rem;
    color: #888;
}

/* Browse Categories */
.browse-categories h2 {
    font-family: Georgia, 'Times New Roman', serif;
    font-size: 1.5rem;
    margin-bottom: 25px;
    color: #121212;
}

.category-list {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    justify-content: center;
}

.category-link {
    display: inline-block;
    padding: 10px 20px;
    background: #f5f5f5;
    color: #121212;
    text-decoration: none;
    border-radius: 20px;
    font-weight: 500;
    transition: all 0.2s;
}

.category-link:hover {
    background: #121212;
    color: #fff;
}

/* Dark Mode */
body.dark-mode .error-title {
    color: #f0f0f0;
}

body.dark-mode .error-message {
    color: #aaa;
}

body.dark-mode .btn-search {
    background: #2a2a2a;
    color: #e0e0e0;
    border-color: #444;
}

body.dark-mode .btn-search:hover {
    background: #444;
}

body.dark-mode .search-input {
    background: #2a2a2a;
    border-color: #444;
    color: #e0e0e0;
}

body.dark-mode .suggested-card {
    background: #1a1a1a;
    border-color: #333;
}

body.dark-mode .suggested-body h3 a {
    color: #e0e0e0;
}

body.dark-mode .category-link {
    background: #2a2a2a;
    color: #e0e0e0;
}

body.dark-mode .category-link:hover {
    background: #444;
}

/* Responsive */
@media (max-width: 768px) {
    .error-code {
        font-size: 5rem;
    }
    
    .error-title {
        font-size: 1.8rem;
    }
    
    .search-box-404 form {
        flex-direction: column;
    }
    
    .suggested-grid {
        grid-template-columns: 1fr;
    }
}
</style>
@endsection
