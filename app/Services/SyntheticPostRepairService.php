<?php

namespace App\Services;

use Botble\Blog\Models\Post;
use Illuminate\Support\Str;

class SyntheticPostRepairService
{
    protected array $syntheticTitlePatterns = [
        'lowercase topic-code prefix in title' => '/^[a-z][a-z&\-\s]{3,50}\s(?:[a-z0-9]{5,8}|\d{3,6})\s*:\s/',
        'lowercase topic label prefix in title' => '/^[a-z][a-z&\-\s]{3,30}:\s[A-Z]/',
        'lowercase synthetic news prefix' => '/^[a-z][a-z&\-\s]{3,30}\s(?:analysis|insights|trending|market|developments|news|update|report)\b/',
    ];

    protected array $syntheticBodyPatterns = [
        'focus keyword artifact' => '/\bfocus keyword\b/i',
        'fake classification language' => '/\b(?:reference code|classification system|analysis framework|identified as|categorized as)\b/i',
        'generic market placeholder' => '/\b(?:industry|market|sector)\s+\d{3,6}\b/i',
        'automation code phrase repeated in prose' => '/\b(?:analysis|insights|trending|market|developments|news|update|latest|report)\s+(?:[a-z]*\d[a-z0-9]*|\d{3,6})\b/i',
        'unexpected non-latin artifact' => '/[\x{4e00}-\x{9fff}]/u',
    ];

    protected array $unsalvageableHeadingPatterns = [
        '/^understanding\b/i',
        '/\b(?:a comprehensive (?:overview|market analysis)|market evolution|industry transformations|sector insights|current landscape|latest market analysis)\b/i',
        '/\b(?:travel|business|fashion|politics|sports|movies|music|health|science|crypto)\s+(?:industry|market|sector)\b/i',
        '/\b(?:industry|market|sector)\s+\d{3,6}\b/i',
    ];

    protected array $paragraphRemovalPatterns = [
        '/<p\b[^>]*>.*?\bfocus keyword\b.*?<\/p>/is',
        '/<p\b[^>]*>.*?\b(?:reference code|classification system|analysis framework|identified as|categorized as)\b.*?<\/p>/is',
        '/<p\b[^>]*>.*?\b(?:industry|market|sector)\s+\d{3,6}\b.*?<\/p>/is',
        '/<p\b[^>]*>.*?captures this evolution perfectly.*?<\/p>/is',
        '/<p\b[^>]*>.*?represents a (?:specialized|comprehensive) (?:sector|classification|analysis framework).*?<\/p>/is',
    ];

    public function analyzeText(string $title, ?string $description = null, ?string $content = null): array
    {
        $description = (string) $description;
        $content = $this->stripNonEditorialBlocks((string) $content);
        $plainContent = $this->normalizeText(strip_tags($content));
        $suspiciousTokens = $this->extractSuspiciousTokens($title);

        $titleReasons = $this->matchPatternLabels($this->syntheticTitlePatterns, $title);
        $bodyReasons = $this->matchPatternLabels($this->syntheticBodyPatterns, $plainContent);
        $tokenHitsInBody = $this->countTokenHits($plainContent, $suspiciousTokens);

        if ($tokenHitsInBody >= 2) {
            $bodyReasons[] = 'suspicious tracking codes repeated in body copy';
        }

        return [
            'flagged' => $titleReasons !== [] || $bodyReasons !== [],
            'title_reasons' => array_values(array_unique($titleReasons)),
            'body_reasons' => array_values(array_unique($bodyReasons)),
            'suspicious_tokens' => $suspiciousTokens,
            'token_hits_in_body' => $tokenHitsInBody,
        ];
    }

    public function proposeRepair(
        Post $post,
        EditorialQualityService $editorialQualityService,
        AutomationContentGuardService $guard
    ): array {
        $analysis = $this->analyzeText((string) $post->name, (string) $post->description, (string) $post->content);

        if (! $analysis['flagged']) {
            return [
                'flagged' => false,
                'action' => 'keep',
                'reasons' => [],
            ];
        }

        $heading = $this->extractPrimaryHeading((string) $post->content);
        $candidateTitle = $heading ? $this->cleanHeading($heading, $editorialQualityService) : '';
        $cleanedContent = $this->cleanContent((string) $post->content, $candidateTitle ?: (string) $post->name, $analysis['suspicious_tokens']);
        $cleanedPlain = $this->normalizeText(strip_tags($cleanedContent));
        $cleanedAnalysis = $this->analyzeText($candidateTitle ?: (string) $post->name, (string) $post->description, $cleanedContent);
        $candidateTokens = $this->extractSuspiciousTokens($candidateTitle);

        $canSalvage = $candidateTitle !== ''
            && $candidateTokens === []
            && ! $this->hasSyntheticTokenPhrase($candidateTitle)
            && ! $this->isUnsalvageableHeading($candidateTitle)
            && $this->hasMeaningfulTitleOverlap((string) $post->name, $candidateTitle)
            && count($analysis['body_reasons']) <= 2
            && $analysis['token_hits_in_body'] <= 1
            && $cleanedAnalysis['body_reasons'] === []
            && str_word_count($cleanedPlain) >= 350;

        if (! $canSalvage) {
            return [
                'flagged' => true,
                'action' => 'draft',
                'reasons' => array_values(array_unique(array_merge($analysis['title_reasons'], $analysis['body_reasons']))),
                'old_title' => $post->name,
            ];
        }

        $newDescription = $guard->buildMetaDescriptionFromContent($candidateTitle, $cleanedContent);

        return [
            'flagged' => true,
            'action' => 'repair',
            'reasons' => array_values(array_unique(array_merge($analysis['title_reasons'], $analysis['body_reasons']))),
            'old_title' => $post->name,
            'new_title' => $candidateTitle,
            'new_description' => $newDescription,
            'new_content' => $cleanedContent,
            'new_focus_keyword' => Str::limit($candidateTitle, 100, ''),
        ];
    }

    protected function matchPatternLabels(array $patterns, string $text): array
    {
        $matches = [];

        foreach ($patterns as $label => $pattern) {
            if (preg_match($pattern, $text)) {
                $matches[] = $label;
            }
        }

        return $matches;
    }

    protected function extractPrimaryHeading(string $content): ?string
    {
        if (preg_match('/<h2\b[^>]*>(.*?)<\/h2>/is', $content, $matches) !== 1) {
            return null;
        }

        $heading = trim(html_entity_decode(strip_tags($matches[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        return $heading !== '' ? $heading : null;
    }

    protected function cleanHeading(string $heading, EditorialQualityService $editorialQualityService): string
    {
        $heading = preg_replace('/\s+/', ' ', trim($heading));
        $heading = preg_replace('/:\s*a comprehensive (?:overview|market analysis)\s*$/i', '', $heading);
        $heading = preg_replace('/:\s*latest market analysis\s*$/i', '', $heading);
        $heading = preg_replace('/^understanding\s+/i', '', $heading);
        $heading = preg_replace('/^the current\s+/i', '', $heading);
        $heading = preg_replace('/^the evolving landscape of\s+/i', '', $heading);
        $heading = trim((string) $heading, " -:!?.,");

        return $editorialQualityService->shortenTitle($heading, 68);
    }

    protected function isUnsalvageableHeading(string $heading): bool
    {
        foreach ($this->unsalvageableHeadingPatterns as $pattern) {
            if (preg_match($pattern, $heading)) {
                return true;
            }
        }

        return false;
    }

    protected function cleanContent(string $content, string $newTitle, array $suspiciousTokens): string
    {
        $updated = $this->stripNonEditorialBlocks($content);

        foreach ($this->paragraphRemovalPatterns as $pattern) {
            $updated = preg_replace($pattern, '', $updated);
        }

        foreach ($suspiciousTokens as $token) {
            if ($token === '' || ctype_digit($token) && $this->isLikelyYear((int) $token)) {
                continue;
            }

            $quotedToken = preg_quote($token, '/');
            $updated = preg_replace('/<p\b[^>]*>[^<]*' . $quotedToken . '.*?<\/p>/is', '', $updated);
        }

        if ($newTitle !== '' && preg_match('/<h2\b[^>]*>.*?<\/h2>/is', $updated) === 1) {
            $updated = preg_replace('/<h2\b[^>]*>.*?<\/h2>/is', '<h2>' . e($newTitle) . '</h2>', $updated, 1);
        }

        $updated = preg_replace('/(<\/p>)\s*(<p\b[^>]*>\s*<\/p>)+/i', '$1', $updated);
        $updated = preg_replace('/\n{3,}/', "\n\n", $updated);

        return trim((string) $updated);
    }

    protected function stripNonEditorialBlocks(string $content): string
    {
        $content = preg_replace('/<!--\s*genznewz:topic-cluster-links:start\s*-->.*?<!--\s*genznewz:topic-cluster-links:end\s*-->/is', '', $content);
        $content = preg_replace('/<div\b[^>]*class="[^"]*\btopic-cluster-links\b[^"]*"[^>]*>.*?<\/div>/is', '', $content);

        return (string) $content;
    }

    protected function extractSuspiciousTokens(string $title): array
    {
        preg_match_all('/\b[a-z0-9]{3,10}\b/i', $title, $matches);

        $tokens = [];

        foreach ($matches[0] ?? [] as $token) {
            $token = trim((string) $token);
            if ($token === '') {
                continue;
            }

            if (preg_match('/[a-z]/i', $token) && preg_match('/\d/', $token) && strlen($token) >= 5) {
                $tokens[] = $token;
                continue;
            }

            if (ctype_digit($token) && strlen($token) >= 3 && ! $this->isLikelyYear((int) $token)) {
                $tokens[] = $token;
            }
        }

        return array_values(array_unique($tokens));
    }

    protected function countTokenHits(string $text, array $tokens): int
    {
        $count = 0;

        foreach ($tokens as $token) {
            $count += preg_match_all('/\b' . preg_quote($token, '/') . '\b/i', $text);
        }

        return $count;
    }

    protected function normalizeText(string $text): string
    {
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', trim($text));

        return (string) $text;
    }

    protected function isLikelyYear(int $value): bool
    {
        return $value >= 1900 && $value <= 2100;
    }

    protected function hasMeaningfulTitleOverlap(string $originalTitle, string $candidateTitle): bool
    {
        $originalTokens = $this->extractTitleKeywords($originalTitle);
        $candidateTokens = $this->extractTitleKeywords($candidateTitle);

        return array_intersect($originalTokens, $candidateTokens) !== [];
    }

    protected function extractTitleKeywords(string $title): array
    {
        preg_match_all('/\b[a-z][a-z0-9-]{3,}\b/i', Str::lower($title), $matches);

        $stopwords = [
            'analysis', 'alert', 'breaking', 'current', 'deep', 'developments', 'dive',
            'exclusive', 'guide', 'industry', 'insights', 'latest', 'market', 'major',
            'news', 'overview', 'report', 'sector', 'trending', 'update', 'understanding',
            'what', 'exactly', 'this', 'that', 'with', 'from', 'into', 'over', 'after',
            'just', 'gets', 'real', 'rise', 'rising', 'more', 'why', 'how', 'year',
        ];

        $keywords = [];

        foreach ($matches[0] ?? [] as $token) {
            if (in_array($token, $stopwords, true)) {
                continue;
            }

            $keywords[] = $token;
        }

        return array_values(array_unique($keywords));
    }

    protected function hasSyntheticTokenPhrase(string $title): bool
    {
        return preg_match('/\b(?:analysis|insights|trending|market|developments|news|update|latest|report)\s+[a-z]{5,8}\b/i', $title) === 1
            || preg_match('/\b[a-z]{5,8}\s+report\b/i', $title) === 1;
    }
}
