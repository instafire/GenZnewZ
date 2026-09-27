@php
    use Botble\SeoHelper\Facades\SeoHelper;
    
    // Set SEO for pages
    SeoHelper::setTitle($page->name . ' | GenZ NewZ');
    if ($page->description) {
        SeoHelper::setDescription($page->description);
    }
    
    // Template routing for special pages
    $template = $page->template;
    
    // Map page slugs to templates
    $slugTemplateMap = [
        'contactus' => 'contact',
        'terms-of-use' => 'terms',
        'privacy-policy' => 'privacy',
        'cookies-are-stupid' => 'cookies',
    ];
    
    // Check if current page slug matches a template
    $currentSlug = request()->route('slug') ?? request()->segment(2);
    if (isset($slugTemplateMap[$currentSlug])) {
        $template = $slugTemplateMap[$currentSlug];
    }
    
    // Route to appropriate template
    if ($template && in_array($template, ['register', 'login', 'contact', 'terms', 'privacy', 'cookies'])) {
        return Theme::scope('templates.' . $template)->render();
    }
@endphp

@extends(Theme::getThemeNamespace('layouts.default'))

@section('content')
<div class="nyt-page-container">
    <article class="nyt-page-article">
        <header class="nyt-page-header">
            <span class="nyt-page-kicker">Information</span>
            <h1 class="nyt-page-title">{{ $page->name }}</h1>
            @if($page->description)
                <p class="nyt-page-summary">{{ $page->description }}</p>
            @endif
            
            <div class="nyt-page-meta">
                <time class="nyt-page-date">
                    Updated {{ $page->updated_at->format('F j, Y') }}
                </time>
            </div>
        </header>

        @if ($page->image)
            <figure class="nyt-page-figure">
                @include('theme::partials.image', [
                    'image' => $page->image,
                    'alt' => $page->name,
                    'size' => 'large',
                    'class' => 'page-featured-image',
                    'imgClass' => 'page-featured-image',
                    'lazy' => false,
                    'loading' => 'eager'
                ])
            </figure>
        @endif
        
        <div class="nyt-page-body">
            {!! $page->content !!}
        </div>
        
        <footer class="nyt-page-footer">
            <div class="page-share">
                <span class="share-label">Share this page</span>
                <div class="share-buttons">
                    <a href="https://twitter.com/intent/tweet?url={{ urlencode(url()->current()) }}&text={{ urlencode($page->name) }}" 
                       target="_blank" rel="noopener" class="share-btn twitter" aria-label="Share on Twitter">
                        <svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                    </a>
                    <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(url()->current()) }}" 
                       target="_blank" rel="noopener" class="share-btn facebook" aria-label="Share on Facebook">
                        <svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                    </a>
                    <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ urlencode(url()->current()) }}" 
                       target="_blank" rel="noopener" class="share-btn linkedin" aria-label="Share on LinkedIn">
                        <svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                    </a>
                    <button class="share-btn copy" onclick="navigator.clipboard.writeText(window.location.href); this.classList.add('copied'); setTimeout(() => this.classList.remove('copied'), 2000);" aria-label="Copy link">
                        <svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M16 1H4c-1.1 0-2 .9-2 2v14h2V3h12V1zm3 4H8c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H8V7h11v14z"/></svg>
                    </button>
                </div>
            </div>
        </footer>
    </article>
    
    @if(isset($relatedPages) && $relatedPages->count() > 0)
    <aside class="nyt-page-related">
        <h3>Related Pages</h3>
        <div class="related-grid">
            @foreach($relatedPages as $relatedPage)
            <a href="{{ $relatedPage->url }}" class="related-card">
                <span class="related-title">{{ $relatedPage->name }}</span>
                <span class="related-arrow">→</span>
            </a>
            @endforeach
        </div>
    </aside>
    @endif
</div>

<style>
/* NYT Page Container */
.nyt-page-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 40px 20px 80px;
    font-family: Georgia, 'Times New Roman', serif;
}

/* Page Article */
.nyt-page-article {
    margin-bottom: 60px;
}

/* Page Header */
.nyt-page-header {
    text-align: center;
    padding-bottom: 32px;
    border-bottom: 1px solid #e2e2e2;
    margin-bottom: 40px;
}

.nyt-page-kicker {
    display: block;
    font-family: 'Space Grotesk', sans-serif;
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 3px;
    color: #666;
    margin-bottom: 16px;
}

.nyt-page-title {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 2.75rem;
    font-weight: 700;
    color: #121212;
    margin: 0 0 20px 0;
    line-height: 1.15;
}

.nyt-page-summary {
    font-size: 1.25rem;
    line-height: 1.5;
    color: #555;
    font-style: italic;
    margin: 0 0 24px 0;
    max-width: 600px;
    margin-left: auto;
    margin-right: auto;
}

.nyt-page-meta {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 0.85rem;
    color: #888;
}

.nyt-page-date {
    text-transform: uppercase;
    letter-spacing: 1px;
}

/* Featured Image */
.nyt-page-figure {
    margin: 0 0 40px 0;
}

.page-featured-image {
    width: 100%;
    max-height: 500px;
    object-fit: cover;
    border-radius: 8px;
}

/* Page Body */
.nyt-page-body {
    font-size: 1.125rem;
    line-height: 1.8;
    color: #333;
}

.nyt-page-body p {
    margin: 0 0 1.5em 0;
}

.nyt-page-body h2 {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1.5rem;
    font-weight: 600;
    color: #121212;
    margin: 2em 0 0.75em 0;
    padding-bottom: 0.5em;
    border-bottom: 2px solid #121212;
}

.nyt-page-body h3 {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1.25rem;
    font-weight: 600;
    color: #333;
    margin: 1.75em 0 0.75em 0;
}

.nyt-page-body h4 {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1.1rem;
    font-weight: 600;
    color: #444;
    margin: 1.5em 0 0.5em 0;
}

.nyt-page-body ul,
.nyt-page-body ol {
    margin: 1.25em 0;
    padding-left: 2em;
}

.nyt-page-body li {
    margin-bottom: 0.5em;
    line-height: 1.7;
}

.nyt-page-body a {
    color: #326891;
    text-decoration: underline;
    text-underline-offset: 2px;
}

.nyt-page-body a:hover {
    color: #121212;
}

.nyt-page-body blockquote {
    margin: 2em 0;
    padding: 1.5em 2em;
    border-left: 4px solid #121212;
    background: #f8f8f8;
    font-style: italic;
}

.nyt-page-body blockquote p:last-child {
    margin-bottom: 0;
}

.nyt-page-body strong {
    font-weight: 700;
    color: #121212;
}

/* Page Footer */
.nyt-page-footer {
    margin-top: 60px;
    padding-top: 40px;
    border-top: 1px solid #e2e2e2;
}

.page-share {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 16px;
}

.share-label {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 2px;
    color: #666;
}

.share-buttons {
    display: flex;
    gap: 12px;
}

.share-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 44px;
    height: 44px;
    border: 1px solid #ddd;
    border-radius: 50%;
    color: #666;
    background: #fff;
    transition: all 0.2s;
    cursor: pointer;
}

.share-btn:hover {
    border-color: #121212;
    background: #121212;
    color: #fff;
}

.share-btn.copied {
    background: #4caf50;
    border-color: #4caf50;
    color: #fff;
}

/* Related Pages */
.nyt-page-related {
    background: #f8f8f8;
    border: 1px solid #e2e2e2;
    border-radius: 8px;
    padding: 32px;
}

.nyt-page-related h3 {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1rem;
    font-weight: 600;
    margin: 0 0 20px 0;
    color: #121212;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.related-grid {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.related-card {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 20px;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 6px;
    text-decoration: none;
    transition: all 0.2s;
}

.related-card:hover {
    border-color: #121212;
    background: #121212;
}

.related-title {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1rem;
    font-weight: 500;
    color: #121212;
}

.related-card:hover .related-title {
    color: #fff;
}

.related-arrow {
    font-size: 1.2rem;
    color: #666;
    transition: transform 0.2s;
}

.related-card:hover .related-arrow {
    color: #fff;
    transform: translateX(4px);
}

/* Dark Mode */
body.dark-mode .nyt-page-header {
    border-bottom-color: #444;
}

body.dark-mode .nyt-page-kicker {
    color: #999;
}

body.dark-mode .nyt-page-title {
    color: #f0f0f0;
}

body.dark-mode .nyt-page-summary {
    color: #aaa;
}

body.dark-mode .nyt-page-meta {
    color: #777;
}

body.dark-mode .nyt-page-body {
    color: #ccc;
}

body.dark-mode .nyt-page-body h2 {
    color: #f0f0f0;
    border-bottom-color: #f0f0f0;
}

body.dark-mode .nyt-page-body h3 {
    color: #ddd;
}

body.dark-mode .nyt-page-body h4 {
    color: #ccc;
}

body.dark-mode .nyt-page-body strong {
    color: #f0f0f0;
}

body.dark-mode .nyt-page-body a {
    color: #5b9bd5;
}

body.dark-mode .nyt-page-body a:hover {
    color: #f0f0f0;
}

body.dark-mode .nyt-page-body blockquote {
    background: #1a1a1a;
    border-left-color: #f0f0f0;
}

body.dark-mode .nyt-page-footer {
    border-top-color: #444;
}

body.dark-mode .share-label {
    color: #999;
}

body.dark-mode .share-btn {
    background: #2a2a2a;
    border-color: #444;
    color: #ccc;
}

body.dark-mode .share-btn:hover {
    background: #f0f0f0;
    border-color: #f0f0f0;
    color: #121212;
}

body.dark-mode .nyt-page-related {
    background: #1a1a1a;
    border-color: #333;
}

body.dark-mode .nyt-page-related h3 {
    color: #f0f0f0;
}

body.dark-mode .related-card {
    background: #2a2a2a;
    border-color: #444;
}

body.dark-mode .related-card:hover {
    background: #f0f0f0;
    border-color: #f0f0f0;
}

body.dark-mode .related-title {
    color: #f0f0f0;
}

body.dark-mode .related-card:hover .related-title {
    color: #121212;
}

body.dark-mode .related-arrow {
    color: #888;
}

body.dark-mode .related-card:hover .related-arrow {
    color: #121212;
}

/* Responsive */
@media (max-width: 600px) {
    .nyt-page-title {
        font-size: 2rem;
    }
    
    .nyt-page-summary {
        font-size: 1.1rem;
    }
    
    .nyt-page-body {
        font-size: 1rem;
    }
    
    .nyt-page-body h2 {
        font-size: 1.3rem;
    }
    
    .nyt-page-body h3 {
        font-size: 1.15rem;
    }
    
    .nyt-page-related {
        padding: 24px;
    }
}
</style>
@endsection
