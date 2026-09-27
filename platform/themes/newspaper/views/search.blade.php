@php
    use Botble\SeoHelper\Facades\SeoHelper;
    use Illuminate\Support\Str;

    $searchQuery = html_entity_decode(strip_tags((string) Request::input('q')), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $searchQuery = str_replace('"', '', $searchQuery);
    $searchQuery = preg_replace('/\s+/', ' ', trim($searchQuery));
    $searchQuery = Str::limit($searchQuery, 80, '');

    $heading = $searchQuery !== ''
        ? __('Search results for ":query"', ['query' => $searchQuery])
        : __('Browse all stories');

    SeoHelper::setTitle($searchQuery !== '' ? 'Search: ' . $searchQuery . ' - GenZ NewZ' : 'Search - GenZ NewZ');
    SeoHelper::setDescription($searchQuery !== '' ? 'Search results for: ' . $searchQuery . ' on GenZ NewZ. Find the latest news and stories.' : 'Search results on GenZ NewZ. Find the latest news and stories.');
    SeoHelper::meta()->addMeta('robots', 'noindex, follow');
@endphp

{{-- Search results reuse the shared card styles from performance.css. --}}
<div class="container archive-page">
    <header class="section-header archive-head">
        <h1 class="section-title">{{ $heading }}</h1>
        @if ($posts->total())
            <p class="archive-count">
                {{ trans_choice(':count story found|:count stories found', $posts->total(), ['count' => number_format($posts->total())]) }}
            </p>
        @endif
    </header>

    @if ($posts->isNotEmpty())
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

                    <div class="news-card-content {{ ! $post->image ? 'text-only-content' : '' }}">
                        @if ($post->categories->count())
                            <span class="news-card-category">{{ $post->categories->first()->name }}</span>
                        @endif

                        <h2 class="news-card-title">
                            <a href="{{ $post->url }}">{!! BaseHelper::clean($post->name) !!}</a>
                        </h2>

                        @if ($post->description)
                            <p class="news-card-excerpt {{ $post->image ? 'has-image' : '' }}">
                                {{ Str::limit($post->description, $post->image ? 100 : 200) }}
                            </p>
                        @endif

                        <div class="news-card-meta news-card-meta-row">
                            @if (class_exists($post->author_type) && $post->author)
                                <a href="{{ $post->author->url }}" class="author-link-small">{{ $post->author->name }}</a>
                                <span>•</span>
                            @endif
                            <span>{{ number_format($post->views ?? 0) }} views</span>
                            <span>•</span>
                            <span>{{ $post->created_at->diffForHumans() }}</span>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        @if ($posts->hasPages())
            <div class="pagination-wrapper">
                {!! $posts->links() !!}
            </div>
        @endif
    @else
        <div class="archive-empty">
            <p>{{ $searchQuery !== '' ? __('No posts found!') : __('No stories published yet.') }}</p>
        </div>
    @endif
</div>
