<section class="featured-home-post">
    <section class="container">
        @if (is_plugin_active('blog'))
            @foreach(get_featured_posts((int) $shortcode->limit ?: 5) as $post)
                <section class="featured-home-post-item thumb-full fleft {{ !$post->image ? 'text-only-format' : '' }}">
                    @if ($post->image)
                        {!! RvMedia::image($post->image, $post->name, attributes: ['class' => 'attachment-full size-full wp-post-image']) !!}
                    @else
                        {{-- Text-only placeholder styling --}}
                        <div class="text-only-placeholder" style="
                            width: 100%;
                            height: 200px;
                            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            color: white;
                            font-size: 3rem;
                        ">
                            <i class="fa fa-align-left" aria-hidden="true"></i>
                        </div>
                    @endif
                    
                    <section class="featured-home-post-item-info bsize">
                        @if ($post->format_type === 'text-only' && !$post->image)
                            <span class="format-badge" style="
                                display: inline-block;
                                padding: 2px 8px;
                                background: #007bff;
                                color: white;
                                font-size: 0.7rem;
                                border-radius: 4px;
                                margin-bottom: 8px;
                                text-transform: uppercase;
                            ">
                                {{ __('Quick Read') }}
                            </span>
                        @endif
                        
                        <h2 class="featured-home-post-item-title">
                            <a href="{{ $post->url }}">{!! BaseHelper::clean($post->name) !!}</a>
                        </h2><!-- end .featured-home-post-item-title -->
                        <section class="featured-home-post-item-date">
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
                        </section><!-- end .featured-home-post-item-date -->
                        <section class="featured-home-post-item-des">
                            {{ Str::limit($post->description, 80) }}
                        </section><!-- end .featured-home-post-item-des -->
                    </section><!-- end .featured-home-post-item-info -->
                </section><!-- end .featured-home-post-item -->
            @endforeach
        @endif
        <section class="cboth"></section><!-- end .cboth -->
    </section><!-- end .featured-home-post -->
</section><!-- end .featured-home-post -->
