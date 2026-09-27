@php
    use App\Services\EditorialProfileService;
    use Botble\Blog\Models\Post;
    use Botble\Media\Facades\RvMedia;
    use Botble\SeoHelper\Facades\SeoHelper;

    $pageNumber = max(1, (int) request()->input('page', 1));
    $editorialProfile = app(EditorialProfileService::class)->profileFor($author->name);
    $authorSeoTitle = 'Articles by ' . $author->name . ' - GenZ NewZ' . ($pageNumber > 1 ? ' - Page ' . $pageNumber : '');
    
    // Get post count first for SEO
    $postCount = Post::where('author_id', $author->id)
        ->where('author_type', get_class($author))
        ->where('status', 'published')
        ->count();
    
    // SEO Title for Author
    SeoHelper::setTitle($authorSeoTitle);
    SeoHelper::setDescription($editorialProfile['description'] . ' Read ' . $postCount . ' published stories on GenZ NewZ.');
    
    // Get author's posts
    $authorPosts = Post::with(['slugable', 'categories'])
        ->where('author_id', $author->id)
        ->where('author_type', get_class($author))
        ->where('status', 'published')
        ->orderByDesc('created_at')
        ->paginate(12);
    
    // Get total views
    $totalViews = Post::where('author_id', $author->id)
        ->where('author_type', get_class($author))
        ->where('status', 'published')
        ->sum('views');

    $authorSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'ProfilePage',
        'url' => $author->url,
        'name' => $author->name . ' | GenZ NewZ',
        'mainEntity' => array_filter([
            '@type' => 'Person',
            'name' => $author->name,
            'jobTitle' => $editorialProfile['job_title'],
            'description' => $editorialProfile['description'],
            'url' => $author->url,
            'image' => $author->avatar ? RvMedia::getImageUrl($author->avatar, 'thumb') : null,
            'sameAs' => !empty($editorialProfile['same_as']) ? $editorialProfile['same_as'] : null,
        ]),
    ];
@endphp

<script type="application/ld+json">
{!! json_encode($authorSchema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) !!}
</script>

<section class="author-page">
    {{-- Author Header --}}
    <div class="author-header">
            <div class="author-profile">
                @if ($author->avatar)
                    @include('theme::partials.image', [
                        'image' => $author->avatar,
                        'alt' => $author->name,
                        'size' => 'thumb',
                        'class' => 'author-avatar',
                        'imgClass' => 'author-avatar',
                        'lazy' => false,
                        'loading' => 'eager'
                    ])
                @else
                    <div class="author-avatar-placeholder">
                        {{ strtoupper(substr($author->name, 0, 1)) }}
                    </div>
                @endif
                
                <div class="author-info">
                    <h1 class="author-name">{{ $author->name }}</h1>
                    <p class="author-role">{{ $editorialProfile['job_title'] }}</p>
                    @if ($author->email)
                        <p class="author-email">{{ $author->email }}</p>
                    @endif
                    <p class="author-bio">{{ $author->description ?: $editorialProfile['description'] }}</p>

                    @if (!empty($editorialProfile['expertise']))
                        <div class="author-expertise">
                            @foreach ($editorialProfile['expertise'] as $expertise)
                                <span class="author-expertise-chip">{{ $expertise }}</span>
                            @endforeach
                        </div>
                    @endif
                    
                    <div class="author-stats">
                        <div class="stat">
                            <span class="stat-number">{{ $postCount }}</span>
                            <span class="stat-label">Articles</span>
                        </div>
                        <div class="stat">
                            <span class="stat-number">{{ number_format($totalViews) }}</span>
                            <span class="stat-label">Total Views</span>
                        </div>
                    </div>

                    @if (!empty($editorialProfile['same_as']))
                        <div class="author-links">
                            @foreach ($editorialProfile['same_as'] as $profileUrl)
                                <a href="{{ $profileUrl }}" target="_blank" rel="noopener noreferrer">{{ parse_url($profileUrl, PHP_URL_HOST) }}</a>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
        
        {{-- Author's Articles --}}
        <div class="author-content">
            <div class="section-header">
                <h2 class="section-title">{{ __('Articles by ') }} {{ $author->name }}</h2>
            </div>
            
            @if ($authorPosts->count())
                <div class="author-posts-grid">
                    @foreach ($authorPosts as $post)
                        @if ($post->format_type === 'text-only' && !$post->image)
                            {{-- Text-only post --}}
                            <article class="author-post author-post-text">
                                @if ($post->categories->count())
                                    <span class="post-category">{{ $post->categories->first()->name }}</span>
                                @endif
                                <h3 class="post-title">
                                    <a href="{{ $post->url }}">{{ $post->name }}</a>
                                </h3>
                                <p class="post-excerpt">{{ Str::limit($post->description, 150) }}</p>
                                <div class="post-meta">
                                    <span>{{ $post->created_at->format('F j, Y') }}</span>
                                    <span>•</span>
                                    <span>{{ $post->views }} views</span>
                                </div>
                            </article>
                        @else
                            {{-- Regular post --}}
                            <article class="author-post">
                                @if ($post->image)
                                    <a href="{{ $post->url }}" class="post-thumb">
                                        @include('theme::partials.image', [
                                            'image' => $post->image,
                                            'alt' => $post->name,
                                            'size' => 'medium',
                                            'class' => 'post-thumb-image',
                                            'imgClass' => 'post-thumb-image',
                                            'lazy' => true
                                        ])
                                    </a>
                                @endif
                                <div class="post-content">
                                    @if ($post->categories->count())
                                        <span class="post-category">{{ $post->categories->first()->name }}</span>
                                    @endif
                                    <h3 class="post-title">
                                        <a href="{{ $post->url }}">{{ $post->name }}</a>
                                    </h3>
                                    <p class="post-excerpt">{{ Str::limit($post->description, 100) }}</p>
                                    <div class="post-meta">
                                        <span>{{ $post->created_at->format('M j, Y') }}</span>
                                        <span>•</span>
                                        <span>{{ $post->views }} views</span>
                                    </div>
                                </div>
                            </article>
                        @endif
                    @endforeach
                </div>
                
                {{-- Pagination --}}
                @if ($authorPosts->hasPages())
                <div class="pagination-wrapper">
                    {!! $authorPosts->links() !!}
                </div>
                @endif
            @else
                <div class="no-posts">
                    <p>{{ __('No articles published yet.') }}</p>
                </div>
            @endif
        </div>
</section>

<style>
.author-page {
    padding: 40px 20px;
    max-width: 1000px;
    margin: 0 auto;
}

.author-header {
    padding: 40px;
    background: linear-gradient(135deg, #f8f9fa 0%, #fff 100%);
    border-radius: 20px;
    margin-bottom: 50px;
    border: 1px solid #e2e2e2;
}

.author-profile {
    display: flex;
    gap: 30px;
    align-items: center;
}

@media (max-width: 600px) {
    .author-profile {
        flex-direction: column;
        text-align: center;
    }
}

.author-avatar {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    object-fit: cover;
    border: 4px solid #fff;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
}

.author-avatar-placeholder {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    background: linear-gradient(135deg, #FF006E 0%, #FB5607 50%, #FFBE0B 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: 'Space Grotesk', sans-serif;
    font-size: 4rem;
    font-weight: 700;
    color: white;
    border: 4px solid #fff;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
}

.author-info {
    flex: 1;
}

.author-name {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 5px;
    color: #121212;
    overflow-wrap: anywhere;
    word-break: break-word;
}

.author-email {
    font-size: 1rem;
    color: #666;
    margin-bottom: 12px;
}

.author-role {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 0.95rem;
    font-weight: 600;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: #7a5f30;
    margin-bottom: 10px;
}

.author-bio {
    font-size: 1.1rem;
    color: #444;
    line-height: 1.6;
    margin-bottom: 20px;
    max-width: 600px;
}

.author-expertise {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 20px;
}

.author-expertise-chip {
    display: inline-flex;
    align-items: center;
    border: 1px solid #d7cec0;
    background: #f6f1e8;
    color: #4b3f2e;
    border-radius: 999px;
    padding: 6px 12px;
    font-size: 0.78rem;
    font-weight: 600;
    letter-spacing: 0.03em;
}

.author-stats {
    display: flex;
    gap: 30px;
}

.stat {
    display: flex;
    flex-direction: column;
}

.stat-number {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 2rem;
    font-weight: 700;
    background: linear-gradient(135deg, #FF006E 0%, #FB5607 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.stat-label {
    font-size: 0.85rem;
    color: #666;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.author-links {
    display: flex;
    flex-wrap: wrap;
    gap: 14px;
    margin-top: 20px;
}

.author-links a {
    color: #326891;
    font-family: 'Space Grotesk', sans-serif;
    font-size: 0.9rem;
    text-decoration: none;
}

.author-links a:hover {
    text-decoration: underline;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding-bottom: 15px;
    border-bottom: 3px solid #121212;
}

.section-title {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1.4rem;
    font-weight: 700;
}

.author-posts-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 25px;
}

@media (max-width: 768px) {
    .author-page {
        padding: 20px 12px;
        max-width: 100%;
        overflow-x: hidden;
    }

    .author-page .container {
        width: 100%;
        max-width: 100%;
        padding-left: 0;
        padding-right: 0;
        margin-left: 0;
        margin-right: 0;
    }

    .author-posts-grid {
        grid-template-columns: 1fr;
        max-width: 100%;
    }

    .author-post {
        display: block;
        padding: 14px;
        max-width: 100%;
        box-sizing: border-box;
    }

    .post-thumb,
    .post-thumb .optimized-image-wrapper,
    .post-thumb .post-thumb-image,
    .post-thumb img {
        width: 100%;
        max-width: 100%;
    }

    .post-thumb img {
        height: auto;
    }

    .post-content {
        margin-top: 10px;
        max-width: 100%;
    }

    .pagination-wrapper .pagination {
        gap: 6px;
    }

    .pagination-wrapper .page-link {
        min-width: 38px;
        height: 38px;
        padding: 0 10px;
        font-size: 0.92rem;
    }

    .pagination-wrapper .page-item[aria-label*="Previous"] .page-link::after,
    .pagination-wrapper .page-item[aria-label*="Next"] .page-link::before,
    .pagination-wrapper .page-link[aria-label*="Previous"]::after,
    .pagination-wrapper .page-link[aria-label*="Next"]::before {
        font-size: 0.72rem;
    }
}

.author-post {
    display: flex;
    gap: 15px;
    align-items: flex-start;
    overflow: hidden;
    padding: 20px;
    background: #fafafa;
    border-radius: 12px;
    transition: all 0.2s;
}

.author-post:hover {
    background: #f0f0f0;
    transform: translateY(-2px);
}

.author-post-text {
    flex-direction: column;
    background: linear-gradient(135deg, #f8f5ff 0%, #fafafa 100%);
    border-left: 4px solid #8338EC;
}

.post-thumb {
    display: block;
    flex-shrink: 0;
    width: 120px;
    min-width: 120px;
}

.post-thumb .optimized-image-wrapper,
.post-thumb .post-thumb-image {
    width: 120px;
    max-width: 120px;
}

.post-thumb .optimized-image {
    border-radius: 8px;
    overflow: hidden;
}

.post-thumb img {
    width: 120px;
    height: 90px;
    object-fit: cover;
    border-radius: 8px;
}

.post-content {
    flex: 1;
    min-width: 0;
}

.text-only-badge {
    display: inline-block;
    padding: 3px 8px;
    background: linear-gradient(135deg, #FF006E 0%, #8338EC 100%);
    color: white;
    font-size: 0.65rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-radius: 10px;
    margin-bottom: 8px;
}

.post-category {
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: #326891;
    margin-bottom: 6px;
    display: block;
}

.post-title {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1.1rem;
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
    font-size: 0.9rem;
    color: #555;
    line-height: 1.4;
    margin-bottom: 10px;
    overflow-wrap: anywhere;
}

.post-meta {
    font-size: 0.8rem;
    color: #888;
    display: flex;
    gap: 10px;
}

.no-posts {
    text-align: center;
    padding: 60px 20px;
    color: #666;
    font-size: 1.1rem;
}

.pagination-wrapper {
    margin-top: 40px;
    display: flex;
    justify-content: center;
    width: 100%;
}

.pagination-wrapper nav {
    display: flex;
    width: 100%;
    justify-content: center;
}

.pagination-wrapper .pagination {
    display: flex;
    align-items: center;
    list-style: none;
    padding: 0;
    margin: 0;
    gap: 8px;
    flex-wrap: wrap;
    justify-content: center;
}

.pagination-wrapper .page-item {
    display: flex;
    align-items: center;
    justify-content: center;
}

.pagination-wrapper .page-link {
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
    background: #fff;
    color: #1a1a1a;
    border: 1px solid #d7d7d7;
    cursor: pointer;
    transition: all 0.15s ease;
    box-sizing: border-box;
}

.pagination-wrapper .page-link:hover {
    background: #121212;
    color: #fff;
    border-color: #121212;
}

.pagination-wrapper .page-item.active .page-link {
    background: #121212;
    color: #fff;
    border-color: #121212;
}

.pagination-wrapper .page-item.disabled .page-link {
    color: #8a8a8a;
    background: #f1f1f1;
    border-color: #e6e6e6;
    opacity: 1;
    cursor: not-allowed;
}

.pagination-wrapper .page-item[aria-label*="Previous"] .page-link::after {
    content: " Prev";
    font-size: 0.76rem;
    font-weight: 700;
    letter-spacing: 0.01em;
    margin-left: 4px;
}

.pagination-wrapper .page-item[aria-label*="Next"] .page-link::before,
.pagination-wrapper .page-link[aria-label*="Next"]::before {
    content: "Next ";
    font-size: 0.76rem;
    font-weight: 700;
    letter-spacing: 0.01em;
    margin-right: 4px;
}

.pagination-wrapper .page-link[aria-label*="Previous"]::after {
    content: " Prev";
    font-size: 0.76rem;
    font-weight: 700;
    letter-spacing: 0.01em;
    margin-left: 4px;
}

/* Dark Mode Styles */
body.dark-mode .author-page {
    color: #e0e0e0;
}

body.dark-mode .author-header {
    background: linear-gradient(135deg, #2a2a2a 0%, #1a1a1a 100%);
    border-color: #444;
}

body.dark-mode .author-name {
    color: #f0f0f0;
}

body.dark-mode .author-email {
    color: #aaa;
}

body.dark-mode .author-bio {
    color: #ccc;
}

body.dark-mode .stat-label {
    color: #888;
}

body.dark-mode .section-header {
    border-bottom-color: #555;
}

body.dark-mode .section-title {
    color: #f0f0f0;
}

body.dark-mode .author-post {
    background: #2a2a2a;
}

body.dark-mode .author-post:hover {
    background: #333;
}

body.dark-mode .author-post-text {
    background: linear-gradient(135deg, #2d2840 0%, #2a2a2a 100%);
    border-left-color: #9b6dff;
}

body.dark-mode .post-title a {
    color: #f0f0f0;
}

body.dark-mode .post-title a:hover {
    color: #5a9fd4;
}

body.dark-mode .post-excerpt {
    color: #aaa;
}

body.dark-mode .post-meta {
    color: #888;
}

body.dark-mode .post-category {
    color: #5a9fd4;
}

body.dark-mode .no-posts {
    color: #888;
}

body.dark-mode .pagination-wrapper .page-link {
    background: #232323;
    color: #e0e0e0;
    border-color: #444;
}

body.dark-mode .pagination-wrapper .page-link:hover {
    background: #0f0f0f;
    color: #fff;
    border-color: #5a5a5a;
}

body.dark-mode .pagination-wrapper .page-item.active .page-link {
    background: #0f0f0f;
    color: #fff;
    border-color: #5a5a5a;
}

body.dark-mode .pagination-wrapper .page-item.disabled .page-link {
    color: #808080;
    background: #2b2b2b;
    border-color: #3b3b3b;
}
</style>
