@if (is_plugin_active('blog'))
    @php
        // Get AI author
        $aiAuthor = \Illuminate\Support\Facades\Schema::hasTable('authors')
            ? \Botble\Author\Models\Author::query()->where('email', 'genzai@genznewz.com')->first()
            : null;
        
        // Get featured text-only posts by AI
        $aiTextPosts = collect();
        
        if ($aiAuthor) {
            $aiTextPosts = app(\Botble\Blog\Repositories\Interfaces\PostInterface::class)
                ->getListPostNonInList([], (int) ($shortcode->limit ?: 5), ['slugable', 'categories'])
                ->where('format_type', 'text-only')
                ->where('author_id', $aiAuthor->id)
                ->where('is_featured', true)
                ->where('status', 'published')
                ->sortByDesc('created_at')
                ->take((int) ($shortcode->limit ?: 5));
        }
        
        // If not enough AI featured posts, get any featured text-only posts
        if ($aiTextPosts->count() < ($shortcode->limit ?: 5)) {
            $additionalPosts = app(\Botble\Blog\Repositories\Interfaces\PostInterface::class)
                ->getListPostNonInList($aiTextPosts->pluck('id')->toArray(), (int) ($shortcode->limit ?: 5), ['slugable', 'categories'])
                ->where('format_type', 'text-only')
                ->where('is_featured', true)
                ->where('status', 'published')
                ->sortByDesc('created_at')
                ->take((int) ($shortcode->limit ?: 5) - $aiTextPosts->count());
            
            $aiTextPosts = $aiTextPosts->merge($additionalPosts);
        }
    @endphp

    @if ($aiTextPosts->count() > 0)
        <section class="ai-text-spotlight" style="
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            padding: 40px 0;
            margin: 30px 0;
            position: relative;
            overflow: hidden;
        ">
            {{-- Decorative background elements --}}
            <div class="ai-spotlight-bg" style="
                position: absolute;
                top: -50%;
                right: -10%;
                width: 500px;
                height: 500px;
                background: radial-gradient(circle, rgba(0,123,255,0.1) 0%, transparent 70%);
                border-radius: 50%;
                pointer-events: none;
            "></div>
            
            <section class="container" style="position: relative; z-index: 1;">
                {{-- Section Header --}}
                <div class="ai-spotlight-header" style="
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    margin-bottom: 30px;
                    border-bottom: 2px solid rgba(255,255,255,0.1);
                    padding-bottom: 15px;
                ">
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <div class="ai-icon" style="
                            width: 50px;
                            height: 50px;
                            background: linear-gradient(135deg, #00d4ff 0%, #0099ff 100%);
                            border-radius: 12px;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            font-size: 1.5rem;
                            box-shadow: 0 4px 15px rgba(0,153,255,0.3);
                        ">
                            🤖
                        </div>
                        <div>
                            <h2 class="ai-spotlight-title" style="
                                margin: 0;
                                font-size: 1.6rem;
                                font-weight: 700;
                                color: #fff;
                                letter-spacing: -0.5px;
                            ">
                                {{ $shortcode->title ?: __('AI Text Spotlight') }}
                            </h2>
                            <p style="
                                margin: 4px 0 0 0;
                                font-size: 0.9rem;
                                color: rgba(255,255,255,0.6);
                            ">
                                {{ __('Featured AI-generated news briefs') }}
                            </p>
                        </div>
                    </div>
                    
                    <a href="{{ url('/search') }}" class="ai-view-all" style="
                        color: #00d4ff;
                        text-decoration: none;
                        font-size: 0.9rem;
                        font-weight: 500;
                        display: flex;
                        align-items: center;
                        gap: 5px;
                        transition: all 0.3s ease;
                    " onmouseover="this.style.color='#fff'; this.style.transform='translateX(5px)';" onmouseout="this.style.color='#00d4ff'; this.style.transform='translateX(0)';">
                        {{ __('View All') }} <i class="fa fa-arrow-right"></i>
                    </a>
                </div>

                {{-- Posts Grid --}}
                <div class="ai-text-grid" style="
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
                    gap: 20px;
                ">
                    @foreach($aiTextPosts as $index => $post)
                        @php
                            // Alternate accent colors
                            $accentColors = ['#00d4ff', '#ff6b6b', '#4ecdc4', '#ffe66d', '#a8e6cf'];
                            $accentColor = $accentColors[$index % count($accentColors)];
                        @endphp
                        
                        <article class="ai-text-card" style="
                            background: rgba(255,255,255,0.05);
                            border-radius: 16px;
                            padding: 24px;
                            border-left: 4px solid {{ $accentColor }};
                            transition: all 0.3s ease;
                            cursor: pointer;
                            position: relative;
                            overflow: hidden;
                        " onmouseover="this.style.background='rgba(255,255,255,0.1)'; this.style.transform='translateY(-5px)'; this.style.boxShadow='0 10px 30px rgba(0,0,0,0.3)';" onmouseout="this.style.background='rgba(255,255,255,0.05)'; this.style.transform='translateY(0)'; this.style.boxShadow='none';"
                        onclick="window.location.href='{{ $post->url }}'">
                            
                            {{-- Glow effect on hover --}}
                            <div style="
                                position: absolute;
                                top: 0;
                                left: 0;
                                right: 0;
                                bottom: 0;
                                background: linear-gradient(135deg, {{ $accentColor }}10 0%, transparent 50%);
                                opacity: 0;
                                transition: opacity 0.3s ease;
                                pointer-events: none;
                            " class="card-glow"></div>
                            
                            {{-- Category Badge --}}
                            @if ($post->categories->count())
                                <span class="ai-category-badge" style="
                                    display: inline-block;
                                    padding: 4px 12px;
                                    background: {{ $accentColor }}20;
                                    color: {{ $accentColor }};
                                    font-size: 0.75rem;
                                    font-weight: 600;
                                    text-transform: uppercase;
                                    letter-spacing: 0.5px;
                                    border-radius: 20px;
                                    margin-bottom: 12px;
                                ">
                                    {{ $post->categories->first()->name }}
                                </span>
                            @endif
                            
                            {{-- Title --}}
                            <h3 class="ai-post-title" style="
                                margin: 0 0 12px 0;
                                font-size: 1.15rem;
                                font-weight: 600;
                                color: #fff;
                                line-height: 1.4;
                                display: -webkit-box;
                                -webkit-line-clamp: 2;
                                -webkit-box-orient: vertical;
                                overflow: hidden;
                            ">
                                {{ $post->name }}
                            </h3>
                            
                            {{-- Excerpt --}}
                            @if ($post->description)
                                <p class="ai-post-excerpt" style="
                                    margin: 0 0 16px 0;
                                    font-size: 0.9rem;
                                    color: rgba(255,255,255,0.7);
                                    line-height: 1.5;
                                    display: -webkit-box;
                                    -webkit-line-clamp: 3;
                                    -webkit-box-orient: vertical;
                                    overflow: hidden;
                                ">
                                    {{ $post->description }}
                                </p>
                            @endif
                            
                            {{-- Footer --}}
                            <div class="ai-post-footer" style="
                                display: flex;
                                align-items: center;
                                justify-content: space-between;
                                margin-top: auto;
                                padding-top: 12px;
                                border-top: 1px solid rgba(255,255,255,0.1);
                            ">
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <span style="
                                        font-size: 0.8rem;
                                        color: rgba(255,255,255,0.5);
                                    ">
                                        <i class="fa fa-clock-o" style="margin-right: 4px;"></i>
                                        {{ $post->created_at->diffForHumans() }}
                                    </span>
                                </div>
                                
                                <span class="ai-read-more" style="
                                    font-size: 0.85rem;
                                    color: {{ $accentColor }};
                                    font-weight: 500;
                                    display: flex;
                                    align-items: center;
                                    gap: 4px;
                                ">
                                    {{ __('Read') }} <i class="fa fa-chevron-right" style="font-size: 0.7rem;"></i>
                                </span>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        </section>

        <style>
            @media (max-width: 768px) {
                .ai-text-grid {
                    grid-template-columns: 1fr !important;
                }
                .ai-spotlight-title {
                    font-size: 1.3rem !important;
                }
                .ai-text-card {
                    padding: 18px !important;
                }
                .ai-spotlight-header {
                    flex-direction: column;
                    align-items: flex-start !important;
                    gap: 15px;
                }
            }
            
            .ai-text-card:hover .card-glow {
                opacity: 1 !important;
            }
        </style>
    @endif
@endif
