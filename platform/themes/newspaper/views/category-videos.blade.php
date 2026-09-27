{{--
    Videos Category Template - Dedicated video listing page
    Optimized for Google Video indexing
--}}

@php
    Theme::set('pageId', 'videos-page');
    Theme::set('pageName', __('Videos'));
    
    // Get video posts
    $videoPosts = get_posts_by_category($category->id, 12);
    $featuredVideo = $videoPosts->first();
@overwrite

@push('header')
    {{-- Video Gallery Schema --}}
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "VideoGallery",
        "name": "{{ $category->name }} - {{ theme_option('site_title') }}",
        "description": "{{ strip_tags($category->description) }}",
        "url": "{{ $category->url }}",
        "publisher": {
            "@type": "Organization",
            "name": "{{ theme_option('site_title') }}",
            "logo": {
                "@type": "ImageObject",
                "url": "{{ RvMedia::getImageUrl(theme_option('logo')) }}"
            }
        },
        "video": [
            @foreach($videoPosts->take(10) as $index => $video)
            @php
                $videoId = null;
                if ($video->format_type == 'video' && $video->video_embed_code) {
                    preg_match('/(?:youtube\.com\/embed\/|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $video->video_embed_code, $matches);
                    $videoId = $matches[1] ?? null;
                }
            @endphp
            @if($videoId)
            {
                "@type": "VideoObject",
                "name": "{{ $video->name }}",
                "description": "{{ Str::limit(strip_tags($video->description), 200) }}",
                "thumbnailUrl": "https://i.ytimg.com/vi/{{ $videoId }}/maxresdefault.jpg",
                "uploadDate": "{{ $video->created_at->toIso8601String() }}",
                "contentUrl": "https://www.youtube.com/watch?v={{ $videoId }}",
                "embedUrl": "https://www.youtube.com/embed/{{ $videoId }}",
                "url": "{{ $video->url }}",
                "author": {
                    "@type": "Organization",
                    "name": "{{ theme_option('site_title') }}"
                },
                "publisher": {
                    "@type": "Organization",
                    "name": "{{ theme_option('site_title') }}"
                }
            }@if(!$loop->last),@endif
            @endif
            @endforeach
        ]
    }
    </script>
    
    <meta property="og:type" content="video.other" />
    <meta property="og:video" content="{{ $featuredVideo ? 'https://www.youtube.com/embed/' . (function($v) { preg_match('/(?:youtube\.com\/embed\/|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $v->video_embed_code, $m); return $m[1] ?? ''; })($featuredVideo) : '' }}" />
    <meta property="og:video:type" content="text/html" />
    <meta property="og:video:width" content="1280" />
    <meta property="og:video:height" content="720" />
@endpush

<section class="videos-category-page">
    {{-- Breadcrumb --}}
    <nav class="breadcrumb" aria-label="Breadcrumb">
            <ol itemscope itemtype="https://schema.org/BreadcrumbList">
                <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                    <a itemprop="item" href="{{ route('public.single') }}">
                        <span itemprop="name">{{ __('Home') }}</span>
                    </a>
                    <meta itemprop="position" content="1" />
                </li>
                <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem" aria-current="page">
                    <span itemprop="name">{{ $category->name }}</span>
                    <meta itemprop="position" content="2" />
                </li>
            </ol>
        </nav>

        {{-- Page Header --}}
        <header class="videos-page-header">
            <h1 class="videos-page-title">{{ $category->name }}</h1>
            @if($category->description)
                <p class="videos-page-description">{{ $category->description }}</p>
            @endif
        </header>

        {{-- Featured Video --}}
        @if($featuredVideo)
            @php
                $featuredVideoId = null;
                if ($featuredVideo->format_type == 'video' && $featuredVideo->video_embed_code) {
                    preg_match('/(?:youtube\.com\/embed\/|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $featuredVideo->video_embed_code, $matches);
                    $featuredVideoId = $matches[1] ?? null;
                }
            @endphp
            
            <section class="featured-video-section">
                <article class="featured-video" itemscope itemtype="https://schema.org/VideoObject">
                    <meta itemprop="name" content="{{ $featuredVideo->name }}" />
                    <meta itemprop="description" content="{{ Str::limit(strip_tags($featuredVideo->description), 200) }}" />
                    <meta itemprop="uploadDate" content="{{ $featuredVideo->created_at->toIso8601String() }}" />
                    @if($featuredVideoId)
                        <meta itemprop="thumbnailUrl" content="https://i.ytimg.com/vi/{{ $featuredVideoId }}/maxresdefault.jpg" />
                        <meta itemprop="contentUrl" content="https://www.youtube.com/watch?v={{ $featuredVideoId }}" />
                        <meta itemprop="embedUrl" content="https://www.youtube.com/embed/{{ $featuredVideoId }}" />
                    @endif
                    
                    <div class="featured-video-player video-responsive">
                        @if($featuredVideoId)
                            <iframe 
                                src="https://www.youtube.com/embed/{{ $featuredVideoId }}?rel=0&modestbranding=1"
                                title="{{ $featuredVideo->name }}"
                                frameborder="0"
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                allowfullscreen
                                loading="eager"
                                itemprop="embedUrl">
                            </iframe>
                        @else
                            {!! $featuredVideo->content !!}
                        @endif
                    </div>
                    
                    <div class="featured-video-info">
                        <h2 class="featured-video-title">
                            <a href="{{ $featuredVideo->url }}" itemprop="url">{{ $featuredVideo->name }}</a>
                        </h2>
                        <div class="featured-video-meta">
                            <time datetime="{{ $featuredVideo->created_at->toIso8601String() }}" itemprop="uploadDate">
                                {{ $featuredVideo->created_at->format('F j, Y') }}
                            </time>
                            @if($featuredVideo->author)
                                <span class="video-author" itemprop="author">
                                    {{ __('By') }} <a href="{{ $featuredVideo->author->url }}">{{ $featuredVideo->author->name }}</a>
                                </span>
                            @endif
                            <span class="video-views">{{ number_format($featuredVideo->views ?? 0) }} {{ __('views') }}</span>
                        </div>
                        <p class="featured-video-description">{{ Str::limit(strip_tags($featuredVideo->description), 300) }}</p>
                        <a href="{{ $featuredVideo->url }}" class="btn btn-primary">{{ __('Watch & Read More') }}</a>
                    </div>
                </article>
            </section>
        @endif

        {{-- Video Grid --}}
        @if($videoPosts->count() > 1)
            <section class="videos-grid-section">
                <h2 class="section-title">{{ __('More Videos') }}</h2>
                <div class="videos-grid">
                    @foreach($videoPosts->skip(1) as $video)
                        @php
                            $videoId = null;
                            if ($video->format_type == 'video' && $video->video_embed_code) {
                                preg_match('/(?:youtube\.com\/embed\/|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $video->video_embed_code, $matches);
                                $videoId = $matches[1] ?? null;
                            }
                        @endphp
                        
                        <article class="video-card" itemscope itemtype="https://schema.org/VideoObject">
                            <meta itemprop="name" content="{{ $video->name }}" />
                            <meta itemprop="uploadDate" content="{{ $video->created_at->toIso8601String() }}" />
                            @if($videoId)
                                <meta itemprop="thumbnailUrl" content="https://i.ytimg.com/vi/{{ $videoId }}/mqdefault.jpg" />
                                <meta itemprop="contentUrl" content="https://www.youtube.com/watch?v={{ $videoId }}" />
                            @endif
                            
                            <a href="{{ $video->url }}" class="video-card-thumbnail" itemprop="url">
                                @if($videoId)
                                    <img src="https://i.ytimg.com/vi/{{ $videoId }}/mqdefault.jpg"
                                         alt="{{ $video->name }}"
                                         loading="lazy"
                                         decoding="async"
                                         width="320"
                                         height="180"
                                         itemprop="thumbnail" />
                                @else
                                    @include('theme::partials.image', [
                                        'image' => $video->image,
                                        'alt' => $video->name,
                                        'size' => 'medium',
                                        'class' => 'video-card-thumbnail-image',
                                        'imgClass' => 'video-card-thumbnail-image',
                                        'lazy' => true
                                    ])
                                @endif
                                <span class="video-play-icon"><i class="fa fa-play-circle"></i></span>
                                <span class="video-overlay"></span>
                            </a>
                            
                            <div class="video-card-info">
                                <h3 class="video-card-title">
                                    <a href="{{ $video->url }}">{{ $video->name }}</a>
                                </h3>
                                <div class="video-card-meta">
                                    <time datetime="{{ $video->created_at->toIso8601String() }}">
                                        {{ $video->created_at->diffForHumans() }}
                                    </time>
                                    <span class="video-views">{{ number_format($video->views ?? 0) }} {{ __('views') }}</span>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
                
                {{-- Pagination --}}
                <div class="videos-pagination">
                    {!! $videoPosts->links() !!}
                </div>
            </section>
        @endif

    {{-- RSS Feed Link --}}
    <link rel="alternate" type="application/rss+xml" title="{{ $category->name }} Feed" href="{{ route('public.feed.category', ['slug' => $category->slug]) }}" />
</section>

<style>
/* Videos Page Styles */
.videos-category-page {
    padding: 30px 0;
}

.videos-page-header {
    text-align: center;
    margin-bottom: 40px;
}

.videos-page-title {
    font-size: 36px;
    font-weight: 700;
    margin-bottom: 15px;
    overflow-wrap: anywhere;
    word-break: break-word;
}

.videos-page-description {
    font-size: 18px;
    color: #666;
    max-width: 600px;
    margin: 0 auto;
}

/* Featured Video */
.featured-video-section {
    margin-bottom: 50px;
}

.featured-video {
    background: #fff;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
}

.featured-video-player {
    margin: 0;
    background: #000;
}

.featured-video-player iframe {
    min-height: 500px;
}

@media screen and (max-width: 890px) {
    .featured-video-player iframe {
        min-height: 400px;
    }
}

@media screen and (max-width: 590px) {
    .featured-video-player iframe {
        min-height: 250px;
    }
}

.featured-video-info {
    padding: 30px;
}

.featured-video-title {
    font-size: 28px;
    font-weight: 700;
    margin-bottom: 15px;
    overflow-wrap: anywhere;
    word-break: break-word;
}

.featured-video-title a {
    color: #333;
    text-decoration: none;
}

.featured-video-title a:hover {
    color: var(--color-1st);
}

.featured-video-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    color: #666;
    font-size: 14px;
    margin-bottom: 15px;
}

.featured-video-description {
    font-size: 16px;
    line-height: 1.7;
    color: #555;
    margin-bottom: 20px;
}

/* Video Grid */
.videos-grid-section {
    margin-top: 50px;
}

.section-title {
    font-size: 24px;
    font-weight: 700;
    margin-bottom: 25px;
    padding-bottom: 10px;
    border-bottom: 3px solid var(--color-1st);
}

.videos-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 25px;
}

@media screen and (max-width: 1190px) {
    .videos-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media screen and (max-width: 590px) {
    .videos-grid {
        grid-template-columns: 1fr;
    }
}

.video-card {
    background: #fff;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.video-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.video-card-thumbnail {
    position: relative;
    display: block;
    aspect-ratio: 16/9;
    overflow: hidden;
    background: #000;
}

.video-card-thumbnail img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.video-card:hover .video-card-thumbnail img {
    transform: scale(1.05);
}

.video-play-icon {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 60px;
    color: #fff;
    opacity: 0.9;
    transition: opacity 0.3s ease, transform 0.3s ease;
    z-index: 2;
}

.video-card:hover .video-play-icon {
    opacity: 1;
    transform: translate(-50%, -50%) scale(1.1);
}

.video-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.2);
    transition: background 0.3s ease;
}

.video-card:hover .video-overlay {
    background: rgba(0,0,0,0.1);
}

.video-card-info {
    padding: 20px;
}

.video-card-title {
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 10px;
    line-height: 1.4;
    overflow-wrap: anywhere;
    word-break: break-word;
}

.video-card-title a {
    color: #333;
    text-decoration: none;
}

.video-card-title a:hover {
    color: var(--color-1st);
}

.video-card-meta {
    display: flex;
    justify-content: space-between;
    font-size: 13px;
    color: #888;
}

/* Pagination */
.videos-pagination {
    margin-top: 40px;
    display: flex;
    justify-content: center;
}
</style>
