{{--
    Post Video Template - Dedicated video watch page
    Optimized for Google Video indexing with full VideoObject schema
--}}

@php
    Theme::set('pageId', 'video-watch-page');
    
    // Extract YouTube video ID
    $videoId = null;
    $videoSource = (string) ($post->video_embed_code ?: $post->content);
    if ($post->format_type == 'video' && $videoSource !== '') {
        preg_match('/(?:youtube\.com\/embed\/|youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]{11})/i', $videoSource, $matches);
        $videoId = $matches[1] ?? null;
    }
@endphp

@push('header')
    @if($videoId)
        {{-- Complete VideoObject Schema for Google Video Indexing --}}
        <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "VideoObject",
            "@id": "{{ $post->url }}#video",
            "name": "{{ $post->name }}",
            "description": "{{ Str::limit(strip_tags($post->description), 500) }}",
            "thumbnailUrl": [
                "https://i.ytimg.com/vi/{{ $videoId }}/maxresdefault.jpg",
                "https://i.ytimg.com/vi/{{ $videoId }}/sddefault.jpg",
                "https://i.ytimg.com/vi/{{ $videoId }}/hqdefault.jpg"
            ],
            "uploadDate": "{{ $post->created_at->toIso8601String() }}",
            "datePublished": "{{ $post->created_at->toIso8601String() }}",
            "dateModified": "{{ $post->updated_at->toIso8601String() }}",
            "duration": "{{ $post->video_duration ?? 'PT0M0S' }}",
            "contentUrl": "https://www.youtube.com/watch?v={{ $videoId }}",
            "embedUrl": "https://www.youtube.com/embed/{{ $videoId }}",
            "interactionStatistic": {
                "@type": "InteractionCounter",
                "interactionType": { "@type": "WatchAction" },
                "userInteractionCount": {{ $post->views ?? 0 }}
            },
            "author": {
                "@type": "Organization",
                "name": "{{ theme_option('site_title', 'GenZ News') }}",
                "url": "{{ route('public.single') }}"
            },
            "publisher": {
                "@type": "Organization",
                "name": "{{ theme_option('site_title', 'GenZ News') }}",
                "logo": {
                    "@type": "ImageObject",
                    "url": "{{ RvMedia::getImageUrl(theme_option('logo')) }}",
                    "width": 400,
                    "height": 100
                }
            },
            "mainEntityOfPage": {
                "@type": "WebPage",
                "@id": "{{ $post->url }}"
            },
            "isPartOf": {
                "@type": "WebPage",
                "@id": "{{ $post->url }}"
            }
        }
        </script>
        
        {{-- NewsArticle Schema for the page --}}
        <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "NewsArticle",
            "mainEntityOfPage": {
                "@type": "WebPage",
                "@id": "{{ $post->url }}"
            },
            "headline": "{{ Str::limit($post->name, 110) }}",
            "description": "{{ Str::limit(strip_tags($post->description), 200) }}",
            "image": [
                "https://i.ytimg.com/vi/{{ $videoId }}/maxresdefault.jpg",
                "{{ RvMedia::getImageUrl($post->image, 'featured') }}"
            ],
            "datePublished": "{{ $post->created_at->toIso8601String() }}",
            "dateModified": "{{ $post->updated_at->toIso8601String() }}",
            "author": {
                "@type": "Organization",
                "name": "{{ theme_option('site_title', 'GenZ News') }}"
            },
            "publisher": {
                "@type": "Organization",
                "name": "{{ theme_option('site_title', 'GenZ News') }}",
                "logo": {
                    "@type": "ImageObject",
                    "url": "{{ RvMedia::getImageUrl(theme_option('logo')) }}"
                }
            },
            "video": {
                "@type": "VideoObject",
                "name": "{{ $post->name }}",
                "description": "{{ Str::limit(strip_tags($post->description), 200) }}",
                "thumbnailUrl": "https://i.ytimg.com/vi/{{ $videoId }}/maxresdefault.jpg",
                "contentUrl": "https://www.youtube.com/watch?v={{ $videoId }}",
                "embedUrl": "https://www.youtube.com/embed/{{ $videoId }}",
                "uploadDate": "{{ $post->created_at->toIso8601String() }}"
            }
        }
        </script>
        
        {{-- Video Open Graph Tags --}}
        <meta property="og:type" content="video.other" />
        <meta property="og:video" content="https://www.youtube.com/embed/{{ $videoId }}" />
        <meta property="og:video:type" content="text/html" />
        <meta property="og:video:width" content="1280" />
        <meta property="og:video:height" content="720" />
        <meta property="og:video:tag" content="{{ $post->tags->pluck('name')->implode(', ') }}" />
        
        {{-- Twitter Card Video Tags --}}
        <meta name="twitter:card" content="player" />
        <meta name="twitter:player" content="https://www.youtube.com/embed/{{ $videoId }}" />
        <meta name="twitter:player:width" content="1280" />
        <meta name="twitter:player:height" content="720" />
        
        {{-- Video Thumbnail --}}
        <meta property="og:image" content="https://i.ytimg.com/vi/{{ $videoId }}/maxresdefault.jpg" />
        <meta property="og:image:width" content="1280" />
        <meta property="og:image:height" content="720" />
        <meta name="twitter:image" content="https://i.ytimg.com/vi/{{ $videoId }}/maxresdefault.jpg" />
    @endif
@endpush

<section class="video-watch-page" itemscope itemtype="https://schema.org/VideoObject">
    <meta itemprop="@id" content="{{ $post->url }}#video" />
    <meta itemprop="name" content="{{ $post->name }}" />
    <meta itemprop="description" content="{{ Str::limit(strip_tags($post->description), 500) }}" />
    <meta itemprop="uploadDate" content="{{ $post->created_at->toIso8601String() }}" />
    <meta itemprop="datePublished" content="{{ $post->created_at->toIso8601String() }}" />
    <meta itemprop="dateModified" content="{{ $post->updated_at->toIso8601String() }}" />
    @if($videoId)
        <meta itemprop="thumbnailUrl" content="https://i.ytimg.com/vi/{{ $videoId }}/maxresdefault.jpg" />
        <meta itemprop="contentUrl" content="https://www.youtube.com/watch?v={{ $videoId }}" />
        <meta itemprop="embedUrl" content="https://www.youtube.com/embed/{{ $videoId }}" />
    @endif
    
    {{-- Breadcrumb --}}
    <nav class="breadcrumb" aria-label="Breadcrumb">
            <ol itemscope itemtype="https://schema.org/BreadcrumbList">
                <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                    <a itemprop="item" href="{{ route('public.single') }}">
                        <span itemprop="name">{{ __('Home') }}</span>
                    </a>
                    <meta itemprop="position" content="1" />
                </li>
                @if($post->categories->first())
                    <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                        <a itemprop="item" href="{{ $post->categories->first()->url }}">
                            <span itemprop="name">{{ $post->categories->first()->name }}</span>
                        </a>
                        <meta itemprop="position" content="2" />
                    </li>
                @endif
                <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem" aria-current="page">
                    <span itemprop="name">{{ $post->name }}</span>
                    <meta itemprop="position" content="3" />
                </li>
            </ol>
        </nav>

        {{-- Video Header --}}
        <header class="video-header">
            <h1 class="video-title" itemprop="headline">{{ $post->name }}</h1>
            
            <div class="video-meta">
                <time datetime="{{ $post->created_at->toIso8601String() }}" itemprop="datePublished">
                    {{ $post->created_at->format('F j, Y') }}
                </time>
                @if($post->author)
                    <span class="video-author" itemprop="author">
                        {{ __('By') }} <a href="{{ $post->author->url }}">{{ $post->author->name }}</a>
                    </span>
                @endif
                <span class="video-views" itemprop="interactionStatistic" itemscope itemtype="https://schema.org/InteractionCounter">
                    <meta itemprop="interactionType" content="https://schema.org/WatchAction" />
                    <span itemprop="userInteractionCount">{{ number_format($post->views ?? 0) }}</span> {{ __('views') }}
                </span>
            </div>
        </header>

        {{-- Main Video Player --}}
        @if($videoId)
            <div class="video-player-container">
                <div class="video-responsive video-watch-player" itemprop="video">
                    <iframe 
                        src="https://www.youtube.com/embed/{{ $videoId }}?autoplay=1&rel=0&modestbranding=1&playsinline=1"
                        title="{{ $post->name }}"
                        frameborder="0"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                        allowfullscreen
                        loading="eager"
                        itemprop="embedUrl">
                    </iframe>
                </div>
            </div>
        @else
            <div class="video-player-container">
                {!! $post->content !!}
            </div>
        @endif

        {{-- Video Description --}}
        <div class="video-description" itemprop="description">
            {!! $post->content !!}
        </div>

        {{-- Video Actions --}}
        <div class="video-actions">
            <div class="video-tags">
                @foreach($post->tags as $tag)
                    <a href="{{ $tag->url }}" class="video-tag">#{{ $tag->name }}</a>
                @endforeach
            </div>
            
            <div class="video-share">
                <span>{{ __('Share:') }}</span>
                <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($post->url) }}" target="_blank" rel="noopener" class="share-facebook" title="Share on Facebook">
                    <i class="fa fa-facebook"></i>
                </a>
                <a href="https://twitter.com/intent/tweet?url={{ urlencode($post->url) }}&text={{ urlencode($post->name) }}" target="_blank" rel="noopener" class="share-twitter" title="Share on Twitter">
                    <i class="fa fa-twitter"></i>
                </a>
                <a href="https://wa.me/?text={{ urlencode($post->name . ' ' . $post->url) }}" target="_blank" rel="noopener" class="share-whatsapp" title="Share on WhatsApp">
                    <i class="fa fa-whatsapp"></i>
                </a>
                <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ urlencode($post->url) }}" target="_blank" rel="noopener" class="share-linkedin" title="Share on LinkedIn">
                    <i class="fa fa-linkedin"></i>
                </a>
            </div>
        </div>

        {{-- Related Videos --}}
        @php
            $relatedVideos = get_related_posts($post->id, 4, $post->categories->pluck('id')->toArray());
        @endphp
        
    @if($relatedVideos && $relatedVideos->count() > 0)
        <section class="related-videos">
            <h2>{{ __('Related Videos') }}</h2>
            <div class="related-videos-grid">
                @foreach($relatedVideos as $relatedVideo)
                    @php
                        $relatedVideoId = null;
                        if ($relatedVideo->format_type == 'video' && $relatedVideo->video_embed_code) {
                            preg_match('/(?:youtube\.com\/embed\/|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $relatedVideo->video_embed_code, $matches);
                            $relatedVideoId = $matches[1] ?? null;
                        }
                    @endphp
                    
                    <article class="related-video-item">
                        <a href="{{ $relatedVideo->url }}" class="video-thumbnail" title="{{ $relatedVideo->name }}">
                            @if($relatedVideoId)
                                <img src="https://i.ytimg.com/vi/{{ $relatedVideoId }}/mqdefault.jpg"
                                     alt="{{ $relatedVideo->name }}"
                                     loading="lazy"
                                     decoding="async"
                                     width="320"
                                     height="180">
                            @else
                                @include('theme::partials.image', [
                                    'image' => $relatedVideo->image,
                                    'alt' => $relatedVideo->name,
                                    'size' => 'medium',
                                    'class' => 'video-thumbnail-image',
                                    'imgClass' => 'video-thumbnail-image',
                                    'lazy' => true
                                ])
                            @endif
                            <span class="video-duration"><i class="fa fa-play-circle"></i></span>
                        </a>
                        <h3><a href="{{ $relatedVideo->url }}">{{ $relatedVideo->name }}</a></h3>
                    </article>
                @endforeach
            </div>
        </section>
    @endif
</section>

<style>
/* Video Watch Page Styles */
.video-watch-page {
    padding: 30px 0 60px;
}

.video-header {
    margin-bottom: 25px;
}

.video-title {
    font-size: 32px;
    font-weight: 700;
    line-height: 1.3;
    margin-bottom: 15px;
    color: #222;
    overflow-wrap: anywhere;
    word-break: break-word;
}

@media screen and (max-width: 890px) {
    .video-title {
        font-size: 24px;
    }
}

@media screen and (max-width: 590px) {
    .video-title {
        font-size: 20px;
    }
}

.video-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    color: #666;
    font-size: 14px;
}

.video-meta a {
    color: var(--color-1st);
}

/* Video Player Container */
.video-player-container {
    background: #000;
    border-radius: 8px;
    overflow: hidden;
    margin-bottom: 30px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.2);
}

.video-watch-player {
    margin: 0;
}

.video-watch-player iframe {
    min-height: 550px;
}

@media screen and (max-width: 1190px) {
    .video-watch-player iframe {
        min-height: 450px;
    }
}

@media screen and (max-width: 890px) {
    .video-watch-player iframe {
        min-height: 350px;
    }
}

@media screen and (max-width: 590px) {
    .video-watch-player iframe {
        min-height: 220px;
    }
}

/* Video Description */
.video-description {
    font-size: 17px;
    line-height: 1.8;
    color: #444;
    margin-bottom: 30px;
}

.video-description p {
    margin-bottom: 15px;
}

.video-description img {
    max-width: 100%;
    height: auto;
    border-radius: 5px;
}

/* Video Actions */
.video-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 0;
    border-top: 1px solid #eee;
    border-bottom: 1px solid #eee;
    margin-bottom: 40px;
    flex-wrap: wrap;
    gap: 20px;
}

.video-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.video-tag {
    display: inline-block;
    padding: 6px 14px;
    background: #f0f0f0;
    color: #555;
    border-radius: 20px;
    font-size: 13px;
    text-decoration: none;
    transition: all 0.3s ease;
}

.video-tag:hover {
    background: var(--color-1st);
    color: #fff;
}

.video-share {
    display: flex;
    align-items: center;
    gap: 12px;
}

.video-share span {
    font-weight: 600;
    color: #555;
}

.video-share a {
    width: 40px;
    height: 40px;
    line-height: 40px;
    text-align: center;
    border-radius: 50%;
    color: #fff;
    font-size: 16px;
    transition: all 0.3s ease;
    text-decoration: none;
}

.video-share a:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 10px rgba(0,0,0,0.2);
}

.video-share .share-facebook { background: #3b5998; }
.video-share .share-twitter { background: #1da1f2; }
.video-share .share-whatsapp { background: #25d366; }
.video-share .share-linkedin { background: #0077b5; }

/* Related Videos */
.related-videos {
    margin-top: 50px;
}

.related-videos h2 {
    font-size: 24px;
    font-weight: 700;
    margin-bottom: 25px;
    padding-bottom: 10px;
    border-bottom: 3px solid var(--color-1st);
}

.related-videos-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
}

@media screen and (max-width: 1190px) {
    .related-videos-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media screen and (max-width: 890px) {
    .related-videos-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media screen and (max-width: 590px) {
    .related-videos-grid {
        grid-template-columns: 1fr;
    }
}

.related-video-item {
    background: #fff;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transition: transform 0.3s ease;
}

.related-video-item:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.related-video-item .video-thumbnail {
    position: relative;
    display: block;
    aspect-ratio: 16/9;
    overflow: hidden;
}

.related-video-item .video-thumbnail img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.related-video-item:hover .video-thumbnail img {
    transform: scale(1.05);
}

.related-video-item .video-duration {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 50px;
    color: #fff;
    opacity: 0.9;
    transition: all 0.3s ease;
}

.related-video-item:hover .video-duration {
    opacity: 1;
    transform: translate(-50%, -50%) scale(1.1);
}

.related-video-item h3 {
    padding: 15px;
    font-size: 15px;
    font-weight: 600;
    margin: 0;
    line-height: 1.4;
}

.related-video-item h3 a {
    color: #333;
    text-decoration: none;
}

.related-video-item h3 a:hover {
    color: var(--color-1st);
}
</style>
