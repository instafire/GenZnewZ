@php
    use App\Services\ArticleContentImageSanitizer;
    use App\Services\PostSeoPresentationService;
    use Botble\SeoHelper\Facades\SeoHelper;

    // Article styles extracted from the former inline <style> block (2026-09).
    // deps: theme-dark-css (shares selectors with dark rules) + comments-css
    // (the comments partial's sheet rendered first in the original DOM).
    $postCssPath = platform_path('themes/newspaper/public/css/post.css');
    Theme::asset()->usePath()->add(
        'post-css',
        'css/post.css',
        ['theme-dark-css', 'comments-css'],
        [],
        file_exists($postCssPath) ? (string) filemtime($postCssPath) : null
    );

    $postSeo = app(PostSeoPresentationService::class);

    // Article reading tools (progress bar, outline, sharing, save-for-later).
    // Vanilla JS, no dependencies; only meaningful on article pages so it is
    // registered here instead of globally in config.php.
    Theme::asset()
        ->container('footer')
        ->usePath()
        ->add('article-tools-js', 'js/article-tools.js', [], [], (string) filemtime(platform_path('themes/newspaper/public/js/article-tools.js')));
    $articleContentSanitizer = app(ArticleContentImageSanitizer::class);
    $normalizedArticleBody = $articleContentSanitizer->sanitize((string) $post->content);
    $normalizedArticleBody = preg_replace('/<\s*\/\s*h1\s*>/i', '</h2>', $normalizedArticleBody);
    $normalizedArticleBody = preg_replace('/<h1\b([^>]*)>/i', '<h2$1>', $normalizedArticleBody);

    SeoHelper::setTitle($postSeo->buildTitle($post));
    SeoHelper::setDescription($postSeo->buildDescription($post));
@endphp

<meta name="post-id" content="{{ $post->id }}">

<article class="standard-article">
    <div class="article-container">
        <div class="article-header-badges">
            @if ($post->categories->count())
                <span class="article-category">{{ $post->categories->first()->name }}</span>
            @endif
            @include('theme::partials.author-badge', ['post' => $post, 'detailed' => true])
        </div>

        <h1 class="article-headline">{{ $post->name }}</h1>
        
        @if ($post->description)
            <p class="article-summary">{{ $post->description }}</p>
        @endif
        
        <div class="article-meta">
            <span>By <a href="{{ $post->author->url }}" class="author-link">{{ $post->author->name ?? 'Staff' }}</a></span>
            <span>•</span>
            <span>{{ $post->created_at->format('F j, Y') }}</span>
            <span>•</span>
            <span>{{ number_format($post->views ?? 0) }} views</span>
        </div>

        @php
            $isAiPost = !empty($post->getMetaData('ai_reporter_id', true))
                || str_contains(strtolower((string) ($post->author->name ?? '')), 'genzai');
        @endphp
        @if ($isAiPost)
            <p class="article-ai-disclosure">This article was produced with AI assistance and reviewed by a named GenZ NewZ editor before publication.</p>
        @endif
        
        @if ($post->image)
            @include('theme::partials.image', [
                'image' => $post->image,
                'alt' => $post->name,
                'size' => 'large',
                'class' => 'article-hero-image',
                'imgClass' => 'article-hero-image',
                'lazy' => false,
                'loading' => 'eager',
                'fetchPriority' => 'high'
            ])
            @php
                $pexelsPhotographer = $post->getMetaData('pexels_photographer', true);
                $pexelsPhotographerUrl = $post->getMetaData('pexels_photographer_url', true);
                $pexelsPhotoUrl = $post->getMetaData('pexels_photo_url', true);
            @endphp
            @if (!empty($pexelsPhotographer))
                <p class="pexels-attribution">
                    Photo by <a href="{{ $pexelsPhotographerUrl }}" target="_blank" rel="noopener noreferrer">{{ $pexelsPhotographer }}</a>
                    on <a href="{{ $pexelsPhotoUrl }}" target="_blank" rel="noopener noreferrer">Pexels</a>
                </p>
            @endif
        @endif

        {{-- Read time, share/save controls and the auto-generated outline. --}}
        @include('theme::partials.article-tools', ['post' => $post, 'normalizedArticleBody' => $normalizedArticleBody])

        <div class="article-body" role="article" aria-label="Article content">
            {!! $normalizedArticleBody !!}
        </div>
    </div>
</article>

{{-- Recommendations --}}
@include('theme::partials.recommendations', ['post' => $post, 'limit' => 4, 'title' => 'More Like This'])

{{-- NYT-Style Comments Section --}}
@include('theme.newspaper::partials.comments', ['post' => $post])

{{-- Transform Social Links to Icon Grid --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Find "Where To Find Him" section
    const headings = document.querySelectorAll('.article-body h2');
    headings.forEach(heading => {
        if (heading.textContent.includes('Where To Find Him') || heading.textContent.includes('Find Him')) {
            const socialContainer = document.createElement('div');
            socialContainer.className = 'social-icons-grid';
            
            // Platform configurations
            const platforms = {
                'twitch.tv': { name: 'Twitch', icon: 'fa-twitch', color: '#9146FF' },
                'instagram.com': { name: 'Instagram', icon: 'fa-instagram', color: '#E4405F' },
                'tiktok.com': { name: 'TikTok', icon: 'fa-tiktok', color: '#00f2ea' },
                'streamscharts.com': { name: 'Analytics', icon: 'fa-chart-line', color: '#00d4aa' },
                'youtube.com': { name: 'YouTube', icon: 'fa-youtube', color: '#FF0000' },
                'twitter.com': { name: 'X', icon: 'fa-x-twitter', color: '#1DA1F2' },
                'x.com': { name: 'X', icon: 'fa-x-twitter', color: '#1DA1F2' }
            };
            
            // Collect all social links after this heading
            let nextEl = heading.nextElementSibling;
            const links = [];
            
            while (nextEl && nextEl.tagName === 'P') {
                const link = nextEl.querySelector('a.social-link');
                if (link) {
                    links.push({
                        url: link.href,
                        text: link.textContent,
                        description: nextEl.textContent.replace(link.textContent, '').replace(/^[^a-zA-Z]*/, '').trim()
                    });
                    nextEl.style.display = 'none'; // Hide original paragraph
                }
                nextEl = nextEl.nextElementSibling;
            }
            
            // Create icon grid
            links.forEach(link => {
                const platform = Object.keys(platforms).find(p => link.url.includes(p)) || 'link';
                const config = platforms[platform] || { name: 'Link', icon: 'fa-link', color: '#666' };
                
                const iconLink = document.createElement('a');
                iconLink.href = link.url;
                iconLink.target = '_blank';
                iconLink.rel = 'noopener noreferrer';
                iconLink.className = 'social-icon-item';
                iconLink.setAttribute('data-platform', config.name);
                iconLink.innerHTML = `
                    <i class="fa-brands ${config.icon} ${config.icon === 'fa-chart-line' ? 'fa-solid' : ''}"></i>
                    <span class="social-icon-label">${config.name}</span>
                `;
                iconLink.style.setProperty('--brand-color', config.color);
                socialContainer.appendChild(iconLink);
            });
            
            if (links.length > 0) {
                heading.after(socialContainer);
            }
        }
    });
});
</script>

