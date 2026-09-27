<section class="latest-news category-section" data-index="{{ $index ?? 0 }}">
    <div class="section-header">
        <h2 class="section-title">{{ $category->name }}</h2>
        <a href="{{ $category->url }}" class="view-all">{{ __('View All') }} {{ $category->name }} →</a>
    </div>

    <div class="news-grid">
        @foreach ($posts as $post)
            <article class="news-card">
                @if ($post->image)
                    @include('theme::partials.image', [
                        'image' => $post->image,
                        'alt' => $post->name,
                        'size' => 'medium',
                        'class' => 'news-card-image',
                        'imgClass' => 'news-card-image',
                        'lazy' => true,
                    ])
                @endif
                <div class="news-card-content {{ !$post->image ? 'text-only-content' : '' }}">
                    @if ($post->categories->count())
                        <span class="news-card-category">{{ $post->categories->first()->name }}</span>
                    @endif
                    <h3 class="news-card-title">
                        <a href="{{ $post->url }}">{{ $post->name }}</a>
                    </h3>
                    @if (!$post->image)
                        <p class="news-card-excerpt">{{ Str::limit($post->description, 200) }}</p>
                    @elseif ($post->description)
                        <p class="news-card-excerpt has-image">{{ Str::limit($post->description, 80) }}</p>
                    @endif
                    <div class="news-card-meta news-card-meta-row">
                        <a href="{{ $post->author->url }}" class="author-link-small">
                            {{ $post->author->name ?? 'Staff' }}
                        </a>
                        <span>•</span>
                        <span>{{ $post->views }} views</span>
                        <span>•</span>
                        <span>{{ $post->created_at->diffForHumans() }}</span>
                    </div>
                </div>
            </article>
        @endforeach
    </div>
</section>
