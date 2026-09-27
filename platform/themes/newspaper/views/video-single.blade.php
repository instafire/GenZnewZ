{{--
    Video Watch Page Template
    Dedicated page for video content to help with Google Video indexing
--}}

@php
    $videoUrl = $post->format_type == 'video' ? ($post->video_embed_code ?: $post->content) : null;
    $videoId = null;
    
    // Extract YouTube video ID
    if ($videoUrl) {
        preg_match('/(?:youtube\.com\/embed\/|youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]{11})/i', $videoUrl, $matches);
        $videoId = $matches[1] ?? null;
    }
@endphp

@if($videoId)
    @push('header')
        {{-- VideoObject Schema Markup --}}
        <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "VideoObject",
            "name": "{{ $post->name }}",
            "description": "{{ strip_tags($post->description) }}",
            "thumbnailUrl": "https://i.ytimg.com/vi/{{ $videoId }}/maxresdefault.jpg",
            "uploadDate": "{{ $post->created_at->toIso8601String() }}",
            "duration": "{{ $post->video_duration ?? 'PT0M0S' }}",
            "contentUrl": "https://www.youtube.com/watch?v={{ $videoId }}",
            "embedUrl": "https://www.youtube.com/embed/{{ $videoId }}",
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
            "author": {
                "@type": "Organization",
                "name": "{{ theme_option('site_title', 'GenZ News') }}"
            },
            "interactionStatistic": {
                "@type": "InteractionCounter",
                "interactionType": { "@type": "WatchAction" },
                "userInteractionCount": {{ $post->views ?? 0 }}
            }
        }
        </script>
        
        <meta property="og:video" content="https://www.youtube.com/embed/{{ $videoId }}" />
        <meta property="og:video:type" content="text/html" />
        <meta property="og:video:width" content="1280" />
        <meta property="og:video:height" content="720" />
        <meta property="video:duration" content="{{ $post->video_duration_seconds ?? 0 }}" />
    @endpush
@endif

<section class="video-watch-page">
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

        {{-- Video Player Section --}}
        <article class="video-player-container">
            <header class="video-header">
                <h1 class="video-title">{{ $post->name }}</h1>
                
                <div class="video-meta">
                    <time datetime="{{ $post->created_at->toIso8601String() }}">
                        {{ $post->created_at->format('F j, Y') }}
                    </time>
                    @if($post->author)
                        <span class="video-author">
                            {{ __('By') }} <a href="{{ $post->author->url }}">{{ $post->author->name }}</a>
                        </span>
                    @endif
                    <span class="video-views">{{ number_format($post->views ?? 0) }} {{ __('views') }}</span>
                </div>
            </header>

            {{-- Main Video Player --}}
            @if($videoId)
                <div class="video-responsive video-watch-player">
                    <iframe 
                        src="https://www.youtube.com/embed/{{ $videoId }}?autoplay=1&rel=0&modestbranding=1"
                        title="{{ $post->name }}"
                        frameborder="0"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                        allowfullscreen
                        loading="eager">
                    </iframe>
                </div>
            @else
                {!! $post->content !!}
            @endif

            {{-- Video Description --}}
            <div class="video-description">
                {!! $post->content !!}
            </div>

            {{-- Video Actions --}}
            <div class="video-actions">
                <a href="{{ $post->url }}" class="btn btn-primary">
                    <i class="fa fa-newspaper-o"></i> {{ __('Read Full Article') }}
                </a>
                
                <div class="video-share">
                    <span>{{ __('Share:') }}</span>
                    <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($post->url) }}" target="_blank" rel="noopener" class="share-facebook">
                        <i class="fa fa-facebook"></i>
                    </a>
                    <a href="https://twitter.com/intent/tweet?url={{ urlencode($post->url) }}&text={{ urlencode($post->name) }}" target="_blank" rel="noopener" class="share-twitter">
                        <i class="fa fa-twitter"></i>
                    </a>
                    <a href="https://wa.me/?text={{ urlencode($post->name . ' ' . $post->url) }}" target="_blank" rel="noopener" class="share-whatsapp">
                        <i class="fa fa-whatsapp"></i>
                    </a>
                </div>
            </div>
        </article>

        {{-- Related Videos --}}
    @if($relatedVideos = get_related_posts($post->id, 4))
        <section class="related-videos">
            <h2>{{ __('Related Videos') }}</h2>
            <div class="related-videos-grid">
                @foreach($relatedVideos as $relatedVideo)
                    @if($relatedVideo->format_type == 'video')
                        <article class="related-video-item">
                            <a href="{{ $relatedVideo->url }}" class="video-thumbnail">
                                @include('theme::partials.image', [
                                    'image' => $relatedVideo->image,
                                    'alt' => $relatedVideo->name,
                                    'size' => 'medium',
                                    'class' => 'video-thumbnail-image',
                                    'imgClass' => 'video-thumbnail-image',
                                    'lazy' => true
                                ])
                                <span class="video-duration">
                                    <i class="fa fa-play-circle"></i>
                                </span>
                            </a>
                            <h3><a href="{{ $relatedVideo->url }}">{{ $relatedVideo->name }}</a></h3>
                        </article>
                    @endif
                @endforeach
            </div>
        </section>
    @endif
</section>
