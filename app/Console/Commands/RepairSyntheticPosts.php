<?php

namespace App\Console\Commands;

use App\Services\AutomationContentGuardService;
use App\Services\EditorialQualityService;
use App\Services\SyntheticPostRepairService;
use Botble\Base\Facades\MetaBox;
use Botble\Blog\Enums\PostStatusEnum;
use Botble\Blog\Models\Post;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class RepairSyntheticPosts extends Command
{
    protected $signature = 'seo:repair-synthetic-posts
                            {--apply : Persist changes instead of reporting only}
                            {--status=published : Comma-separated post statuses to scan}
                            {--limit=0 : Optional limit after filtering}
                            {--report= : Custom report output path}';

    protected $description = 'Repair or draft synthetic AI slop posts with templated titles and body artifacts';

    public function handle(
        SyntheticPostRepairService $repairService,
        EditorialQualityService $editorialQualityService,
        AutomationContentGuardService $guard
    ): int {
        $apply = (bool) $this->option('apply');
        $limit = max(0, (int) $this->option('limit'));
        $statuses = collect(explode(',', (string) $this->option('status')))
            ->map(fn (string $status) => trim($status))
            ->filter()
            ->values()
            ->all();

        if ($statuses === []) {
            $statuses = ['published'];
        }

        $reportPath = $this->option('report') ?: storage_path('app/reports/synthetic-post-repair-' . now()->format('Ymd-His') . '.json');

        $posts = Post::query()
            ->with(['slugable'])
            ->whereIn('status', $statuses)
            ->orderByDesc('id')
            ->get();

        if ($limit > 0) {
            $posts = $posts->take($limit);
        }

        $report = [
            'generated_at' => now()->toIso8601String(),
            'mode' => $apply ? 'apply' : 'dry-run',
            'statuses' => $statuses,
            'posts_scanned' => $posts->count(),
            'repaired_posts' => [],
            'drafted_posts' => [],
        ];

        foreach ($posts as $post) {
            $proposal = $repairService->proposeRepair($post, $editorialQualityService, $guard);

            if (! ($proposal['flagged'] ?? false)) {
                continue;
            }

            if (($proposal['action'] ?? '') === 'repair') {
                $report['repaired_posts'][] = [
                    'id' => $post->id,
                    'slug' => $post->slugable?->key,
                    'old_title' => $post->name,
                    'new_title' => $proposal['new_title'],
                    'reasons' => $proposal['reasons'],
                ];

                if ($apply) {
                    $post->name = $proposal['new_title'];
                    $post->description = $proposal['new_description'];
                    $post->content = $proposal['new_content'];
                    $post->saveQuietly();

                    MetaBox::saveMetaBoxData($post, 'focus_keyword', $proposal['new_focus_keyword']);
                }

                continue;
            }

            $report['drafted_posts'][] = [
                'id' => $post->id,
                'slug' => $post->slugable?->key,
                'title' => $post->name,
                'reasons' => $proposal['reasons'],
            ];

            if ($apply) {
                $post->status = PostStatusEnum::DRAFT();
                $post->saveQuietly();
            }
        }

        File::ensureDirectoryExists(dirname($reportPath));
        File::put($reportPath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $this->info('Report written to ' . $reportPath);
        $this->table(['Action', 'Count'], [
            ['Repaired posts', count($report['repaired_posts'])],
            ['Drafted posts', count($report['drafted_posts'])],
        ]);

        return self::SUCCESS;
    }
}
