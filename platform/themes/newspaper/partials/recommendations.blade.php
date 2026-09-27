@php
    use App\Services\RecommendationService;

    $recommendationService = app(RecommendationService::class);
    $recommendations = $recommendationService->getPersonalizedRecommendations($post, $limit ?? 4);
@endphp

@if ($recommendations->count() > 0)
    <aside class="recommendation-widget" aria-label="{{ __('More Like This') }}">
        <div class="recommendation-header">
            <h3 class="recommendation-title">{{ $title ?? __('More Like This') }}</h3>
            @if ($post->categories->count())
                <a href="{{ $post->categories->first()->url }}" class="recommendation-category-link">
                    {{ __('More in') }} {{ $post->categories->first()->name }} →
                </a>
            @endif
        </div>

        <div class="recommendation-grid">
            @foreach ($recommendations as $recommendedPost)
                <article class="recommendation-card">
                    @if ($recommendedPost->image)
                        @include('theme::partials.image', [
                            'image' => $recommendedPost->image,
                            'alt' => $recommendedPost->name,
                            'size' => 'small',
                            'class' => 'recommendation-image',
                            'imgClass' => 'recommendation-image',
                            'lazy' => true,
                        ])
                    @endif
                    <div class="recommendation-content">
                        @if ($recommendedPost->categories->count())
                            <span class="recommendation-category">{{ $recommendedPost->categories->first()->name }}</span>
                        @endif
                        <h4 class="recommendation-headline">
                            <a href="{{ $recommendedPost->url }}" class="recommendation-link" data-track-post="{{ $recommendedPost->id }}" data-track-source="recommendation_widget">{{ $recommendedPost->name }}</a>
                        </h4>
                        @if ($recommendedPost->description)
                            <p class="recommendation-excerpt">{{ Str::limit($recommendedPost->description, 90) }}</p>
                        @endif
                        <div class="recommendation-meta">
                            <span>{{ number_format($recommendedPost->views ?? 0) }} views</span>
                            <span>•</span>
                            <span>{{ $recommendedPost->created_at->diffForHumans() }}</span>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    </aside>
@endif
