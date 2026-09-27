<?php

namespace App\Services;

class AutomationContentGuardService
{
    protected array $disposablePatterns = [
        '/\bapi\s*test\b/i',
        '/\btest\s+(article|post|story|content)\b/i',
        '/\bdummy\b/i',
        '/\bplaceholder\b/i',
        '/\blorem ipsum\b/i',
        '/\bhello world\b/i',
        '/\$\(date[^)]*\)/i',
        '/\bexample\s+article\b/i',
        '/\bsample\s+post\b/i',
        '/\bold\s+api\s+test\b/i',
    ];

    protected array $aiArtifactPatterns = [
        '/\bas an ai language model\b/i',
        '/\bi (?:can(?:not|\'t)|do not|don\'t) have (?:real[-\s]?time|live) (?:data|access)\b/i',
        '/\bbased on my training data\b/i',
        '/\bi cannot browse the web\b/i',
        '/\bhere(\'s| is) (?:the )?(?:article|draft)\b/i',
    ];

    protected array $editorialTonePatterns = [
        '/\bbestie\b/i',
        '/\bspill(?:s|ing)? the tea\b/i',
        '/\bmain character (?:energy|syndrome)?\b/i',
        '/\bflop era\b/i',
        '/\blow-key\b/i',
        '/\bhigh-key\b/i',
        '/\bvibe check\b/i',
        '/\bchaos\b/i',
    ];

    protected array $clickbaitTitlePatterns = [
        '/\b(hot off the press|buzz you should know)\b/i',
        '/\b(you need to know|you should know|you don\'t even know it)\b/i',
        '/\b(gen z guide|ultimate guide|life hacks)\b/i',
        '/\b(the tea|vibe check|main character)\b/i',
    ];

    protected array $instructionalTitlePatterns = [
        '/\b(how to|tutorial|step[-\s]?by[-\s]?step)\b.{0,24}\b(script|scripts|bot|workflow|automation|crawler|scraper|api client|wrapper|integration)\b/i',
        '/\b(build|create|write|generate)\b.{0,24}\b(script|scripts|bot|workflow|automation|crawler|scraper|api client|wrapper|integration)\b/i',
        '/\b(script|scripts|automation|bot|crawler|scraper|api client|wrapper|integration)\b.{0,24}\b(template|starter|boilerplate|example|sample)\b/i',
    ];

    protected array $codeArtifactPatterns = [
        '/```[\s\S]*?```/m',
        '/<pre\b[^>]*>[\s\S]*?<\/pre>/i',
        '/<code\b[^>]*>[\s\S]*?<\/code>/i',
        '/^\s*#!\/(?:usr\/bin\/env )?(?:bash|sh|python|php|node|ruby)\b/im',
        '/^\s*(?:curl|wget|npm|pnpm|yarn|composer|pip|python3?|node|php artisan|git|docker|kubectl)\b/im',
    ];

    protected array $instructionalWorkflowPatterns = [
        '/\bcopy (?:this|the) (?:script|code|command)\b/i',
        '/\bpaste (?:this|the) (?:script|code)\b/i',
        '/\brun (?:this|the following|the) (?:script|command)\b/i',
        '/\binstall (?:the|this) (?:package|dependency|tool)\b/i',
        '/\bset up (?:a|the) (?:cron job|workflow|bot|automation)\b/i',
        '/\bsave (?:this|the) (?:script|file)\b/i',
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

    protected array $weakAttributionPatterns = [
        '/\bexperts say\b/i',
        '/\bsources say\b/i',
        '/\bits being said\b/i',
        '/\bmany believe\b/i',
        '/\bpeople are saying\b/i',
        '/\breports suggest\b/i',
    ];

    protected array $unsupportedClaimPatterns = [
        '/\b(always|never|proven|guaranteed|undeniable|everyone knows)\b/i',
        '/\bno doubt\b/i',
        '/\bcannot fail\b/i',
    ];

    protected array $authoritativeDomains = [
        'reuters.com',
        'apnews.com',
        'bbc.com',
        'npr.org',
        'nytimes.com',
        'wsj.com',
        'bloomberg.com',
        'ft.com',
        'theguardian.com',
        'cnn.com',
        'abcnews.go.com',
        'cbsnews.com',
        'nbcnews.com',
        'fda.gov',
        'cdc.gov',
        'nih.gov',
        'noaa.gov',
        'nasa.gov',
        'sec.gov',
        'congress.gov',
        'whitehouse.gov',
        'justice.gov',
        'europa.eu',
        'un.org',
        'worldbank.org',
        'imf.org',
        'oecd.org',
        'who.int',
        'nature.com',
        'science.org',
        'arxiv.org',
    ];

    protected array $socialOrShortDomains = [
        'x.com',
        'twitter.com',
        'facebook.com',
        'instagram.com',
        'tiktok.com',
        'youtube.com',
        'linkedin.com',
        'pinterest.com',
        'reddit.com',
        'bit.ly',
        'tinyurl.com',
        't.co',
        'ow.ly',
        'goo.gl',
        'lnkd.in',
    ];

    public function validate(array $data): array
    {
        $title = trim((string) ($data['title'] ?? ''));
        $description = trim((string) ($data['description'] ?? ''));
        $content = (string) ($data['content'] ?? '');
        $focusKeyword = trim((string) ($data['focus_keyword'] ?? ''));

        $errors = [];
        $warnings = [];

        $plainContent = $this->normalizeText(strip_tags($content));
        $wordCount = str_word_count($plainContent);
        $headingCount = preg_match_all('/<h2\b[^>]*>/i', $content, $matches);
        $paragraphCount = preg_match_all('/<p\b[^>]*>/i', $content, $paragraphMatches);
        $sentenceCount = $this->countSentences($plainContent);
        $links = $this->extractLinks($content);
        $internalLinks = $this->countInternalLinks($links);
        $externalLinks = $this->countExternalLinks($links);
        $httpsExternalLinks = $this->countHttpsExternalLinks($links);
        $authoritativeExternalLinks = $this->countAuthoritativeExternalLinks($links);
        $socialSourceLinks = $this->countSocialOrShortLinks($links);
        $keywordMentions = $focusKeyword !== '' ? $this->countKeywordMentions($plainContent, $focusKeyword) : 0;
        $firstPersonCount = $this->countPattern('/\b(i|me|my|mine|we|our|ours)\b/i', $plainContent);
        $toneHits = $this->countPatterns($this->editorialTonePatterns, $title . "\n" . $description . "\n" . $plainContent);
        $sourcePhraseHits = $this->countSourcePhrases($plainContent);
        $quoteCount = $this->countPattern('/"[^"]{20,}"/u', $content);
        $weakAttributionHits = $this->countPatterns($this->weakAttributionPatterns, $plainContent);
        $unsupportedClaimHits = $this->countPatterns($this->unsupportedClaimPatterns, $plainContent);
        $instructionalTitleHits = $this->countPatterns($this->instructionalTitlePatterns, $title . "\n" . $focusKeyword);
        $codeArtifactHits = $this->countPatterns($this->codeArtifactPatterns, $content);
        $instructionalWorkflowHits = $this->countPatterns($this->instructionalWorkflowPatterns, $title . "\n" . $description . "\n" . $plainContent);

        $blockedReasons = $this->getDisposableReasons($title, $description, $content);
        if ($blockedReasons !== []) {
            $errors[] = 'Disposable/test content markers detected: ' . implode('; ', $blockedReasons);
        }

        foreach ($this->aiArtifactPatterns as $pattern) {
            if (preg_match($pattern, $title . "\n" . $description . "\n" . $content)) {
                $errors[] = 'Draft contains AI-assistant artifact language. Remove meta/disclaimer phrasing before publishing.';
                break;
            }
        }

        foreach ($this->clickbaitTitlePatterns as $pattern) {
            if (preg_match($pattern, $title)) {
                $errors[] = 'Title reads like clickbait or lifestyle filler. Use a direct, search-friendly headline.';
                break;
            }
        }

        if ($instructionalTitleHits > 0) {
            $errors[] = 'Topic reads like a script/tutorial deliverable. Publish reported articles, not automation guides or client-builder walkthroughs.';
        }

        if ($codeArtifactHits > 0) {
            $errors[] = 'Submit finished article prose only. Do not include scripts, commands, code blocks, or API call samples in the article body.';
        }

        if ($instructionalWorkflowHits >= 2) {
            $errors[] = 'Draft reads like copy-paste automation instructions. Replace tool steps with sourced reporting and narrative analysis.';
        }

        $syntheticAnalysis = app(SyntheticPostRepairService::class)->analyzeText($title, $description, $content);

        if ($syntheticAnalysis['title_reasons'] !== []) {
            $errors[] = 'Title matches a synthetic automation template. Remove tracking codes, generic "Deep Dive" framing, and market-report filler.';
        }

        if ($syntheticAnalysis['body_reasons'] !== []) {
            $errors[] = 'Draft contains synthetic automation artifacts in the body. Remove focus-keyword placeholders, fake classification language, and tracking-code references.';
        }

        if ($wordCount < 650) {
            $errors[] = "Automation minimum is 650 words. Current draft: {$wordCount} words.";
        } elseif ($wordCount < 800) {
            $warnings[] = "Word count is only {$wordCount}. Aim for 800+ words for stronger topical depth.";
        }

        if ($headingCount < 2) {
            $errors[] = 'Include at least two meaningful H2 subheadings in the article body.';
        }

        if ($paragraphCount < 5) {
            $errors[] = 'Include at least five substantial paragraphs in the article body.';
        }

        if ($sentenceCount < 12) {
            $errors[] = 'Draft is too shallow. Include at least 12 clear sentences with reporting context.';
        }

        if ($externalLinks < 1) {
            $errors[] = 'Include at least one authoritative external source link in the article body.';
        } elseif ($externalLinks < 2) {
            $warnings[] = 'Only one external source found. Add a second source when possible to improve factual confidence.';
        }

        if ($httpsExternalLinks < 1) {
            $errors[] = 'Use at least one HTTPS external source link to avoid unverifiable or insecure citations.';
        }

        if ($externalLinks > 0 && $socialSourceLinks >= $externalLinks) {
            $errors[] = 'External links cannot be only social media or short-link URLs. Add at least one direct source URL.';
        }

        if ($sourcePhraseHits < 1) {
            $errors[] = 'Add explicit source attribution in the prose with phrasing like "according to" or "reported by".';
        } elseif ($sourcePhraseHits < 2) {
            $warnings[] = 'Add at least one more explicit attribution phrase to strengthen factual traceability.';
        }

        if ($authoritativeExternalLinks < 1) {
            $warnings[] = 'Add at least one top-tier or primary source link (.gov/.edu or major newsroom) for stronger factual trust.';
        }

        if ($quoteCount > 0 && $sourcePhraseHits < 1) {
            $errors[] = 'Quoted material is present without attribution language. Cite who said it and where.';
        }

        if ($weakAttributionHits >= 2) {
            $warnings[] = 'Vague attribution detected (for example "experts say"). Name the specific source outlet or institution.';
        }

        if ($unsupportedClaimHits >= 2) {
            $warnings[] = 'Absolute claims detected (always/never/proven). Add qualifiers or supporting evidence.';
        }

        if ($internalLinks < 1) {
            $warnings[] = 'Add at least one relevant internal link to strengthen crawl paths and topic clusters.';
        }

        if ($focusKeyword !== '' && $keywordMentions < 2) {
            $warnings[] = "Use the focus keyword naturally at least twice in the article body. Current mentions: {$keywordMentions}.";
        }

        if ($description !== '' && $this->normalizeText($description) === $this->normalizeText($title)) {
            $errors[] = 'Meta description cannot just repeat the title.';
        }

        if ($this->hasExcessiveRepetition($plainContent)) {
            $warnings[] = 'Draft has repeated phrasing. Rewrite repeated sentences before publishing.';
        }

        if ($toneHits >= 2) {
            $errors[] = 'Draft leans too hard on slang or influencer-style phrasing for a news article.';
        } elseif ($toneHits === 1) {
            $warnings[] = 'Tone is drifting into slang. Keep phrasing tighter and more newsroom-neutral.';
        }

        if ($firstPersonCount >= 8) {
            $errors[] = 'Draft is too first-person for a news explainer. Rewrite in reported third-person voice.';
        } elseif ($firstPersonCount >= 4) {
            $warnings[] = 'Draft uses noticeable first-person framing. Reduce opinion voice unless the format explicitly calls for it.';
        }

        return [
            'passed' => $errors === [],
            'errors' => $errors,
            'warnings' => $warnings,
            'metrics' => [
                'word_count' => $wordCount,
                'h2_count' => $headingCount,
                'paragraph_count' => $paragraphCount,
                'sentence_count' => $sentenceCount,
                'internal_links' => $internalLinks,
                'external_links' => $externalLinks,
                'https_external_links' => $httpsExternalLinks,
                'authoritative_external_links' => $authoritativeExternalLinks,
                'social_or_short_links' => $socialSourceLinks,
                'focus_keyword_mentions' => $keywordMentions,
                'source_phrase_hits' => $sourcePhraseHits,
                'quote_count' => $quoteCount,
                'weak_attribution_hits' => $weakAttributionHits,
                'unsupported_claim_hits' => $unsupportedClaimHits,
                'tone_hits' => $toneHits,
                'first_person_count' => $firstPersonCount,
                'instructional_title_hits' => $instructionalTitleHits,
                'code_artifact_hits' => $codeArtifactHits,
                'instructional_workflow_hits' => $instructionalWorkflowHits,
            ],
        ];
    }

    public function isDisposableTestContent(string $title, ?string $description = null, ?string $content = null): bool
    {
        return $this->getDisposableReasons($title, $description, $content) !== [];
    }

    public function getDisposableReasons(string $title, ?string $description = null, ?string $content = null): array
    {
        $reasons = [];
        $combined = implode("\n", array_filter([$title, $description, strip_tags((string) $content)]));
        $normalizedTitle = $this->normalizeText($title);

        if (preg_match('/^test(?:\s*\d+|[-_]?\d+)?$/i', trim($title))) {
            $reasons[] = 'title is a plain test stub';
        }

        if ($normalizedTitle === 'test' || $normalizedTitle === 'test article') {
            $reasons[] = 'title is a disposable test label';
        }

        foreach ($this->disposablePatterns as $pattern) {
            if (preg_match($pattern, $combined)) {
                $reasons[] = 'matched pattern ' . trim($pattern, '/i');
            }
        }

        return array_values(array_unique($reasons));
    }

    public function buildMetaDescriptionFromContent(string $title, string $content, int $maxLength = 160): string
    {
        $plain = $this->normalizeText(strip_tags($content));
        if ($plain === '') {
            return $title;
        }

        $sentences = preg_split('/(?<=[.!?])\s+/', $plain) ?: [];
        $candidate = '';

        foreach ($sentences as $sentence) {
            $sentence = trim($sentence);
            if ($sentence === '') {
                continue;
            }

            $next = trim($candidate . ' ' . $sentence);
            if (mb_strlen($next) > $maxLength) {
                break;
            }

            $candidate = $next;

            if (mb_strlen($candidate) >= 120) {
                break;
            }
        }

        if ($candidate === '') {
            $candidate = mb_substr($plain, 0, $maxLength);
        }

        $candidate = rtrim($candidate, ' ,;:-');

        if (mb_strlen($candidate) > $maxLength) {
            $candidate = rtrim(mb_substr($candidate, 0, $maxLength - 1), ' ,;:-') . '…';
        }

        return $candidate;
    }

    protected function normalizeText(string $text): string
    {
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', trim(strtolower($text)));

        return (string) $text;
    }

    protected function extractLinks(string $content): array
    {
        preg_match_all('/<a\b[^>]*href=["\']([^"\']+)["\'][^>]*>/i', $content, $matches);

        return $matches[1] ?? [];
    }

    protected function countInternalLinks(array $links): int
    {
        $host = parse_url((string) config('app.url'), PHP_URL_HOST);

        return count(array_filter($links, function (string $link) use ($host) {
            $linkHost = parse_url($link, PHP_URL_HOST);

            if ($linkHost === null) {
                return str_starts_with($link, '/');
            }

            return $host !== null && strcasecmp($linkHost, (string) $host) === 0;
        }));
    }

    protected function countExternalLinks(array $links): int
    {
        $host = parse_url((string) config('app.url'), PHP_URL_HOST);

        return count(array_filter($links, function (string $link) use ($host) {
            $linkHost = parse_url($link, PHP_URL_HOST);

            if ($linkHost === null) {
                return false;
            }

            return $host === null || strcasecmp($linkHost, (string) $host) !== 0;
        }));
    }

    protected function countHttpsExternalLinks(array $links): int
    {
        $host = parse_url((string) config('app.url'), PHP_URL_HOST);

        return count(array_filter($links, function (string $link) use ($host) {
            $linkHost = parse_url($link, PHP_URL_HOST);

            if ($linkHost === null) {
                return false;
            }

            if ($host !== null && strcasecmp($linkHost, (string) $host) === 0) {
                return false;
            }

            return strtolower((string) parse_url($link, PHP_URL_SCHEME)) === 'https';
        }));
    }

    protected function countAuthoritativeExternalLinks(array $links): int
    {
        $host = $this->normalizeHost(parse_url((string) config('app.url'), PHP_URL_HOST));

        return count(array_filter($links, function (string $link) use ($host) {
            $linkHost = $this->normalizeHost(parse_url($link, PHP_URL_HOST));

            if ($linkHost === null) {
                return false;
            }

            if ($host !== null && $linkHost === $host) {
                return false;
            }

            if (str_ends_with($linkHost, '.gov') || str_ends_with($linkHost, '.edu')) {
                return true;
            }

            return $this->hostMatchesAnyDomain($linkHost, $this->authoritativeDomains);
        }));
    }

    protected function countSocialOrShortLinks(array $links): int
    {
        $host = $this->normalizeHost(parse_url((string) config('app.url'), PHP_URL_HOST));

        return count(array_filter($links, function (string $link) use ($host) {
            $linkHost = $this->normalizeHost(parse_url($link, PHP_URL_HOST));

            if ($linkHost === null) {
                return false;
            }

            if ($host !== null && $linkHost === $host) {
                return false;
            }

            return $this->hostMatchesAnyDomain($linkHost, $this->socialOrShortDomains);
        }));
    }

    protected function countKeywordMentions(string $content, string $focusKeyword): int
    {
        $normalizedContent = $this->normalizeText($content);
        $normalizedKeyword = $this->normalizeText($focusKeyword);

        if ($normalizedContent === '' || $normalizedKeyword === '') {
            return 0;
        }

        return substr_count($normalizedContent, $normalizedKeyword);
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

    protected function countSourcePhrases(string $content): int
    {
        $count = 0;

        foreach ($this->sourcePhrases as $phrase) {
            $count += substr_count($content, $phrase);
        }

        return $count;
    }

    protected function normalizeHost(?string $host): ?string
    {
        if (!is_string($host) || trim($host) === '') {
            return null;
        }

        $host = strtolower(trim($host));

        if (str_starts_with($host, 'www.')) {
            $host = substr($host, 4);
        }

        return $host === '' ? null : $host;
    }

    protected function hostMatchesAnyDomain(string $host, array $domains): bool
    {
        foreach ($domains as $domain) {
            $domain = strtolower(trim($domain));

            if ($domain === '') {
                continue;
            }

            if ($host === $domain || str_ends_with($host, '.' . $domain)) {
                return true;
            }
        }

        return false;
    }

    protected function hasExcessiveRepetition(string $content): bool
    {
        $sentences = preg_split('/(?<=[.!?])\s+/', $content) ?: [];
        $normalizedSentences = [];

        foreach ($sentences as $sentence) {
            $sentence = trim($sentence);
            if (str_word_count($sentence) < 6) {
                continue;
            }

            $normalizedSentences[] = $sentence;
        }

        if (count($normalizedSentences) < 6) {
            return false;
        }

        return count(array_unique($normalizedSentences)) / count($normalizedSentences) < 0.75;
    }

    protected function countSentences(string $content): int
    {
        if (trim($content) === '') {
            return 0;
        }

        $sentences = preg_split('/(?<=[.!?])\s+/', $content) ?: [];
        $sentences = array_filter($sentences, fn ($sentence) => str_word_count((string) $sentence) >= 4);

        return count($sentences);
    }
}
