<?php

namespace App\Console\Commands;

use App\Services\EditorialQualityService;
use Botble\Blog\Enums\PostStatusEnum;
use Botble\Blog\Models\Post;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class PolishEditorialQuality extends Command
{
    protected $signature = 'seo:polish-editorial {--apply : Persist changes} {--report= : Custom report output path} {--title-threshold=90 : Only retitle posts longer than this}';

    protected $description = 'Unpublish obviously weak thin posts and shorten overlong titles';

    public function handle(EditorialQualityService $service): int
    {
        $apply = (bool) $this->option('apply');
        $titleThreshold = (int) $this->option('title-threshold');
        $reportPath = $this->option('report') ?: storage_path('app/reports/editorial-polish-' . now()->format('Ymd-His') . '.json');

        $posts = Post::with(['slugable', 'categories'])
            ->where('status', 'published')
            ->orderBy('id')
            ->get();

        $report = [
            'generated_at' => now()->toIso8601String(),
            'mode' => $apply ? 'apply' : 'dry-run',
            'unpublished_thin_posts' => [],
            'retitled_posts' => [],
        ];

        foreach ($posts as $post) {
            $thin = $service->analyzeThinPost($post);
            if ($thin['should_unpublish']) {
                $report['unpublished_thin_posts'][] = [
                    'id' => $post->id,
                    'slug' => $post->slugable?->key,
                    'title' => $post->name,
                    'word_count' => $thin['word_count'],
                    'reasons' => $thin['reasons'],
                ];

                if ($apply) {
                    $post->status = PostStatusEnum::DRAFT();
                    $post->saveQuietly();
                }

                continue;
            }

            if (! $service->needsTitlePolish((string) $post->name, $titleThreshold)) {
                continue;
            }

            $shortened = $service->shortenTitle((string) $post->name, 68);
            if ($shortened === '' || $shortened === $post->name) {
                continue;
            }

            $report['retitled_posts'][] = [
                'id' => $post->id,
                'slug' => $post->slugable?->key,
                'old_title' => $post->name,
                'new_title' => $shortened,
                'old_length' => mb_strlen((string) $post->name),
                'new_length' => mb_strlen($shortened),
            ];

            if ($apply) {
                $post->name = $shortened;
                $post->saveQuietly();
            }
        }

        File::ensureDirectoryExists(dirname($reportPath));
        File::put($reportPath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $this->info('Report written to ' . $reportPath);
        $this->table(['Action', 'Count'], [
            ['Unpublished thin posts', count($report['unpublished_thin_posts'])],
            ['Retitled posts', count($report['retitled_posts'])],
        ]);

        return self::SUCCESS;
    }
}
