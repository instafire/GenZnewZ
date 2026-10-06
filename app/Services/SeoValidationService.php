<?php

namespace App\Services;


class SeoValidationService
{
    protected array $errors = [];
    protected array $warnings = [];
    protected int $score = 0;
    protected array $passed = [];

    /**
     * Validate content for SEO compliance
     *
     * @param array $data
     * @return array
     */
    public function validate(array $data): array
    {
        $this->errors = [];
        $this->warnings = [];
        $this->score = 0;
        $this->passed = [];

        $title = $data['title'] ?? '';
        $description = $data['description'] ?? '';
        $content = $data['content'] ?? '';
        $focusKeyword = $data['focus_keyword'] ?? null;

        // Title Validation (20 points)
        $this->validateTitle($title, $focusKeyword);

        // Meta Description Validation (15 points)
        $this->validateMetaDescription($description, $focusKeyword);

        // Content Quality (25 points)
        $this->validateContentQuality($content);

        // Heading Structure (15 points)
        $this->validateHeadingStructure($content);

        // Keyword Optimization (15 points)
        if ($focusKeyword) {
            $this->validateKeywordOptimization($title, $description, $content, $focusKeyword);
        } else {
            $this->warnings[] = 'No focus keyword provided for optimization';
        }

        // Link Validation (10 points)
        $this->validateLinks($content);

        // Pass if score >= 70 and no critical errors
        $passed = $this->score >= 70 && empty(array_filter($this->errors, function($error) {
            return isset($error['critical']) && $error['critical'];
        }));

        return [
            'passed' => $passed,
            'score' => $this->score,
            'grade' => $this->calculateGrade($this->score),
            'errors' => $this->errors,
            'warnings' => $this->warnings,
            'passed_checks' => $this->passed,
            'total_checks' => count($this->passed) + count($this->errors)
        ];
    }

    /**
     * Validate title for SEO best practices
     */
    protected function validateTitle(string $title, ?string $focusKeyword = null): void
    {
        $length = mb_strlen($title);

        // Title length (50-60 optimal)
        if ($length < 30) {
            $this->errors[] = [
                'field' => 'title',
                'message' => "Title too short ({$length} chars). Optimal: 50-60 characters.",
                'critical' => true
            ];
        } elseif ($length > 70) {
            $this->errors[] = [
                'field' => 'title',
                'message' => "Title too long ({$length} chars). It will be truncated in search results. Optimal: 50-60 characters.",
                'critical' => false
            ];
            $this->score += 5;
        } elseif ($length >= 50 && $length <= 60) {
            $this->passed[] = "Title length perfect ({$length} chars)";
            $this->score += 10;
        } else {
            $this->passed[] = "Title length acceptable ({$length} chars)";
            $this->score += 7;
        }

        // Check for ALL CAPS
        if ($title === strtoupper($title) && preg_match('/[A-Z]/', $title)) {
            $this->errors[] = [
                'field' => 'title',
                'message' => 'Title should not be in ALL CAPS. Use title case instead.',
                'critical' => false
            ];
        } else {
            $this->score += 2;
        }

        // Check for clickbait patterns
        $clickbaitPatterns = [
            '/you won\'?t believe/i',
            '/shocking/i',
            '/doctors hate/i',
            '/one weird trick/i',
            '/!!+/',
            '/\?\?+/',
        ];

        foreach ($clickbaitPatterns as $pattern) {
            if (preg_match($pattern, $title)) {
                $this->warnings[] = 'Title contains potential clickbait phrases. Consider more professional wording.';
                break;
            }
        }

        // Focus keyword in title
        if ($focusKeyword) {
            $titleLower = strtolower($title);
            $keywordLower = strtolower($focusKeyword);

            if (strpos($titleLower, $keywordLower) !== false) {
                // Check if keyword is in first 60% of title
                $keywordPosition = strpos($titleLower, $keywordLower);
                $titleLength = mb_strlen($title);

                if ($keywordPosition <= ($titleLength * 0.6)) {
                    $this->passed[] = 'Focus keyword found in title (good position)';
                    $this->score += 8;
                } else {
                    $this->passed[] = 'Focus keyword found in title';
                    $this->score += 5;
                    $this->warnings[] = 'Focus keyword is near the end of title. Consider moving it earlier.';
                }
            } else {
                $this->errors[] = [
                    'field' => 'title',
                    'message' => 'Focus keyword not found in title. This is crucial for SEO.',
                    'critical' => true
                ];
            }
        }
    }

    /**
     * Validate meta description
     */
    protected function validateMetaDescription(string $description, ?string $focusKeyword = null): void
    {
        $length = mb_strlen($description);

        // Description length (150-160 optimal)
        if ($length < 120) {
            $this->errors[] = [
                'field' => 'description',
                'message' => "Meta description too short ({$length} chars). Optimal: 150-160 characters.",
                'critical' => true
            ];
        } elseif ($length > 165) {
            $this->errors[] = [
                'field' => 'description',
                'message' => "Meta description too long ({$length} chars). It will be truncated. Optimal: 150-160 characters.",
                'critical' => false
            ];
            $this->score += 5;
        } elseif ($length >= 150 && $length <= 160) {
            $this->passed[] = "Meta description length perfect ({$length} chars)";
            $this->score += 8;
        } else {
            $this->passed[] = "Meta description length acceptable ({$length} chars)";
            $this->score += 6;
        }

        // Focus keyword in description
        if ($focusKeyword) {
            $descLower = strtolower($description);
            $keywordLower = strtolower($focusKeyword);

            if (strpos($descLower, $keywordLower) !== false) {
                $this->passed[] = 'Focus keyword found in meta description';
                $this->score += 7;
            } else {
                $this->warnings[] = 'Focus keyword not found in meta description. Consider adding it naturally.';
                $this->score += 2;
            }
        }
    }

    /**
     * Validate content quality
     */
    protected function validateContentQuality(string $content): void
    {
        // Strip HTML tags for word count
        $plainText = strip_tags($content);
        $plainText = preg_replace('/\s+/', ' ', $plainText);
        $wordCount = str_word_count($plainText);

        // Word count validation
        if ($wordCount < 300) {
            $this->errors[] = [
                'field' => 'content',
                'message' => "Content too short ({$wordCount} words). Minimum: 500 words for good SEO.",
                'critical' => true
            ];
        } elseif ($wordCount < 500) {
            $this->warnings[] = "Content is short ({$wordCount} words). Aim for 500+ words for better rankings.";
            $this->score += 10;
        } elseif ($wordCount > 3000) {
            $this->warnings[] = "Content is very long ({$wordCount} words). Consider breaking into multiple articles.";
            $this->score += 15;
        } elseif ($wordCount >= 800 && $wordCount <= 2000) {
            $this->passed[] = "Content length excellent ({$wordCount} words)";
            $this->score += 20;
        } else {
            $this->passed[] = "Content length good ({$wordCount} words)";
            $this->score += 15;
        }

        // Paragraph length check
        $paragraphs = array_filter(explode('</p>', $content));
        $longParagraphs = 0;

        foreach ($paragraphs as $para) {
            $paraText = strip_tags($para);
            $paraWords = str_word_count($paraText);
            if ($paraWords > 150) {
                $longParagraphs++;
            }
        }

        if ($longParagraphs > 0) {
            $this->warnings[] = "{$longParagraphs} paragraph(s) are too long (>150 words). Break them up for better readability.";
        } else {
            $this->score += 5;
        }
    }

    /**
     * Validate heading structure
     */
    protected function validateHeadingStructure(string $content): void
    {
        // Count headings
        preg_match_all('/<h1[^>]*>(.*?)<\/h1>/i', $content, $h1Matches);
        preg_match_all('/<h2[^>]*>(.*?)<\/h2>/i', $content, $h2Matches);
        preg_match_all('/<h3[^>]*>(.*?)<\/h3>/i', $content, $h3Matches);
        preg_match_all('/<h4[^>]*>(.*?)<\/h4>/i', $content, $h4Matches);

        $h1Count = count($h1Matches[0]);
        $h2Count = count($h2Matches[0]);
        $h3Count = count($h3Matches[0]);
        $h4Count = count($h4Matches[0]);

        // H1 validation (should have 0 in content - title is H1)
        if ($h1Count > 0) {
            $this->errors[] = [
                'field' => 'content',
                'message' => "Content contains {$h1Count} H1 tag(s). Remove them - the article title is the only H1.",
                'critical' => true
            ];
        } else {
            $this->passed[] = 'No H1 tags in content (correct - title is H1)';
            $this->score += 5;
        }

        // H2 validation
        $wordCount = str_word_count(strip_tags($content));
        $optimalH2Count = max(2, floor($wordCount / 350));

        if ($h2Count === 0) {
            $this->errors[] = [
                'field' => 'content',
                'message' => 'No H2 headings found. Add subheadings to structure your content.',
                'critical' => true
            ];
        } elseif ($h2Count < 2) {
            $this->warnings[] = "Only {$h2Count} H2 heading. Add more subheadings for better structure.";
            $this->score += 3;
        } elseif ($h2Count >= $optimalH2Count) {
            $this->passed[] = "Good heading structure ({$h2Count} H2 headings)";
            $this->score += 10;
        } else {
            $this->passed[] = "Acceptable heading structure ({$h2Count} H2 headings)";
            $this->score += 7;
        }

        // Check for heading hierarchy issues (H4 without H3, etc.)
        if ($h4Count > 0 && $h3Count === 0 && $h2Count === 0) {
            $this->warnings[] = 'Found H4 tags without H2/H3. Maintain proper heading hierarchy.';
        }
    }

    /**
     * Validate keyword optimization
     */
    protected function validateKeywordOptimization(string $title, string $description, string $content, string $focusKeyword): void
    {
        $plainText = strip_tags($content);
        $wordCount = str_word_count($plainText);
        $keywordLower = strtolower($focusKeyword);
        $contentLower = strtolower($plainText);

        // Count keyword occurrences
        $keywordCount = substr_count($contentLower, $keywordLower);

        if ($keywordCount === 0) {
            $this->errors[] = [
                'field' => 'content',
                'message' => "Focus keyword '{$focusKeyword}' not found in content. Add it naturally.",
                'critical' => true
            ];
        } else {
            // Calculate keyword density
            $density = ($keywordCount / $wordCount) * 100;

            if ($density < 0.5) {
                $this->warnings[] = "Focus keyword density too low ({$density}%). Aim for 1-2%.";
                $this->score += 3;
            } elseif ($density > 3) {
                $this->warnings[] = "Focus keyword density too high ({$density}%). This may be seen as keyword stuffing. Aim for 1-2%.";
                $this->score += 5;
            } else {
                $this->passed[] = "Focus keyword density optimal (" . number_format($density, 1) . "%)";
                $this->score += 10;
            }

            // Check keyword in first 100 words
            $first100Words = implode(' ', array_slice(str_word_count($contentLower, 2), 0, 100));
            if (strpos($first100Words, $keywordLower) !== false) {
                $this->passed[] = 'Focus keyword appears in first 100 words';
                $this->score += 5;
            } else {
                $this->warnings[] = 'Focus keyword not found in first 100 words. Add it early for better SEO.';
            }
        }
    }

    /**
     * Validate internal and external links
     */
    protected function validateLinks(string $content): void
    {
        // Count internal links (relative URLs or genznewz.com)
        preg_match_all('/<a[^>]+href=["\'](\/[^"\']*|https?:\/\/(www\.)?genznewz\.com[^"\']*)["\'][^>]*>/i', $content, $internalLinks);
        $internalCount = count($internalLinks[0]);

        // Count external links
        preg_match_all('/<a[^>]+href=["\']https?:\/\/(?!genznewz\.com)[^"\']+["\'][^>]*>/i', $content, $externalLinks);
        $externalCount = count($externalLinks[0]);

        // Internal links validation
        if ($internalCount === 0) {
            $this->errors[] = [
                'field' => 'content',
                'message' => 'No internal links found. Add 2-3 links to related articles.',
                'critical' => false
            ];
        } elseif ($internalCount === 1) {
            $this->warnings[] = 'Only 1 internal link. Add 1-2 more links to related content.';
            $this->score += 3;
        } elseif ($internalCount >= 2 && $internalCount <= 5) {
            $this->passed[] = "Good internal linking ({$internalCount} links)";
            $this->score += 5;
        } else {
            $this->warnings[] = "Many internal links ({$internalCount}). Ensure they add value.";
            $this->score += 3;
        }

        // External links validation
        if ($externalCount === 0) {
            $this->warnings[] = 'No external links. Consider adding 1-2 authoritative sources.';
            $this->score += 2;
        } elseif ($externalCount >= 1 && $externalCount <= 3) {
            $this->passed[] = "Good external linking ({$externalCount} links)";
            $this->score += 5;
        } else {
            $this->warnings[] = "Many external links ({$externalCount}). Too many may dilute page authority.";
            $this->score += 3;
        }

        // Check for descriptive anchor text (no "click here")
        preg_match_all('/<a[^>]+>([^<]+)<\/a>/i', $content, $allLinks);
        $badAnchors = ['click here', 'read more', 'here', 'this', 'link'];

        foreach ($allLinks[1] as $anchorText) {
            $anchorLower = strtolower(trim($anchorText));
            if (in_array($anchorLower, $badAnchors)) {
                $this->warnings[] = "Avoid generic anchor text like '{$anchorText}'. Use descriptive text instead.";
                break;
            }
        }
    }

    /**
     * Calculate letter grade from score
     */
    protected function calculateGrade(int $score): string
    {
        if ($score >= 95) return 'A+';
        if ($score >= 90) return 'A';
        if ($score >= 85) return 'A-';
        if ($score >= 80) return 'B+';
        if ($score >= 75) return 'B';
        if ($score >= 70) return 'B-';
        if ($score >= 65) return 'C+';
        if ($score >= 60) return 'C';
        if ($score >= 55) return 'C-';
        if ($score >= 50) return 'D';
        return 'F';
    }

    /**
     * Get readability score (Flesch Reading Ease)
     *
     * @param string $content
     * @return float
     */
    public function calculateReadabilityScore(string $content): float
    {
        $plainText = strip_tags($content);
        $sentences = preg_split('/[.!?]+/', $plainText, -1, PREG_SPLIT_NO_EMPTY);
        $sentenceCount = count($sentences);

        if ($sentenceCount === 0) return 0;

        $wordCount = str_word_count($plainText);
        $syllableCount = $this->countSyllables($plainText);

        if ($wordCount === 0) return 0;

        // Flesch Reading Ease formula
        $score = 206.835 - (1.015 * ($wordCount / $sentenceCount)) - (84.6 * ($syllableCount / $wordCount));

        return max(0, min(100, $score));
    }

    /**
     * Count syllables in text (simplified)
     */
    protected function countSyllables(string $text): int
    {
        $words = str_word_count(strtolower($text), 1);
        $syllables = 0;

        foreach ($words as $word) {
            $syllables += max(1, preg_match_all('/[aeiouy]+/', $word));
        }

        return $syllables;
    }
}
