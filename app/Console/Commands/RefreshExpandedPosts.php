<?php

namespace App\Console\Commands;

use App\Services\AutomationContentGuardService;
use App\Services\SeoValidationService;
use App\Services\ShortPostExpansionService;
use Botble\Blog\Models\Post;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class RefreshExpandedPosts extends Command
{
    protected $signature = 'seo:refresh-expanded-posts
                            {--apply : Persist the refreshed copy}
                            {--ids= : Comma-separated post IDs to process}
                            {--limit= : Maximum number of matching posts to process}
                            {--min-score=80 : Minimum SEO score required to apply the refresh}
                            {--report= : Custom report output path}';

    protected $description = 'Refresh legacy expanded posts that still contain boilerplate scaffolding or weak update-style titles';

    public function handle(
        ShortPostExpansionService $expander,
        SeoValidationService $seo,
        AutomationContentGuardService $guard
    ): int {
        $apply = (bool) $this->option('apply');
        $minScore = max(0, (int) $this->option('min-score'));
        $ids = collect(explode(',', (string) $this->option('ids')))
            ->map(fn (string $id) => (int) trim($id))
            ->filter(fn (int $id) => $id > 0)
            ->values();
        $limit = $this->option('limit') !== null ? max(1, (int) $this->option('limit')) : null;
        $reportPath = $this->option('report') ?: storage_path('app/reports/refresh-expanded-posts-' . now()->format('Ymd-His') . '.json');

        $query = Post::with(['categories', 'slugable'])
            ->where('status', 'published')
            ->orderBy('id');

        if ($ids->isNotEmpty()) {
            $query->whereIn('id', $ids->all());
        }

        $posts = $query
            ->get()
            ->filter(fn (Post $post) => $ids->isNotEmpty() || $expander->needsLegacyRefresh($post))
            ->when($limit !== null, fn ($collection) => $collection->take($limit))
            ->values();

        $report = [
            'generated_at' => now()->toIso8601String(),
            'mode' => $apply ? 'apply' : 'dry-run',
            'ids' => $ids->all(),
            'limit' => $limit,
            'min_score' => $minScore,
            'matching_posts' => $posts->count(),
            'qualified' => [],
            'applied' => [],
            'apply_failures' => [],
            'skipped' => [],
        ];

        foreach ($posts as $post) {
            $beforeHits = $expander->countLegacyBoilerplateHits((string) $post->content);
            $expanded = $expander->expand($post);
            $afterHits = $expander->countLegacyBoilerplateHits($expanded['content']);

            $seoValidation = $seo->validate([
                'title' => $expanded['title'],
                'description' => $expanded['description'],
                'content' => $expanded['content'],
                'focus_keyword' => $expanded['focus_keyword'],
            ]);

            $guardValidation = $guard->validate([
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
                'before_hits' => $beforeHits,
                'after_hits' => $afterHits,
                'seo_score' => $seoValidation['score'],
                'seo_grade' => $seoValidation['grade'],
                'seo_errors' => $seoValidation['errors'],
                'seo_warnings' => $seoValidation['warnings'],
                'guard_passed' => $guardValidation['passed'],
                'guard_errors' => $guardValidation['errors'],
                'guard_warnings' => $guardValidation['warnings'],
                'before_word_count' => str_word_count(strip_tags((string) $post->content)),
                'after_word_count' => str_word_count(strip_tags($expanded['content'])),
            ];

            $improved = $afterHits < $beforeHits
                || trim((string) $expanded['title']) !== trim((string) $post->name);

            if (! $improved || ! $guardValidation['passed'] || ! $seoValidation['passed'] || $seoValidation['score'] < $minScore) {
                $report['skipped'][] = $row;
                continue;
            }

            $report['qualified'][] = $row;

            if (! $apply) {
                $report['applied'][] = $row;
                continue;
            }

            $expander->applyToPost($post);

            $persistedPost = Post::query()->find($post->getKey());
            $persistedHits = $persistedPost ? $expander->countLegacyBoilerplateHits((string) $persistedPost->content) : 999;

            $row['persisted_title'] = $persistedPost?->name;
            $row['persisted_hits'] = $persistedHits;
            $row['persisted_word_count'] = $persistedPost ? str_word_count(strip_tags((string) $persistedPost->content)) : 0;
            $row['persistence_verified'] = $persistedPost !== null
                && trim((string) $persistedPost->name) === trim((string) $expanded['title'])
                && $persistedHits <= $afterHits;

            if (! $row['persistence_verified']) {
                $report['apply_failures'][] = $row;
                continue;
            }

            $report['applied'][] = $row;
        }

        File::ensureDirectoryExists(dirname($reportPath));
        File::put($reportPath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $this->info('Report written to ' . $reportPath);
        $this->table(['Metric', 'Count'], [
            ['Matching posts', $report['matching_posts']],
            ['Qualified refreshes', count($report['qualified'])],
            ['Applied refreshes', count($report['applied'])],
            ['Apply failures', count($report['apply_failures'])],
            ['Skipped refreshes', count($report['skipped'])],
        ]);

        if ($report['skipped'] !== []) {
            $this->warn('Some matching posts did not improve enough or failed validation. Check the report before rerunning.');
        }

        if ($report['apply_failures'] !== []) {
            $this->warn('Some refreshes failed persistence verification. Check the report before trusting the run.');
        }

        return self::SUCCESS;
    }
}
