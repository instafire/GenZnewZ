<?php

namespace App\Services;

use Botble\Blog\Models\Post;
use Illuminate\Support\Str;

class DuplicateContentService
{
    protected int $titleCandidateLimit = 500;

    protected int $contentCandidateLimit = 800;

    protected int $leadCandidateLimit = 350;

    protected int $reporterTopicCandidateLimit = 250;

    /**
     * Check if title is too similar to existing posts
     *
     * @param string $title
     * @param int|null $excludePostId
     * @return array
     */
    public function checkTitleSimilarity(string $title, ?int $excludePostId = null): array
    {
        $normalizedTitle = $this->normalizeText($title);

        $exactQuery = Post::query()
            ->where('status', 'published')
            ->select(['id', 'name'])
            ->whereRaw('LOWER(TRIM(name)) = ?', [$normalizedTitle]);

        if ($excludePostId) {
            $exactQuery->where('id', '!=', $excludePostId);
        }

        $exactMatches = $exactQuery->get();

        if ($exactMatches->isNotEmpty()) {
            $similarPosts = $exactMatches->map(function (Post $post) {
                return [
                    'id' => $post->id,
                    'title' => (string) $post->name,
                    'similarity' => 100,
                    'url' => $post->url ?? url('post-' . $post->id),
                ];
            })->all();

            return [
                'is_duplicate' => true,
                'has_similar' => true,
                'similar_posts' => array_slice($similarPosts, 0, 5),
                'highest_similarity' => 100,
            ];
        }

        $query = Post::query()
            ->where('status', 'published')
            ->select(['id', 'name'])
            ->orderByDesc('created_at');

        if ($excludePostId) {
            $query->where('id', '!=', $excludePostId);
        }

        $titleWords = $this->getSignificantWords(strtolower($title));
        $keywords = $this->getSearchKeywords($titleWords);

        if ($keywords !== []) {
            $query->where(function ($builder) use ($keywords) {
                foreach ($keywords as $keyword) {
                    $builder->orWhere('name', 'like', '%' . $keyword . '%');
                }
            });
        }

        $existingPosts = $query
            ->limit($this->titleCandidateLimit)
            ->get();

        $similarPosts = [];
        $titleLower = strtolower($title);

        foreach ($existingPosts as $post) {
            $existingTitle = (string) $post->name;
            $similarity = $this->calculateSimilarity($titleLower, strtolower($existingTitle));

            if ($similarity >= 80) {
                $similarPosts[] = [
                    'id' => $post->id,
                    'title' => $existingTitle,
                    'similarity' => $similarity,
                    'url' => $post->url ?? url('post-' . $post->id),
                ];
            }
        }

        // Sort by similarity descending
        usort($similarPosts, function ($a, $b) {
            return $b['similarity'] <=> $a['similarity'];
        });

        return [
            'is_duplicate' => !empty($similarPosts) && $similarPosts[0]['similarity'] >= 88,
            'has_similar' => !empty($similarPosts),
            'similar_posts' => array_slice($similarPosts, 0, 5),
            'highest_similarity' => !empty($similarPosts) ? $similarPosts[0]['similarity'] : 0,
        ];
    }

    /**
     * Check content similarity
     *
     * @param string $content
     * @param int|null $excludePostId
     * @return array
     */
    public function checkContentSimilarity(string $content, ?int $excludePostId = null): array
    {
        // Strip HTML and normalize
        $plainContent = strip_tags($content);
        $plainContent = preg_replace('/\s+/', ' ', $plainContent);
        $contentHash = $this->generateContentHash($plainContent);

        $query = Post::query();
        $query->where('status', 'published');

        if ($excludePostId) {
            $query->where('id', '!=', $excludePostId);
        }

        // Sample recent posts for performance (last 500 posts)
        $recentPosts = $query->orderBy('created_at', 'desc')
            ->limit($this->contentCandidateLimit)
            ->get(['id', 'name', 'content']);

        $similarContent = [];

        foreach ($recentPosts as $post) {
            $existingContent = strip_tags($post->content);
            $existingContent = preg_replace('/\s+/', ' ', $existingContent);

            // Quick hash comparison first
            $existingHash = $this->generateContentHash($existingContent);

            if ($contentHash === $existingHash) {
                $similarContent[] = [
                    'id' => $post->id,
                    'title' => $post->name,
                    'similarity' => 100,
                    'type' => 'exact_duplicate',
                ];
                break;
            }

            // Deep comparison for partial similarity
            $similarity = $this->calculateContentSimilarity($plainContent, $existingContent);

            if ($similarity >= 50) {
                $similarContent[] = [
                    'id' => $post->id,
                    'title' => $post->name,
                    'similarity' => $similarity,
                    'type' => $similarity >= 80 ? 'very_similar' : 'similar',
                ];
            }
        }

        // Sort by similarity descending
        usort($similarContent, function ($a, $b) {
            return $b['similarity'] <=> $a['similarity'];
        });

        return [
            'is_duplicate' => !empty($similarContent) && $similarContent[0]['similarity'] >= 85,
            'has_similar' => !empty($similarContent),
            'similar_posts' => array_slice($similarContent, 0, 3),
            'highest_similarity' => !empty($similarContent) ? $similarContent[0]['similarity'] : 0,
        ];
    }

    public function checkDescriptionSimilarity(?string $description, ?int $excludePostId = null): array
    {
        $normalizedDescription = $this->normalizeText((string) $description);

        if ($normalizedDescription === '' || mb_strlen($normalizedDescription) < 40) {
            return [
                'is_duplicate' => false,
                'has_similar' => false,
                'similar_posts' => [],
                'highest_similarity' => 0,
            ];
        }

        $query = Post::query()
            ->where('status', 'published')
            ->select(['id', 'name', 'description'])
            ->whereRaw('LOWER(TRIM(description)) = ?', [$normalizedDescription]);

        if ($excludePostId) {
            $query->where('id', '!=', $excludePostId);
        }

        $matches = $query->get();

        if ($matches->isEmpty()) {
            return [
                'is_duplicate' => false,
                'has_similar' => false,
                'similar_posts' => [],
                'highest_similarity' => 0,
            ];
        }

        $similarPosts = $matches->map(function (Post $post) {
            return [
                'id' => $post->id,
                'title' => (string) $post->name,
                'similarity' => 100,
                'type' => 'exact_description_duplicate',
                'url' => $post->url ?? url('post-' . $post->id),
            ];
        })->all();

        return [
            'is_duplicate' => true,
            'has_similar' => true,
            'similar_posts' => array_slice($similarPosts, 0, 5),
            'highest_similarity' => 100,
        ];
    }

    public function checkLeadSimilarity(string $content, ?int $excludePostId = null): array
    {
        $leadExcerpt = $this->extractLeadExcerpt($content);

        if ($leadExcerpt === '' || mb_strlen($leadExcerpt) < 120) {
            return [
                'is_duplicate' => false,
                'has_similar' => false,
                'similar_posts' => [],
                'highest_similarity' => 0,
                'lead_excerpt' => $leadExcerpt,
            ];
        }

        $query = Post::query()
            ->where('status', 'published')
            ->select(['id', 'name', 'content'])
            ->orderByDesc('created_at');

        if ($excludePostId) {
            $query->where('id', '!=', $excludePostId);
        }

        $candidates = $query
            ->limit($this->leadCandidateLimit)
            ->get();

        $similarPosts = [];

        foreach ($candidates as $post) {
            $existingLead = $this->extractLeadExcerpt((string) $post->content);

            if ($existingLead === '' || mb_strlen($existingLead) < 120) {
                continue;
            }

            $similarity = $this->calculateSimilarity($leadExcerpt, $existingLead);

            if ($similarity >= 75) {
                $similarPosts[] = [
                    'id' => $post->id,
                    'title' => (string) $post->name,
                    'similarity' => $similarity,
                    'type' => $similarity >= 90 ? 'duplicate_lead' : 'similar_lead',
                    'url' => $post->url ?? url('post-' . $post->id),
                ];
            }
        }

        usort($similarPosts, fn ($a, $b) => $b['similarity'] <=> $a['similarity']);

        return [
            'is_duplicate' => !empty($similarPosts) && $similarPosts[0]['similarity'] >= 90,
            'has_similar' => !empty($similarPosts),
            'similar_posts' => array_slice($similarPosts, 0, 3),
            'highest_similarity' => !empty($similarPosts) ? $similarPosts[0]['similarity'] : 0,
            'lead_excerpt' => Str::limit($leadExcerpt, 220, ''),
        ];
    }

    public function checkReporterTopicCollision(
        int $reporterId,
        string $title,
        ?string $focusKeyword = null,
        ?string $content = null,
        ?int $excludePostId = null,
        int $lookbackDays = 14
    ): array {
        $candidateFocusKeyword = $this->normalizeTopicSignature($focusKeyword);
        $candidateTitleTopic = $this->extractTitleTopicSignature($title);
        $candidateLeadExcerpt = $content ? $this->extractLeadExcerpt($content) : '';

        if ($candidateFocusKeyword === '' && $candidateTitleTopic === '') {
            return array_merge($this->emptySimilarityResult(), [
                'should_block' => false,
            ]);
        }

        $query = Post::query()
            ->where('posts.status', 'published')
            ->where('posts.created_at', '>=', now()->subDays($lookbackDays))
            ->select([
                'posts.id',
                'posts.name',
                'posts.content',
                'focus_meta.meta_value as focus_keyword_meta',
                'reporter_meta.meta_value as reporter_id_meta',
            ])
            ->leftJoin('meta_boxes as focus_meta', function ($join) {
                $join->on('focus_meta.reference_id', '=', 'posts.id')
                    ->where('focus_meta.reference_type', '=', Post::class)
                    ->where('focus_meta.meta_key', '=', 'focus_keyword');
            })
            ->leftJoin('meta_boxes as reporter_meta', function ($join) {
                $join->on('reporter_meta.reference_id', '=', 'posts.id')
                    ->where('reporter_meta.reference_type', '=', Post::class)
                    ->where('reporter_meta.meta_key', '=', 'ai_reporter_id');
            })
            ->orderByDesc('posts.created_at')
            ->limit($this->reporterTopicCandidateLimit);

        if ($excludePostId) {
            $query->where('posts.id', '!=', $excludePostId);
        }

        $candidates = $query->get();
        $similarPosts = [];
        $normalizedTitle = $this->normalizeText($title);

        foreach ($candidates as $post) {
            $existingReporterId = $this->extractMetaTextValue($post->reporter_id_meta);
            if ((string) $existingReporterId !== (string) $reporterId) {
                continue;
            }

            $existingFocusKeyword = $this->normalizeTopicSignature($post->focus_keyword_meta);
            $existingTitleTopic = $this->extractTitleTopicSignature((string) $post->name);

            $focusMatch = $candidateFocusKeyword !== '' && $existingFocusKeyword !== '' && $candidateFocusKeyword === $existingFocusKeyword;
            $topicMatch = $candidateTitleTopic !== '' && $existingTitleTopic !== '' && $candidateTitleTopic === $existingTitleTopic;

            if (! $focusMatch && ! $topicMatch) {
                continue;
            }

            $titleSimilarity = $this->calculateSimilarity($normalizedTitle, $this->normalizeText((string) $post->name));
            $existingLeadExcerpt = $this->extractLeadExcerpt((string) $post->content);
            $leadSimilarity = ($candidateLeadExcerpt !== '' && $existingLeadExcerpt !== '')
                ? $this->calculateSimilarity($candidateLeadExcerpt, $existingLeadExcerpt)
                : 0;

            $shouldBlock = $topicMatch
                || ($focusMatch && ($titleSimilarity >= 35 || $leadSimilarity >= 30))
                || ($titleSimilarity >= 82 && $leadSimilarity >= 28);

            if (! $shouldBlock) {
                continue;
            }

            $similarPosts[] = [
                'id' => $post->id,
                'title' => (string) $post->name,
                'similarity' => max(
                    $titleSimilarity,
                    $leadSimilarity,
                    $focusMatch && $topicMatch ? 100 : 0,
                    $topicMatch ? 88 : 0,
                    $focusMatch ? 72 : 0
                ),
                'type' => $focusMatch ? 'matching_focus_keyword' : 'matching_topic_signature',
                'url' => $post->url ?? url('post-' . $post->id),
            ];
        }

        usort($similarPosts, fn ($a, $b) => $b['similarity'] <=> $a['similarity']);

        return [
            'is_duplicate' => ! empty($similarPosts),
            'has_similar' => ! empty($similarPosts),
            'should_block' => ! empty($similarPosts),
            'similar_posts' => array_slice($similarPosts, 0, 5),
            'highest_similarity' => ! empty($similarPosts) ? $similarPosts[0]['similarity'] : 0,
        ];
    }

    /**
     * Calculate text similarity percentage (0-100)
     *
     * @param string $text1
     * @param string $text2
     * @return int
     */
    protected function calculateSimilarity(string $text1, string $text2): int
    {
        // Use similar_text for basic comparison
        similar_text($text1, $text2, $percent);

        return (int) round($percent);
    }

    /**
     * Calculate content similarity using word overlap
     *
     * @param string $content1
     * @param string $content2
     * @return int
     */
    protected function calculateContentSimilarity(string $content1, string $content2): int
    {
        // Get significant words from both contents
        $words1 = $this->getSignificantWords($content1);
        $words2 = $this->getSignificantWords($content2);

        if (empty($words1) || empty($words2)) {
            return 0;
        }

        // Calculate word overlap using Jaccard similarity
        $intersection = count(array_intersect($words1, $words2));
        $union = count(array_unique(array_merge($words1, $words2)));

        if ($union === 0) {
            return 0;
        }

        $similarity = ($intersection / $union) * 100;

        return (int) round($similarity);
    }

    /**
     * Extract significant words (filter out common stop words)
     *
     * @param string $text
     * @return array
     */
    protected function getSignificantWords(string $text): array
    {
        // Common stop words to ignore
        $stopWords = [
            'the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for',
            'of', 'with', 'by', 'from', 'as', 'is', 'was', 'are', 'were', 'been',
            'be', 'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would',
            'should', 'could', 'may', 'might', 'can', 'this', 'that', 'these',
            'those', 'i', 'you', 'he', 'she', 'it', 'we', 'they', 'what', 'which',
            'who', 'when', 'where', 'why', 'how', 'all', 'each', 'every', 'some',
            'any', 'few', 'more', 'most', 'other', 'into', 'through', 'during',
            'before', 'after', 'above', 'below', 'between', 'under', 'again',
            'further', 'then', 'once',
        ];

        // Extract words (3+ characters)
        preg_match_all('/\b\w{3,}\b/', strtolower($text), $matches);
        $words = $matches[0];

        // Filter out stop words
        $significantWords = array_diff($words, $stopWords);

        return array_values($significantWords);
    }

    protected function getSearchKeywords(array $words, int $limit = 5): array
    {
        if ($words === []) {
            return [];
        }

        usort($words, fn ($a, $b) => strlen($b) <=> strlen($a));

        return array_slice(array_values(array_unique($words)), 0, $limit);
    }

    /**
     * Generate a hash for content comparison
     *
     * @param string $content
     * @return string
     */
    protected function generateContentHash(string $content): string
    {
        // Normalize content
        $normalized = $this->normalizeText($content);

        return md5($normalized);
    }

    protected function normalizeText(string $text): string
    {
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = strtolower(trim($text));
        $text = preg_replace('/\s+/', ' ', $text);

        return (string) $text;
    }

    /**
     * Check for plagiarism indicators
     *
     * @param string $content
     * @return array
     */
    public function checkPlagiarismIndicators(string $content): array
    {
        $warnings = [];

        // Check for suspiciously formatted quotes
        preg_match_all('/"([^"]{100,})"/i', $content, $longQuotes);
        if (count($longQuotes[0]) > 3) {
            $warnings[] = 'Content contains many long quoted sections. Ensure proper attribution.';
        }

        // Check for missing attribution phrases
        $hasAttributions = preg_match('/(according to|source:|via|credit:|cited from)/i', $content);
        if (!$hasAttributions && preg_match('/"[^"]+"/', $content)) {
            $warnings[] = 'Content has quotes but no attribution phrases. Add sources.';
        }

        // Check content diversity (repeated phrases)
        $sentences = preg_split('/[.!?]+/', strip_tags($content));
        $sentences = array_filter($sentences, fn($s) => str_word_count($s) > 5);

        $uniqueSentences = count(array_unique($sentences));
        $totalSentences = count($sentences);

        if ($totalSentences > 0 && ($uniqueSentences / $totalSentences) < 0.8) {
            $warnings[] = 'Content has many repeated phrases. Increase variety.';
        }

        return [
            'has_warnings' => !empty($warnings),
            'warnings' => $warnings,
            'uniqueness_score' => $totalSentences > 0 ? (int) (($uniqueSentences / $totalSentences) * 100) : 100,
        ];
    }

    /**
     * Full duplicate check (title + content)
     *
     * @param string $title
     * @param string $content
     * @param int|null $excludePostId
     * @return array
     */
    public function fullDuplicateCheck(
        string $title,
        string $content,
        ?int $excludePostId = null,
        ?string $description = null,
        ?string $focusKeyword = null,
        ?int $reporterId = null
    ): array
    {
        $titleCheck = $this->checkTitleSimilarity($title, $excludePostId);
        $contentCheck = $this->checkContentSimilarity($content, $excludePostId);
        $descriptionCheck = $this->checkDescriptionSimilarity($description, $excludePostId);
        $leadCheck = $this->checkLeadSimilarity($content, $excludePostId);
        $reporterTopicCheck = $reporterId
            ? $this->checkReporterTopicCollision($reporterId, $title, $focusKeyword, $content, $excludePostId)
            : array_merge($this->emptySimilarityResult(), ['should_block' => false]);
        $plagiarismCheck = $this->checkPlagiarismIndicators($content);

        $isDuplicate = $titleCheck['is_duplicate']
            || $contentCheck['is_duplicate']
            || $descriptionCheck['is_duplicate']
            || $leadCheck['is_duplicate']
            || ($reporterTopicCheck['is_duplicate'] ?? false);

        $hasConcerns = $titleCheck['has_similar']
            || $contentCheck['has_similar']
            || $descriptionCheck['has_similar']
            || $leadCheck['has_similar']
            || ($reporterTopicCheck['has_similar'] ?? false)
            || $plagiarismCheck['has_warnings'];

        $shouldBlock = $isDuplicate
            || ($reporterTopicCheck['should_block'] ?? false)
            || (($contentCheck['highest_similarity'] ?? 0) >= 78)
            || (
                ($titleCheck['highest_similarity'] ?? 0) >= 82
                && ($contentCheck['highest_similarity'] ?? 0) >= 65
            )
            || (
                ($titleCheck['highest_similarity'] ?? 0) >= 86
                && ($leadCheck['highest_similarity'] ?? 0) >= 75
            )
            || (($leadCheck['highest_similarity'] ?? 0) >= 88);

        return [
            'is_duplicate' => $isDuplicate,
            'should_block' => $shouldBlock,
            'has_concerns' => $hasConcerns,
            'title_check' => $titleCheck,
            'content_check' => $contentCheck,
            'description_check' => $descriptionCheck,
            'lead_check' => $leadCheck,
            'reporter_topic_check' => $reporterTopicCheck,
            'plagiarism_check' => $plagiarismCheck,
            'recommendation' => $this->getRecommendation(
                $shouldBlock,
                $hasConcerns,
                $titleCheck,
                $contentCheck,
                $leadCheck,
                $reporterTopicCheck
            ),
        ];
    }

    /**
     * Get recommendation based on checks
     *
     * @param bool $shouldBlock
     * @param bool $hasConcerns
     * @param array $titleCheck
     * @param array $contentCheck
     * @param array $leadCheck
     * @return string
     */
    protected function getRecommendation(
        bool $shouldBlock,
        bool $hasConcerns,
        array $titleCheck,
        array $contentCheck,
        array $leadCheck,
        array $reporterTopicCheck
    ): string
    {
        if ($reporterTopicCheck['has_similar'] ?? false) {
            return 'REJECT: This AI reporter already has a published article on the same topic. Update the existing post instead of creating another version.';
        }

        if ($shouldBlock) {
            return 'REJECT: This content is too similar to existing posts. Create original content.';
        }

        if ($leadCheck['has_similar'] && $leadCheck['highest_similarity'] >= 80) {
            return 'WARNING: The opening paragraph is very close to an existing article. Rewrite the intro with a distinct angle.';
        }

        if ($titleCheck['has_similar'] && $titleCheck['highest_similarity'] >= 70) {
            return 'WARNING: Title is very similar to existing posts. Consider making it more unique.';
        }

        if ($contentCheck['has_similar'] && $contentCheck['highest_similarity'] >= 60) {
            return 'WARNING: Content shows significant overlap with existing posts. Add more original insights.';
        }

        if ($hasConcerns) {
            return 'CAUTION: Some similarity detected. Review suggestions and improve uniqueness.';
        }

        return 'APPROVED: Content appears unique and original.';
    }

    public function makeSubmissionTopicSignatures(string $title, ?string $focusKeyword = null): array
    {
        $focusSignature = $this->normalizeTopicSignature($focusKeyword);
        $titleSignature = $this->extractTitleTopicSignature($title);
        $signatures = [];

        if ($focusSignature !== '') {
            $signatures[] = $focusSignature;
        }

        if ($titleSignature !== '') {
            $signatures[] = $titleSignature;
        }

        return array_values(array_unique($signatures));
    }

    public function makeSubmissionTopicSignature(string $title, ?string $focusKeyword = null): string
    {
        return $this->makeSubmissionTopicSignatures($title, $focusKeyword)[0] ?? '';
    }

    protected function extractLeadExcerpt(string $content, int $maxSentences = 2, int $maxChars = 420): string
    {
        $plain = $this->normalizeText(strip_tags($content));

        if ($plain === '') {
            return '';
        }

        $sentences = preg_split('/(?<=[.!?])\s+/', $plain) ?: [];
        $lead = [];

        foreach ($sentences as $sentence) {
            $sentence = trim($sentence);

            if ($sentence === '' || str_word_count($sentence) < 5) {
                continue;
            }

            $candidate = trim(implode(' ', array_merge($lead, [$sentence])));
            if (mb_strlen($candidate) > $maxChars) {
                break;
            }

            $lead[] = $sentence;

            if (count($lead) >= $maxSentences) {
                break;
            }
        }

        if ($lead === []) {
            return trim(mb_substr($plain, 0, $maxChars));
        }

        return trim(implode(' ', $lead));
    }

    protected function normalizeTopicSignature(?string $value): string
    {
        $value = $this->extractMetaTextValue($value);
        $value = $this->normalizeText($value);
        $value = preg_replace('/\bin\s+20\d{2}\b/', '', $value) ?? $value;
        $value = preg_replace('/\b20\d{2}\b/', '', $value) ?? $value;
        $value = preg_replace('/\s+/', ' ', trim((string) $value)) ?? $value;

        return trim((string) $value);
    }

    protected function extractTitleTopicSignature(string $title): string
    {
        $topic = trim($title);

        foreach ([':', ' - ', ' – ', ' — '] as $delimiter) {
            if (str_contains($topic, $delimiter)) {
                $topic = explode($delimiter, $topic, 2)[0];
                break;
            }
        }

        return $this->normalizeTopicSignature($topic);
    }

    protected function extractMetaTextValue(mixed $value): string
    {
        if (! is_string($value)) {
            return trim((string) $value);
        }

        $trimmed = trim($value);
        if ($trimmed === '') {
            return '';
        }

        $decoded = json_decode($trimmed, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            if (is_array($decoded)) {
                $decoded = collect($decoded)->flatten()->filter()->map(fn ($item) => trim((string) $item))->first();

                return trim((string) $decoded);
            }

            if (is_string($decoded) || is_numeric($decoded)) {
                return trim((string) $decoded);
            }
        }

        return trim($trimmed, "\"'[]");
    }

    protected function emptySimilarityResult(): array
    {
        return [
            'is_duplicate' => false,
            'has_similar' => false,
            'similar_posts' => [],
            'highest_similarity' => 0,
        ];
    }
}
