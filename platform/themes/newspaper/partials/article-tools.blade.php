@php
    // Read time is derived from the same normalised body the page renders, so
    // it cannot drift from what the reader actually sees.
    $articleBody = $normalizedArticleBody ?? (string) $post->content;
    $articleWordCount = str_word_count(strip_tags($articleBody));
    $articleReadMinutes = max(1, (int) ceil($articleWordCount / 200));

    $shareUrl = $post->url;
    $shareTitle = html_entity_decode(strip_tags((string) $post->name), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $shareEncodedUrl = rawurlencode($shareUrl);
    $shareEncodedTitle = rawurlencode($shareTitle);

    $shareTargets = [
        ['label' => 'X', 'short' => 'X', 'slug' => 'x', 'url' => 'https://twitter.com/intent/tweet?url=' . $shareEncodedUrl . '&text=' . $shareEncodedTitle],
        ['label' => 'Facebook', 'short' => 'f', 'slug' => 'facebook', 'url' => 'https://www.facebook.com/sharer/sharer.php?u=' . $shareEncodedUrl],
        ['label' => 'LinkedIn', 'short' => 'in', 'slug' => 'linkedin', 'url' => 'https://www.linkedin.com/sharing/share-offsite/?url=' . $shareEncodedUrl],
        ['label' => 'WhatsApp', 'short' => 'WA', 'slug' => 'whatsapp', 'url' => 'https://api.whatsapp.com/send?text=' . $shareEncodedTitle . '%20' . $shareEncodedUrl],
        ['label' => 'Email', 'short' => '✉', 'slug' => 'email', 'url' => 'mailto:?subject=' . $shareEncodedTitle . '&body=' . $shareEncodedUrl],
    ];
@endphp

{{-- Reading progress; driven by article-tools.js and hidden when the script does not run. --}}
<div class="reading-progress" id="readingProgress" role="progressbar" aria-label="Reading progress" aria-hidden="true">
    <span class="reading-progress-bar" id="readingProgressBar"></span>
</div>

<div class="article-utilities" data-article-tools>
    <div class="article-utilities-left">
        <span class="article-read-time" title="{{ number_format($articleWordCount) }} words">
            <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
            {{ $articleReadMinutes }} min read
        </span>
    </div>

    <div class="article-utilities-right">
        <button
            type="button"
            class="article-save-btn"
            data-save-article
            data-article-url="{{ $shareUrl }}"
            data-article-title="{{ $shareTitle }}"
            data-article-image="{{ $post->image ? RvMedia::getImageUrl($post->image, 'small', false, '') : '' }}"
            aria-pressed="false"
        >
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M6 3h12a1 1 0 0 1 1 1v17l-7-4-7 4V4a1 1 0 0 1 1-1z"/></svg>
            <span data-save-label>Save</span>
        </button>

        <div class="article-share">
            <button type="button" class="article-share-native" data-share-native hidden>
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 16V4"/><path d="M8 8l4-4 4 4"/><path d="M4 16v3a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-3"/></svg>
                Share
            </button>
            <span class="article-share-label" aria-hidden="true">Share</span>
            <div class="article-share-links">
                @foreach ($shareTargets as $target)
                    <a
                        class="article-share-link"
                        data-share-target="{{ $target['slug'] }}"
                        href="{{ $target['url'] }}"
                        target="_blank"
                        rel="noopener noreferrer nofollow"
                        aria-label="Share on {{ $target['label'] }}"
                        title="Share on {{ $target['label'] }}"
                    >{{ $target['short'] }}</a>
                @endforeach
                <button type="button" class="article-share-copy" data-copy-link aria-label="Copy link to this article">
                    <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="9" y="9" width="11" height="11" rx="2"/><path d="M5 15V5a2 2 0 0 1 2-2h10"/></svg>
                    <span data-copy-label>Copy link</span>
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Populated client-side from the article's own headings. --}}
<nav class="article-outline" id="articleOutline" aria-label="Article outline" hidden>
    <h2 class="article-outline-title">In this article</h2>
    <ol class="article-outline-list" id="articleOutlineList"></ol>
</nav>
