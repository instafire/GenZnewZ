@if (is_plugin_active('blog'))
    @php
        $textOnlyPosts = app(\Botble\Blog\Repositories\Interfaces\PostInterface::class)
            ->getListPostNonInList([], (int) ($shortcode->limit ?: 10), ['slugable'])
            ->where('format_type', 'text-only')
            ->where('status', 'published')
            ->sortByDesc('created_at')
            ->take((int) ($shortcode->limit ?: 10));
    @endphp

    @if ($textOnlyPosts->count() > 0)
        <section class="text-only-posts-section" style="margin: 30px 0;">
            <section class="container">
                @if ($shortcode->title)
                    <h2 class="block-title" style="font-size: 1.4rem; margin-bottom: 16px; border-bottom: 2px solid #333; padding-bottom: 8px;">
                        <i class="fa fa-align-left" aria-hidden="true" style="margin-right: 8px;"></i>
                        {{ $shortcode->title }}
                    </h2>
                @else
                    <h2 class="block-title" style="font-size: 1.4rem; margin-bottom: 16px; border-bottom: 2px solid #333; padding-bottom: 8px;">
                        <i class="fa fa-align-left" aria-hidden="true" style="margin-right: 8px;"></i>
                        {{ __('Quick Reads') }}
                    </h2>
                @endif
                
                <div class="text-only-posts-list" style="display: flex; flex-direction: column; gap: 12px;">
                    @foreach($textOnlyPosts as $post)
                        <article class="text-only-post-item" style="
                            padding: 16px 20px;
                            background: #f8f9fa;
                            border-radius: 8px;
                            border-left: 4px solid #007bff;
                            transition: all 0.2s ease;
                        " onmouseover="this.style.background='#e9ecef'; this.style.transform='translateX(4px)';" onmouseout="this.style.background='#f8f9fa'; this.style.transform='translateX(0)';">
                            <div class="text-only-post-header" style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
                                @if ($post->categories->count())
                                    <span class="post-category" style="
                                        font-size: 0.75rem;
                                        text-transform: uppercase;
                                        letter-spacing: 0.5px;
                                        color: #007bff;
                                        font-weight: 600;
                                    ">
                                        {{ $post->categories->first()->name }}
                                    </span>
                                @endif
                                <time class="post-date" style="font-size: 0.8rem; color: #6c757d;" datetime="{{ $post->created_at }}">
                                    <i class="fa fa-clock-o" aria-hidden="true" style="margin-right: 4px;"></i>
                                    {{ Theme::formatDate($post->created_at) }}
                                </time>
                            </div>
                            
                            <h3 class="text-only-post-title" style="margin: 0; font-size: 1.1rem; line-height: 1.4;">
                                <a href="{{ $post->url }}" style="
                                    color: #212529;
                                    text-decoration: none;
                                    display: block;
                                " onmouseover="this.style.color='#007bff';" onmouseout="this.style.color='#212529';">
                                    {{ $post->name }}
                                </a>
                            </h3>
                            
                            @if ($post->description)
                                <p class="text-only-post-excerpt" style="
                                    margin: 8px 0 0 0;
                                    font-size: 0.9rem;
                                    color: #495057;
                                    line-height: 1.5;
                                ">
                                    {{ Str::limit($post->description, 120) }}
                                </p>
                            @endif
                        </article>
                    @endforeach
                </div>
                
                @if ($shortcode->show_view_all !== 'no')
                    <div class="text-only-posts-footer" style="margin-top: 16px; text-align: center;">
                        <a href="{{ url('/search') }}" style="
                            display: inline-block;
                            padding: 8px 20px;
                            background: #007bff;
                            color: white;
                            text-decoration: none;
                            border-radius: 20px;
                            font-size: 0.9rem;
                            transition: background 0.2s;
                        " onmouseover="this.style.background='#0056b3';" onmouseout="this.style.background='#007bff';">
                            {{ __('View All Articles') }} <i class="fa fa-arrow-right" aria-hidden="true" style="margin-left: 4px;"></i>
                        </a>
                    </div>
                @endif
            </section>
        </section>

        <style>
            @media (max-width: 768px) {
                .text-only-post-item {
                    padding: 12px 16px !important;
                }
                .text-only-post-title {
                    font-size: 1rem !important;
                }
            }
        </style>
    @endif
@endif
