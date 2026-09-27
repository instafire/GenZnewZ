<?php

namespace App\Services;

use Botble\Base\Facades\MetaBox;
use Botble\Blog\Models\Post;
use Illuminate\Support\Str;

class PostSeoPresentationService
{
    protected const MAX_TITLE_LENGTH = 64;

    protected array $weakDescriptionPatterns = [
        '/\blet\'s be real\b/i',
        '/\bhere is the tea\b/i',
        '/\bwe are all\b/i',
        '/\bvibing\b/i',
        '/\bmain character\b/i',
        '/\bglow-?up\b/i',
        '/\bserious skincare goals\b/i',
        '/\bstupid-simple\b/i',
        '/\bdon\'t quit\b/i',
        '/\bnot panicking\b/i',
        '/\bthe real flex\b/i',
        '/\blet us talk about\b/i',
        '/\byou\'ve been\b/i',
    ];

    protected array $weakTitlePatterns = [
        '/\bomg\b/i',
        '/\bspill(?:ing)? the tea\b/i',
        '/\bvibing\b/i',
        '/\bgaslighting\b/i',
        '/\bnoob\b/i',
        '/\bpanic\b/i',
        '/\bmain character\b/i',
        '/\bsoft life\b/i',
    ];

    public function __construct(
        protected EditorialQualityService $editorialQualityService,
        protected AutomationContentGuardService $automationContentGuardService
    ) {
    }

    public function buildTitle(Post $post): string
    {
        $baseTitle = $this->normalizeTitle((string) $post->name);
        if ($baseTitle === '') {
            return 'Latest News | GenZ NewZ';
        }

        if (mb_strlen($baseTitle) <= self::MAX_TITLE_LENGTH) {
            $seoBase = $baseTitle;
        } else {
            $shortened = trim($this->editorialQualityService->shortenTitle($baseTitle, self::MAX_TITLE_LENGTH));
            $seoBase = $this->shouldPreferBaseTitle($baseTitle, $shortened)
                ? $this->truncateTitleNicely($baseTitle, self::MAX_TITLE_LENGTH)
                : $this->truncateTitleNicely($shortened, self::MAX_TITLE_LENGTH);
        }

        if (mb_strlen($seoBase) < 30) {
            foreach ($this->expansionCandidates($post) as $candidate) {
                if ($candidate === '') {
                    continue;
                }

                $expanded = $this->truncateTitleNicely($this->normalizeTitle($candidate), self::MAX_TITLE_LENGTH);
                if (mb_strlen($expanded) >= 30) {
                    $seoBase = $expanded;
                    break;
                }
            }
        }

        if ($seoBase === '') {
            $seoBase = $baseTitle;
        }

        return $seoBase . ' | GenZ NewZ';
    }

    public function buildDescription(Post $post): string
    {
        $description = $this->sanitizeAttributeText($this->cleanText((string) $post->description));

        if ($this->isUsableDescription($description)) {
            return $description;
        }

        $generated = $this->automationContentGuardService->buildMetaDescriptionFromContent(
            (string) $post->name,
            (string) $post->content,
            160
        );

        $generated = $this->sanitizeAttributeText($this->cleanText($generated));
        if ($this->isUsableDescription($generated)) {
            return Str::limit($generated, 160, '');
        }

        $fallback = $this->buildNeutralDescription($post);

        return Str::limit($fallback, 160, '');
    }

    protected function expansionCandidates(Post $post): array
    {
        $title = trim((string) $post->name);
        $slug = trim((string) optional($post->slugable)->key);
        $focusKeyword = $this->cleanText((string) MetaBox::getMetaData($post, 'focus_keyword', true));
        $description = $this->cleanText((string) $post->description);

        $candidates = [];

        $slugHeadline = '';
        if ($slug !== '') {
            $slugHeadline = $this->normalizeTitle(Str::headline(str_replace('-', ' ', $slug)));
        }

        if (mb_strlen($title) < 30 && $slugHeadline !== '' && ! $this->sameText($slugHeadline, $title) && ! $this->containsWeakTitlePhrases($slugHeadline)) {
            $candidates[] = $slugHeadline;
        }

        $expandedFromDescription = $this->expandFromDescription($title, $description);
        if ($expandedFromDescription !== '') {
            $candidates[] = $expandedFromDescription;
        }

        if ($focusKeyword !== '' && ! $this->sameText($focusKeyword, $title)) {
            $candidates[] = $focusKeyword;
        }

        if ($slugHeadline !== '' && ! $this->sameText($slugHeadline, $title) && ! in_array($slugHeadline, $candidates, true) && ! $this->containsWeakTitlePhrases($slugHeadline)) {
            $candidates[] = $slugHeadline;
        }

        return array_values(array_unique(array_filter($candidates)));
    }

    protected function expandFromDescription(string $title, string $description): string
    {
        if ($description === '') {
            return '';
        }

        $candidate = $description;
        if ($title !== '') {
            $pattern = '/^(?:' . preg_quote($title, '/') . '\s*:?\s*)+/i';
            $candidate = preg_replace($pattern, '', $candidate) ?: $candidate;
        }

        $candidate = preg_split('/[.!?]/', $candidate)[0] ?? $candidate;
        $candidate = $this->cleanText($candidate);

        if ($candidate === '') {
            return '';
        }

        if ($title !== '' && ! Str::startsWith(Str::lower($candidate), Str::lower($title))) {
            $candidate = $title . ': ' . $candidate;
        }

        return $candidate;
    }

    protected function cleanText(string $value): string
    {
        $value = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = preg_replace('/\s+/', ' ', trim($value));

        return (string) $value;
    }

    protected function normalizeTitle(string $value): string
    {
        $value = $this->cleanText($value);
        $value = strtr($value, [
            '—' => ' - ',
            '–' => ' - ',
            '“' => '"',
            '”' => '"',
            '’' => "'",
            '‘' => "'",
        ]);
        $value = preg_replace('/[\x{1F300}-\x{1FAFF}\x{2600}-\x{27BF}]/u', '', $value);
        $value = preg_replace('/\s+/', ' ', trim((string) $value));

        return trim((string) $value, " -:!?.,");
    }

    protected function truncateTitleNicely(string $value, int $maxLength): string
    {
        $value = trim($value);

        if ($value === '' || mb_strlen($value) <= $maxLength) {
            return $value;
        }

        $parts = preg_split('/\s*[:\-|]\s*/u', $value) ?: [];
        $parts = array_values(array_filter(array_map('trim', $parts)));

        if (count($parts) > 1) {
            $combined = $parts[0] . ': ' . $parts[1];
            if (mb_strlen($combined) <= $maxLength) {
                return $combined;
            }
        }

        $truncated = mb_substr($value, 0, $maxLength);
        $lastSpace = mb_strrpos($truncated, ' ');

        if ($lastSpace !== false && $lastSpace >= max(28, $maxLength - 15)) {
            $truncated = mb_substr($truncated, 0, $lastSpace);
        }

        $truncated = preg_replace('/\b(a|an|and|the|to|for|of|in|on|with|at|by)$/i', '', $truncated) ?: $truncated;

        return trim($truncated, " -:!?.,");
    }

    protected function shouldPreferBaseTitle(string $baseTitle, string $shortened): bool
    {
        if ($shortened === '') {
            return true;
        }

        if (mb_strlen($shortened) < 30) {
            return true;
        }

        return mb_strlen($shortened) < (int) floor(mb_strlen($baseTitle) * 0.6);
    }

    protected function containsWeakTitlePhrases(string $title): bool
    {
        foreach ($this->weakTitlePatterns as $pattern) {
            if (preg_match($pattern, $title)) {
                return true;
            }
        }

        return false;
    }

    protected function isUsableDescription(string $description): bool
    {
        if ($description === '' || mb_strlen($description) < 110 || mb_strlen($description) > 165) {
            return false;
        }

        if (! preg_match('/^[A-Z0-9"\']/', $description)) {
            return false;
        }

        if (! preg_match('/[.!?]$/', $description)) {
            return false;
        }

        foreach ($this->weakDescriptionPatterns as $pattern) {
            if (preg_match($pattern, $description)) {
                return false;
            }
        }

        return true;
    }

    protected function buildNeutralDescription(Post $post): string
    {
        $title = $this->truncateTitleNicely($this->normalizeTitle((string) $post->name), 72);
        $category = $post->relationLoaded('categories') ? $post->categories->first()?->name : $post->categories()->value('name');
        $categoryText = $category ? Str::lower((string) $category) . ' context' : 'context';

        $fallback = $title . ' explained with key facts, ' . $categoryText . ', and the main takeaways for GenZ NewZ readers.';

        return $this->sanitizeAttributeText($this->cleanText($fallback));
    }

    protected function sameText(string $left, string $right): bool
    {
        return Str::lower($this->cleanText($left)) === Str::lower($this->cleanText($right));
    }

    protected function sanitizeAttributeText(string $value): string
    {
        return str_replace('"', "'", $value);
    }
}
