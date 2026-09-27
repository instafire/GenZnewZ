<?php

namespace App\Console\Commands;

use App\Services\SeoValidationService;
use App\Services\ShortPostExpansionService;
use Botble\Blog\Models\Post;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ExpandShortPosts extends Command
{
    protected $signature = 'seo:expand-short-posts
                            {--apply : Persist the expanded copy}
                            {--threshold=100 : Expand published posts below this word-count threshold}
                            {--min-score=90 : Minimum SEO score required to apply the rewrite}
                            {--ids= : Comma-separated post IDs to process}
                            {--limit= : Maximum number of eligible posts to process}
                            {--report= : Custom report output path}';

    protected $description = 'Expand short published posts into SEO-safe long-form articles and only apply A/A+ drafts';

    public function handle(ShortPostExpansionService $expander, SeoValidationService $seo): int
    {
        $apply = (bool) $this->option('apply');
        $threshold = max(1, (int) $this->option('threshold'));
        $minScore = max(0, (int) $this->option('min-score'));
        $ids = collect(explode(',', (string) $this->option('ids')))
            ->map(fn (string $id) => (int) trim($id))
            ->filter(fn (int $id) => $id > 0)
            ->values();
        $limit = $this->option('limit') !== null ? max(1, (int) $this->option('limit')) : null;
        $reportPath = $this->option('report') ?: storage_path('app/reports/short-post-expansion-' . now()->format('Ymd-His') . '.json');

        $query = Post::with(['categories', 'slugable'])
            ->where('status', 'published')
            ->orderBy('id');

        if ($ids->isNotEmpty()) {
            $query->whereIn('id', $ids->all());
        }

        $posts = $query
            ->get()
            ->filter(fn (Post $post) => str_word_count(strip_tags((string) $post->content)) < $threshold)
            ->when($limit !== null, fn ($collection) => $collection->take($limit))
            ->values();

        $report = [
            'generated_at' => now()->toIso8601String(),
            'mode' => $apply ? 'apply' : 'dry-run',
            'threshold' => $threshold,
            'min_score' => $minScore,
            'ids' => $ids->all(),
            'limit' => $limit,
            'eligible_posts' => $posts->count(),
            'qualified' => [],
            'applied' => [],
            'apply_failures' => [],
            'skipped' => [],
        ];

        foreach ($posts as $post) {
            $beforeWordCount = str_word_count(strip_tags((string) $post->content));
            $expanded = $expander->expand($post);
            $validation = $seo->validate([
                'title' => $expanded['title'],
                'description' => $expanded['description'],
                'content' => $expanded['content'],
                'focus_keyword' => $expanded['focus_keyword'],
            ]);

            $row = [
                'id' => $post->id,
                'slug' => $post->slugable?->key,
                'old_title' => $post->name,
                'new_title' => $expanded['title'],
                'focus_keyword' => $expanded['focus_keyword'],
                'before_word_count' => $beforeWordCount,
                'after_word_count' => str_word_count(strip_tags($expanded['content'])),
                'score' => $validation['score'],
                'grade' => $validation['grade'],
                'passed' => $validation['passed'],
                'warnings' => $validation['warnings'],
                'errors' => $validation['errors'],
            ];

            if (!$validation['passed'] || $validation['score'] < $minScore) {
                $report['skipped'][] = $row;
                continue;
            }

            $report['qualified'][] = $row;

            if ($apply) {
                $expander->applyToPost($post);

                $persistedPost = Post::query()->find($post->getKey());
                $persistedWordCount = $persistedPost
                    ? str_word_count(strip_tags((string) $persistedPost->content))
                    : 0;

                $row['persisted_title'] = $persistedPost?->name;
                $row['persisted_description'] = $persistedPost?->description;
                $row['persisted_word_count'] = $persistedWordCount;
                $row['persistence_verified'] = $persistedPost !== null
                    && $persistedPost->name === $expanded['title']
                    && trim((string) $persistedPost->description) === trim((string) $expanded['description'])
                    && $persistedWordCount === $row['after_word_count'];

                if (! $row['persistence_verified']) {
                    $report['apply_failures'][] = $row;
                    continue;
                }

                $report['applied'][] = $row;
                continue;
            }

            $report['applied'][] = $row;
        }

        File::ensureDirectoryExists(dirname($reportPath));
        File::put($reportPath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $this->info('Report written to ' . $reportPath);
        $this->table(['Metric', 'Count'], [
            ['Eligible short posts', $report['eligible_posts']],
            ['Qualified expansions', count($report['qualified'])],
            ['Applied expansions', count($report['applied'])],
            ['Apply failures', count($report['apply_failures'])],
            ['Skipped expansions', count($report['skipped'])],
        ]);

        if ($report['skipped'] !== []) {
            $this->warn('Some posts were skipped because they did not clear the minimum SEO score.');
        }

        if ($report['apply_failures'] !== []) {
            $this->warn('Some expansions cleared the SEO gate but failed persistence verification. Check the report before trusting the run.');
        }

        return self::SUCCESS;
    }
}
