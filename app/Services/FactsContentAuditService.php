<?php

namespace App\Services;

use Botble\Blog\Models\Post;
use Illuminate\Support\Str;

class FactsContentAuditService
{
    protected array $titleRiskPatterns = [
        '/\b(hot off the press|buzz you should know|vibe check|spill(?:s|ing)? the tea)\b/i',
        '/\b(main character|flop era|glow up|chaos|wild)\b/i',
        '/\b(you need to know|you should know|you don\'t even know it)\b/i',
        '/\b(life hacks|ultimate guide|gen z guide)\b/i',
    ];

    protected array $slangPatterns = [
        '/\bbestie\b/i',
        '/\blow-key\b/i',
        '/\bhigh-key\b/i',
        '/\bvibe(?:s| check)?\b/i',
        '/\bthe tea\b/i',
        '/\bmain character\b/i',
        '/\bflop era\b/i',
        '/\bslay\b/i',
        '/\biconic\b/i',
        '/\bchaos\b/i',
        '/\bgiving\b/i',
        '/\bfam\b/i',
    ];

    protected array $sourcePhrases = [
        'according to',
        'reported by',
        'data from',
        'research from',
        'official statement',
        'officials said',
        'in a statement',
        'court filing',
        'press release',
        'survey found',
        'documents show',
    ];

    public function audit(Post $post): array
    {
        $title = trim((string) $post->name);
        $description = trim((string) $post->description);
        $html = (string) $post->content;
        $plain = $this->normalize(strip_tags($html));
        $wordCount = str_word_count($plain);
        $links = $this->extractLinks($html);
        $externalLinks = $this->countExternalLinks($links);
        $firstPersonCount = $this->countPattern('/\b(i|me|my|mine|we|our|ours|us)\b/i', $plain);
        $emojiCount = $this->countPattern('/[\x{1F300}-\x{1FAFF}\x{2600}-\x{27BF}]/u', $title . ' ' . $html);
        $slangHits = $this->countPatterns($this->slangPatterns, $title . "\n" . $plain);
        $sourcePhraseHits = $this->countSourcePhrases($plain);
        $searchIntentMismatch = $this->matchesAny($this->titleRiskPatterns, $title);
        $listicleStyle = preg_match('/^\s*(\d+[\.)]|[-*])\s/m', strip_tags($html)) === 1;

        $score = 100;
        $issues = [];

        if ($searchIntentMismatch) {
            $score -= 20;
            $issues[] = 'title reads like clickbait or lifestyle content instead of a search-focused article';
        }

        if ($slangHits >= 3) {
            $score -= 20;
            $issues[] = 'heavy Gen Z slang in body copy';
        } elseif ($slangHits >= 1) {
            $score -= 10;
            $issues[] = 'some slang or overly conversational phrasing';
        }

        if ($firstPersonCount >= 10) {
            $score -= 20;
            $issues[] = 'strong first-person/opinion voice for a facts-style page';
        } elseif ($firstPersonCount >= 4) {
            $score -= 10;
            $issues[] = 'noticeable first-person framing';
        }

        if ($emojiCount >= 3) {
            $score -= 15;
            $issues[] = 'emoji-heavy presentation';
        } elseif ($emojiCount >= 1) {
            $score -= 5;
            $issues[] = 'contains emoji styling';
        }

        if ($externalLinks < 1) {
            $score -= 20;
            $issues[] = 'no external source links';
        }

        if ($sourcePhraseHits < 1) {
            $score -= 15;
            $issues[] = 'weak source attribution in the prose';
        }

        if ($listicleStyle) {
            $score -= 10;
            $issues[] = 'listicle formatting weakens search/news intent fit';
        }

        if (mb_strlen($description) < 120) {
            $score -= 10;
            $issues[] = 'stored description is shorter than target range';
        }

        $score = max(0, $score);

        return [
            'id' => $post->id,
            'title' => $title,
            'slug' => $post->slugable?->key,
            'created_at' => optional($post->created_at)->toDateString(),
            'score' => $score,
            'issues' => $issues,
            'recommendation' => $this->recommendation($score, $searchIntentMismatch, $slangHits, $firstPersonCount),
            'metrics' => [
                'word_count' => $wordCount,
                'external_links' => $externalLinks,
                'source_phrase_hits' => $sourcePhraseHits,
                'first_person_count' => $firstPersonCount,
                'slang_hits' => $slangHits,
                'emoji_count' => $emojiCount,
                'listicle_style' => $listicleStyle,
            ],
        ];
    }

    protected function recommendation(int $score, bool $searchIntentMismatch, int $slangHits, int $firstPersonCount): string
    {
        if ($score < 45 || ($searchIntentMismatch && $slangHits >= 3)) {
            return 'unpublish_or_rebuild';
        }

        if ($score < 70 || $firstPersonCount >= 10) {
            return 'rewrite';
        }

        if ($score < 85) {
            return 'refresh';
        }

        return 'keep';
    }

    protected function extractLinks(string $html): array
    {
        preg_match_all('/<a\b[^>]*href=["\']([^"\']+)["\'][^>]*>/i', $html, $matches);

        return $matches[1] ?? [];
    }

    protected function countExternalLinks(array $links): int
    {
        $host = parse_url((string) config('app.url'), PHP_URL_HOST);

        return count(array_filter($links, function (string $link) use ($host) {
            $linkHost = parse_url($link, PHP_URL_HOST);

            if ($linkHost === null) {
                return false;
            }

            return $host === null || strcasecmp($host, (string) $linkHost) !== 0;
        }));
    }

    protected function countPatterns(array $patterns, string $text): int
    {
        $count = 0;

        foreach ($patterns as $pattern) {
            $count += $this->countPattern($pattern, $text);
        }

        return $count;
    }

    protected function countPattern(string $pattern, string $text): int
    {
        preg_match_all($pattern, $text, $matches);

        return count($matches[0] ?? []);
    }

    protected function countSourcePhrases(string $plain): int
    {
        $count = 0;

        foreach ($this->sourcePhrases as $phrase) {
            $count += substr_count(Str::lower($plain), $phrase);
        }

        return $count;
    }

    protected function matchesAny(array $patterns, string $text): bool
    {
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text)) {
                return true;
            }
        }

        return false;
    }

    protected function normalize(string $text): string
    {
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', trim($text));

        return (string) $text;
    }
}
