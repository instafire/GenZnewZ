<?php

namespace App\Console\Commands;

use App\Services\FactsContentAuditService;
use Botble\Blog\Models\Post;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class AuditFactsContent extends Command
{
    protected $signature = 'seo:audit-facts-content {--days=30 : Minimum age in days for facts posts to audit} {--limit=40 : Number of top-risk posts to print} {--report= : Custom report path}';

    protected $description = 'Audit older /facts/ posts for tone, sourcing clarity, and search-intent fit';

    public function handle(FactsContentAuditService $auditService): int
    {
        $days = max(1, (int) $this->option('days'));
        $limit = max(1, (int) $this->option('limit'));
        $reportPath = $this->option('report') ?: storage_path('app/reports/facts-content-audit-' . now()->format('Ymd-His') . '.json');

        $posts = Post::query()
            ->with('slugable')
            ->where('status', 'published')
            ->where('created_at', '<', now()->subDays($days))
            ->orderBy('created_at')
            ->get()
            ->filter(fn (Post $post) => $post->slugable?->prefix === 'facts')
            ->values();

        $audits = $posts->map(fn (Post $post) => $auditService->audit($post))
            ->sortBy('score')
            ->values();

        $summary = [
            'generated_at' => now()->toIso8601String(),
            'days' => $days,
            'facts_posts_audited' => $audits->count(),
            'recommendations' => $audits->countBy('recommendation')->sortDesc()->all(),
            'average_score' => round($audits->avg('score') ?? 0, 1),
            'top_risks' => $audits->take($limit)->all(),
        ];

        File::ensureDirectoryExists(dirname($reportPath));
        File::put($reportPath, json_encode([
            'summary' => $summary,
            'results' => $audits->all(),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $this->info('Report written to ' . $reportPath);
        $this->table(
            ['Metric', 'Value'],
            [
                ['Facts posts audited', $summary['facts_posts_audited']],
                ['Average score', $summary['average_score']],
                ['Keep', $summary['recommendations']['keep'] ?? 0],
                ['Refresh', $summary['recommendations']['refresh'] ?? 0],
                ['Rewrite', $summary['recommendations']['rewrite'] ?? 0],
                ['Unpublish or rebuild', $summary['recommendations']['unpublish_or_rebuild'] ?? 0],
            ]
        );

        $this->line('');
        $this->info('Top risk facts posts:');

        foreach ($audits->take($limit) as $row) {
            $this->line(sprintf(
                '[%d] %s (%s) score=%d recommendation=%s',
                $row['id'],
                $row['title'],
                $row['slug'],
                $row['score'],
                $row['recommendation']
            ));
        }

        return self::SUCCESS;
    }
}
