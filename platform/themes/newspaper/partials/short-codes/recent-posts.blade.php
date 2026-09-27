@if (is_plugin_active('blog'))
    @php
        $primarySidebar = $withSidebar ? Theme::partial('primary-sidebar') : null;
        
        // Get recent posts
        $recentPosts = app(\Botble\Blog\Repositories\Interfaces\PostInterface::class)
            ->getListPostNonInList([], (int) ($shortcode->limit ?: 12), ['slugable', 'categories'])
            ->sortByDesc('created_at')
            ->take((int) ($shortcode->limit ?: 12));
    @endphp
    <section @if ($primarySidebar) class="primary fleft" @else style="width: 100%;" @endif>
        <section class="block-post-wrap-item block-post1-wrap-item fleft bsize">
            <section class="block-post-wrap-head sidebar-item-head tf">
                @if ($shortcode->title)
                    <span><i class="fa fa-tags" aria-hidden="true"></i>{{ $shortcode->title }}</span>
                @else
                    <span><i class="fa fa-tags" aria-hidden="true"></i>{{ __('Recent posts') }}</span>
                @endif
            </section>
            <section class="block-post-wrap-content">
                @if (!$recentPosts->isEmpty())
                    @foreach($recentPosts as $post)
                        <section class="post1-item fleft {{ !$post->image ? 'text-only-item' : '' }}">
                            @if ($post->image)
                                <a class="post1-item-thumb thumb-full item-thumbnail"
                                   href="{{ $post->url }}">
                                    {!! RvMedia::image($post->image, $post->name, attributes: ['class' => 'attachment-full size-full wp-post-image']) !!}
                                    <div class="thumbnail-hoverlay main-color-1-bg"></div>
                                    <div class="thumbnail-hoverlay-icon"><i class="fa fa-search"></i></div>
                                </a>
                            @else
                                {{-- Text-only post styling --}}
                                <a class="post1-item-thumb thumb-full item-thumbnail text-only-thumb"
                                   href="{{ $post->url }}"
                                   style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;">
                                    <div style="
                                        width: 100%;
                                        height: 100%;
                                        display: flex;
                                        align-items: center;
                                        justify-content: center;
                                        color: white;
                                        font-size: 2rem;
                                    ">
                                        <i class="fa fa-align-left" aria-hidden="true"></i>
                                    </div>
                                    <div class="thumbnail-hoverlay main-color-1-bg"></div>
                                    <div class="thumbnail-hoverlay-icon"><i class="fa fa-arrow-right"></i></div>
                                </a>
                            @endif
                            <section class="post1-item-info">
                                @if ($post->format_type === 'text-only' && !$post->image)
                                    <span class="format-badge" style="
                                        display: inline-block;
                                        padding: 2px 6px;
                                        background: #007bff;
                                        color: white;
                                        font-size: 0.65rem;
                                        border-radius: 3px;
                                        margin-bottom: 4px;
                                        text-transform: uppercase;
                                    ">
                                        {{ __('Quick Read') }}
                                    </span>
                                @endif
                                <h2 class="post1-item-title">
                                    <a class="white-space"
                                       href="{{ $post->url }}">{{ $post->name }}</a>
                                </h2>
                                @if ($loop->first)
                                    <section class="featured-home-post-item-date" style="color: #444343;">
                                        <span><i class="fa fa-calendar" aria-hidden="true"></i>{{ Theme::formatDate($post->created_at) }}</span>
                                        @if (class_exists($post->author_type) && $post->author)
                                            <span><i class="fa fa-user-secret" aria-hidden="true"></i>
                                                @if ($post->author->url)
                                                    <a href="{{ $post->author->url }}" class="author-link-name">{{ $post->author->name }}</a>
                                                @else
                                                    {{ $post->author->name }}
                                                @endif
                                            </span>
                                        @endif
                                    </section>
                                    <section class="post1-item-snippet">
                                        {{ Str::limit($post->description, 80) }}
                                    </section>
                                @endif
                            </section>
                        </section>
                    @endforeach
                @endif
                <section class="cboth"></section>
            </section>
        </section>
        <section class="cboth"></section>
    </section>
    @if ($primarySidebar)
        {!! $primarySidebar !!}
    @endif
@endif
