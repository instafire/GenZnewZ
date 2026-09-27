<?php

namespace App\Console\Commands;

use App\Services\DuplicateContentService;
use Botble\Base\Facades\MetaBox;
use Botble\Blog\Models\Post;
use Botble\Slug\Models\Slug;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class CleanAutomationDuplicates extends Command
{
    protected $signature = 'automation:clean-duplicates {--apply : Persist changes instead of reporting only} {--days=21 : Only scan published AI-reporter posts from the last N days} {--report= : Custom report output path}';

    protected $description = 'Merge same-topic duplicate AI posts and keep the best canonical article in each cluster';

    public function handle(DuplicateContentService $duplicateService): int
    {
        $apply = (bool) $this->option('apply');
        $days = max(1, (int) $this->option('days'));
        $reportPath = $this->option('report') ?: storage_path('app/reports/automation-duplicate-cleanup-' . now()->format('Ymd-His') . '.json');

        $posts = Post::with(['categories', 'slugable'])
            ->where('status', 'published')
            ->where('created_at', '>=', now()->subDays($days))
            ->orderBy('created_at')
            ->get();

        $metaRows = DB::table('meta_boxes')
            ->where('reference_type', Post::class)
            ->whereIn('meta_key', ['ai_reporter_id', 'focus_keyword', 'ai_reporter_username'])
            ->whereIn('reference_id', $posts->pluck('id')->all())
            ->get(['reference_id', 'meta_key', 'meta_value'])
            ->groupBy('reference_id');

        $snapshots = $posts
            ->map(function (Post $post) use ($metaRows, $duplicateService) {
                $meta = collect($metaRows->get($post->id, []))->keyBy('meta_key');
                $reporterId = $this->extractMetaTextValue($meta->get('ai_reporter_id')->meta_value ?? null);
                $focusKeyword = $this->extractMetaTextValue($meta->get('focus_keyword')->meta_value ?? null);

                if ($reporterId === '') {
                    return null;
                }

                $signatures = $duplicateService->makeSubmissionTopicSignatures($post->name, $focusKeyword);

                return [
                    'id' => $post->id,
                    'post' => $post,
                    'reporter_id' => $reporterId,
                    'reporter_username' => $this->extractMetaTextValue($meta->get('ai_reporter_username')->meta_value ?? null),
                    'focus_keyword' => $focusKeyword,
                    'focus_signature' => $this->normalizeTopicSignature($focusKeyword),
                    'title_signature' => $this->extractTitleTopicSignature($post->name),
                    'signatures' => $signatures,
                    'normalized_title' => $this->normalizeText($post->name),
                    'lead_excerpt' => $this->extractLeadExcerpt((string) $post->content),
                    'word_count' => str_word_count(strip_tags((string) $post->content)),
                    'category_names' => $post->categories->pluck('name')->values()->all(),
                ];
            })
            ->filter()
            ->values();

        $clusters = $this->buildDuplicateClusters($snapshots);

        $report = [
            'generated_at' => now()->toIso8601String(),
            'mode' => $apply ? 'apply' : 'dry-run',
            'lookback_days' => $days,
            'published_ai_posts_scanned' => $snapshots->count(),
            'clusters_found' => count($clusters),
            'clusters' => [],
        ];

        foreach ($clusters as $cluster) {
            $clusterSnapshots = collect($cluster)->values();
            $canonical = $this->selectCanonicalSnapshot($clusterSnapshots);
            $duplicates = $clusterSnapshots->reject(fn (array $snapshot) => $snapshot['id'] === $canonical['id'])->values();

            $reportEntry = [
                'reporter_id' => $canonical['reporter_id'],
                'reporter_username' => $canonical['reporter_username'],
                'shared_signatures' => $this->sharedClusterSignatures($clusterSnapshots),
                'canonical' => $this->formatSnapshotSummary($canonical),
                'duplicates' => $duplicates->map(fn (array $snapshot) => $this->formatSnapshotSummary($snapshot))->all(),
            ];

            if ($apply) {
                $canonicalPost = $canonical['post']->fresh(['categories', 'slugable']);

                foreach ($duplicates as $duplicate) {
                    $duplicatePost = $duplicate['post']->fresh(['categories', 'slugable']);
                    if (! $duplicatePost || ! $canonicalPost) {
                        continue;
                    }

                    $this->mergePostIntoCanonical($canonicalPost, $duplicatePost);
                    $canonicalPost = $canonicalPost->fresh(['categories', 'slugable']);
                }

                $reportEntry['canonical_after_merge'] = [
                    'id' => $canonicalPost?->id,
                    'title' => $canonicalPost?->name,
                    'slug' => $canonicalPost?->slugable?->key,
                    'categories' => $canonicalPost?->categories?->pluck('name')->values()->all(),
                    'views' => (int) ($canonicalPost?->views ?? 0),
                    'word_count' => $canonicalPost ? str_word_count(strip_tags((string) $canonicalPost->content)) : 0,
                ];
            }

            $report['clusters'][] = $reportEntry;
        }

        File::ensureDirectoryExists(dirname($reportPath));
        File::put($reportPath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $this->info('Report written to ' . $reportPath);
        $this->table(['Metric', 'Value'], [
            ['Published AI posts scanned', $snapshots->count()],
            ['Duplicate clusters found', count($clusters)],
            ['Mode', $apply ? 'apply' : 'dry-run'],
        ]);

        return self::SUCCESS;
    }

    protected function buildDuplicateClusters(Collection $snapshots): array
    {
        $parents = [];
        foreach ($snapshots as $snapshot) {
            $parents[$snapshot['id']] = $snapshot['id'];
        }

        $byReporter = $snapshots->groupBy('reporter_id');
        foreach ($byReporter as $reporterSnapshots) {
            $items = $reporterSnapshots->values();
            $count = $items->count();

            for ($i = 0; $i < $count; $i++) {
                for ($j = $i + 1; $j < $count; $j++) {
                    $left = $items[$i];
                    $right = $items[$j];

                    if (! $this->sharesTopicSignature($left, $right)) {
                        continue;
                    }

                    if (! $this->areLikelyDuplicates($left, $right)) {
                        continue;
                    }

                    $this->unionParents($parents, $left['id'], $right['id']);
                }
            }
        }

        $clusters = [];
        foreach ($snapshots as $snapshot) {
            $root = $this->findParent($parents, $snapshot['id']);
            $clusters[$root][] = $snapshot;
        }

        return array_values(array_filter($clusters, fn (array $cluster) => count($cluster) > 1));
    }

    protected function sharesTopicSignature(array $left, array $right): bool
    {
        return array_intersect($left['signatures'], $right['signatures']) !== [];
    }

    protected function areLikelyDuplicates(array $left, array $right): bool
    {
        $titleSimilarity = $this->calculateSimilarity($left['normalized_title'], $right['normalized_title']);
        $leadSimilarity = ($left['lead_excerpt'] !== '' && $right['lead_excerpt'] !== '')
            ? $this->calculateSimilarity($left['lead_excerpt'], $right['lead_excerpt'])
            : 0;

        $focusMatch = $left['focus_signature'] !== ''
            && $left['focus_signature'] === $right['focus_signature'];
        $topicMatch = $left['title_signature'] !== ''
            && $left['title_signature'] === $right['title_signature'];

        return $topicMatch
            || ($focusMatch && ($titleSimilarity >= 35 || $leadSimilarity >= 30))
            || ($titleSimilarity >= 82 && $leadSimilarity >= 28);
    }

    protected function selectCanonicalSnapshot(Collection $cluster): array
    {
        return $cluster->sort(function (array $left, array $right) {
            $scoreComparison = $this->snapshotQualityScore($right) <=> $this->snapshotQualityScore($left);
            if ($scoreComparison !== 0) {
                return $scoreComparison;
            }

            return optional($left['post']->created_at)->timestamp <=> optional($right['post']->created_at)->timestamp;
        })->first();
    }

    protected function snapshotQualityScore(array $snapshot): int
    {
        /** @var \Botble\Blog\Models\Post $post */
        $post = $snapshot['post'];
        $slug = (string) ($post->slugable?->key ?? '');

        $score = min($snapshot['word_count'], 1800);
        $score += min(mb_strlen(trim((string) $post->description)), 180);
        $score += ! empty($post->image) ? 400 : 0;
        $score += $post->is_featured ? 150 : 0;
        $score += (int) min((int) $post->views, 5000) / 10;
        $score += preg_match('/-\d+$/', $slug) ? -250 : 125;
        $score -= $this->suspiciousCategoryPenalty($snapshot['category_names']);

        return $score;
    }

    protected function suspiciousCategoryPenalty(array $categoryNames): int
    {
        $suspiciousCategories = ['fashion', 'horoscopes', 'sexual wellness'];
        $penalty = 0;

        foreach ($categoryNames as $categoryName) {
            if (in_array(Str::lower(trim((string) $categoryName)), $suspiciousCategories, true)) {
                $penalty += 250;
            }
        }

        return $penalty;
    }

    protected function mergePostIntoCanonical(Post $canonical, Post $duplicate): void
    {
        DB::transaction(function () use ($canonical, $duplicate) {
            $canonicalWordCount = str_word_count(strip_tags((string) $canonical->content));
            $duplicateWordCount = str_word_count(strip_tags((string) $duplicate->content));
            $preferredSlugKey = $canonical->slugable?->key;

            if ((trim((string) $canonical->description) === '' || mb_strlen((string) $canonical->description) < 120)
                && mb_strlen(trim((string) $duplicate->description)) >= 120) {
                $canonical->description = $duplicate->description;
            }

            if (($canonicalWordCount + 100) < $duplicateWordCount) {
                $canonical->content = $duplicate->content;
                MetaBox::saveMetaBoxData(
                    $canonical,
                    'content_fingerprint',
                    sha1(preg_replace('/\s+/', ' ', Str::lower(trim(strip_tags((string) $duplicate->content)))))
                );
            }

            if (empty($canonical->image) && ! empty($duplicate->image)) {
                $canonical->image = $duplicate->image;

                foreach (['pexels_photographer', 'pexels_photographer_url', 'pexels_photo_url', 'pexels_photo_id'] as $key) {
                    $value = MetaBox::getMetaData($duplicate, $key, true);
                    if (! empty($value)) {
                        MetaBox::saveMetaBoxData($canonical, $key, $value);
                    }
                }
            }

            $canonical->is_featured = (bool) $canonical->is_featured || (bool) $duplicate->is_featured;
            $canonical->views = (int) $canonical->views + (int) $duplicate->views;
            $canonical->saveQuietly();

            if (($canonical->categories->isEmpty() || $this->hasSuspiciousCategories($canonical))
                && ! $this->hasSuspiciousCategories($duplicate)
                && $duplicate->categories->isNotEmpty()) {
                $canonical->categories()->sync($duplicate->categories->pluck('id')->values()->all());
            }

            foreach ([
                'ai_reporter_id',
                'ai_reporter_username',
                'focus_keyword',
                'image_search_query',
                'image_description',
                'image_search_query_requested',
                'image_description_requested',
            ] as $key) {
                $canonicalValue = MetaBox::getMetaData($canonical, $key, true);
                $duplicateValue = MetaBox::getMetaData($duplicate, $key, true);
                if (empty($canonicalValue) && ! empty($duplicateValue)) {
                    MetaBox::saveMetaBoxData($canonical, $key, $duplicateValue);
                }
            }

            if (DB::getSchemaBuilder()->hasTable('fob_comments')) {
                DB::table('fob_comments')
                    ->where('reference_type', Post::class)
                    ->where('reference_id', $duplicate->id)
                    ->whereNull('reference_url')
                    ->update(['reference_url' => $canonical->url]);

                DB::table('fob_comments')
                    ->where('reference_type', Post::class)
                    ->where('reference_id', $duplicate->id)
                    ->update(['reference_id' => $canonical->id]);
            }

            if (empty($preferredSlugKey) && ! empty($duplicate->slugable?->key)) {
                Slug::query()
                    ->where('reference_type', Post::class)
                    ->where('reference_id', $duplicate->id)
                    ->update([
                        'reference_id' => $canonical->id,
                        'key' => $duplicate->slugable->key,
                    ]);
            } else {
                Slug::query()
                    ->where('reference_type', Post::class)
                    ->where('reference_id', $duplicate->id)
                    ->delete();
            }

            if (! empty($preferredSlugKey)) {
                Slug::query()
                    ->where('reference_type', Post::class)
                    ->where('reference_id', $canonical->id)
                    ->where('key', '!=', $preferredSlugKey)
                    ->delete();
            }

            DB::table('meta_boxes')
                ->where('reference_type', Post::class)
                ->where('reference_id', $duplicate->id)
                ->delete();

            $duplicate->delete();
        });
    }

    protected function hasSuspiciousCategories(Post $post): bool
    {
        return $this->suspiciousCategoryPenalty($post->categories->pluck('name')->values()->all()) > 0;
    }

    protected function sharedClusterSignatures(Collection $cluster): array
    {
        $counts = [];
        foreach ($cluster as $snapshot) {
            foreach ($snapshot['signatures'] as $signature) {
                $counts[$signature] = ($counts[$signature] ?? 0) + 1;
            }
        }

        return array_keys(array_filter($counts, fn (int $count) => $count > 1));
    }

    protected function formatSnapshotSummary(array $snapshot): array
    {
        /** @var \Botble\Blog\Models\Post $post */
        $post = $snapshot['post'];

        return [
            'id' => $snapshot['id'],
            'title' => $post->name,
            'slug' => $post->slugable?->key,
            'focus_keyword' => $snapshot['focus_keyword'],
            'categories' => $snapshot['category_names'],
            'views' => (int) $post->views,
            'word_count' => $snapshot['word_count'],
            'quality_score' => $this->snapshotQualityScore($snapshot),
            'created_at' => optional($post->created_at)->toDateTimeString(),
        ];
    }

    protected function findParent(array &$parents, int $id): int
    {
        if ($parents[$id] !== $id) {
            $parents[$id] = $this->findParent($parents, $parents[$id]);
        }

        return $parents[$id];
    }

    protected function unionParents(array &$parents, int $leftId, int $rightId): void
    {
        $leftRoot = $this->findParent($parents, $leftId);
        $rightRoot = $this->findParent($parents, $rightId);

        if ($leftRoot !== $rightRoot) {
            $parents[$rightRoot] = $leftRoot;
        }
    }

    protected function calculateSimilarity(string $left, string $right): int
    {
        similar_text($left, $right, $percent);

        return (int) round($percent);
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

    protected function normalizeText(string $text): string
    {
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = Str::lower(trim($text));
        $text = preg_replace('/\s+/', ' ', $text);

        return trim((string) $text);
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
}
