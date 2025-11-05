@php
    // Pull featured posts (by a "is_featured" flag if available) and latest posts.
    // These helpers exist in Botble's Blog plugin; fallbacks avoid breaking if empty.
    $featured = get_featured_posts(['limit' => 5]) ?? collect();
    $latest = get_latest_posts(['limit' => 12]) ?? collect();

    // Fetch top categories to strengthen internal linking.
    $topCategories = get_categories(['limit' => 8]) ?? collect();
@endphp

<section class="home">
    <section class="container">
        <h1 style="position:absolute;left:-9999px;">GenZNewz ¨C Latest Stories</h1>

        {{-- HERO / FEATURED --}}
        @if ($featured->count())
            <section class="home-hero">
                <h2 class="block-title">Featured</h2>
                <section class="grid grid-hero">
                    @foreach ($featured as $post)
                        <article class="hero-item">
                            <a href="{{ $post->url }}" class="hero-thumb">
                                <img src="{{ RvMedia::getImageUrl($post->image, 'medium', false, RvMedia::getDefaultImage()) }}"
                                     alt="{{ $post->name }}">
                            </a>
                            <h3 class="hero-title">
                                <a href="{{ $post->url }}">{{ $post->name }}</a>
                            </h3>
                            <div class="hero-meta">
                                <time datetime="{{ $post->created_at }}">{{ Theme::formatDate($post->created_at) }}</time>
                                @if ($post->categories?->count())
                                    <span>¡¤</span>
                                    <a href="{{ $post->categories->first()->url }}">{{ $post->categories->first()->name }}</a>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </section>
            </section>
        @endif

        {{-- LATEST POSTS --}}
        <section class="home-latest" style="margin-top:30px;">
            <h2 class="block-title">Latest</h2>
            @if ($latest->count())
                <section class="grid grid-latest">
                    @foreach ($latest as $post)
                        <article class="card">
                            <a href="{{ $post->url }}" class="thumb">
                                <img src="{{ RvMedia::getImageUrl($post->image, 'small', false, RvMedia::getDefaultImage()) }}"
                                     alt="{{ $post->name }}">
                            </a>
                            <h3 class="title"><a href="{{ $post->url }}">{{ $post->name }}</a></h3>
                            <div class="meta">
                                <time datetime="{{ $post->created_at }}">{{ Theme::formatDate($post->created_at) }}</time>
                            </div>
                            <p class="excerpt">{{ Str::limit(strip_tags($post->description ?: $post->content), 140) }}</p>
                        </article>
                    @endforeach
                </section>
            @else
                {{-- Fallback if no posts yet --}}
                <p>No posts yet. Publish a few articles to populate your homepage.</p>
            @endif
        </section>

        {{-- CATEGORY LINKS (internal linking boost) --}}
        @if ($topCategories->count())
            <section class="home-cats" style="margin-top:30px;">
                <h2 class="block-title">Explore Topics</h2>
                <ul class="cat-list">
                    @foreach ($topCategories as $cat)
                        <li><a href="{{ $cat->url }}">{{ $cat->name }}</a></li>
                    @endforeach
                </ul>
            </section>
        @endif

        <div class="cboth"></div>
    </section>
</section>

<style>
/* simple responsive grids */
.grid { display:grid; gap:16px; }
.grid-hero { grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); }
.grid-latest { grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); }
.hero-item .hero-thumb img, .card .thumb img { width:100%; height:auto; display:block; }
.block-title { font-size:1.4rem; margin:0 0 12px; }
.card .title { font-size:1rem; margin:.5rem 0; line-height:1.3; }
.card .excerpt { font-size:.92rem; opacity:.85; }
.cat-list { display:flex; flex-wrap:wrap; gap:10px; list-style:none; padding:0; margin:0; }
.cat-list a { display:inline-block; padding:6px 10px; border:1px solid rgba(0,0,0,.12); border-radius:999px; text-decoration:none; }
</style>
