@php
    use App\Services\EditorialProfileService;

    $authorName = strtolower((string) ($post->author->name ?? ''));
    $aiReporterId = method_exists($post, 'getMetaData') ? $post->getMetaData('ai_reporter_id', true) : null;
    $isAiPost = !empty($aiReporterId) || str_contains($authorName, 'genzai') || str_contains($authorName, ' ai');
    $detailed = $detailed ?? false;
    $reviewer = app(EditorialProfileService::class)->reviewerForPost($post);
@endphp

@if ($isAiPost)
    <span class="origin-badge-group {{ $detailed ? 'origin-badge-group-detailed' : '' }}" aria-label="AI generated and editor reviewed">
        <span class="origin-badge origin-badge-ai">AI-generated</span>
        @if ($detailed)
            <span class="origin-review-meta">
                <span>Reviewed by: {{ $reviewer['name'] }}, {{ $reviewer['job_title'] }}</span>
                <span>Updated: {{ optional($post->updated_at)->format('F j, Y') }}</span>
            </span>
        @else
            <span class="origin-badge origin-badge-review">Reviewed by {{ $reviewer['name'] }}</span>
        @endif
    </span>
@endif

