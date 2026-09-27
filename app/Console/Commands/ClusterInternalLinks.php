<?php

namespace App\Console\Commands;

use App\Services\TopicClusterLinkService;
use Botble\Blog\Models\Post;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ClusterInternalLinks extends Command
{
    protected $signature = 'seo:cluster-internal-links
                            {--apply : Persist the generated topic-cluster links}
                            {--report= : Custom report output path}';

    protected $description = 'Add or refresh topic-cluster internal links across published posts';

    public function handle(TopicClusterLinkService $service): int
    {
        $apply = (bool) $this->option('apply');
        $reportPath = $this->option('report') ?: storage_path('app/reports/topic-cluster-links-' . now()->format('Ymd-His') . '.json');

        $posts = Post::with(['slugable', 'categories.slugable'])
            ->where('status', 'published')
            ->orderBy('id')
            ->get()
            ->filter(fn (Post $post) => $this->countInternalLinks((string) $post->content) < 2)
            ->values();

        $categoryPools = [];
        foreach ($posts as $post) {
            foreach ($post->categories as $category) {
                $categoryPools[$category->id] ??= collect();
                $categoryPools[$category->id]->push($post);
            }
        }

        $report = [
            'generated_at' => now()->toIso8601String(),
            'mode' => $apply ? 'apply' : 'dry-run',
            'processed_posts' => $posts->count(),
            'changed_posts' => [],
            'skipped_posts' => [],
        ];

        foreach ($posts as $post) {
            $pool = collect();
            foreach ($post->categories->pluck('id') as $categoryId) {
                $pool = $pool->merge($categoryPools[$categoryId] ?? collect());
            }
            $pool = $pool->unique('id')->values();

            $result = $apply
                ? $service->saveToPost($post, $pool)
                : $service->applyToPost($post, $pool);

            $row = [
                'id' => $post->id,
                'slug' => $post->slugable?->key,
                'title' => $post->name,
                'before_internal_links' => $result['before_internal_links'],
                'after_internal_links' => $result['after_internal_links'],
                'related_post_ids' => $result['related_post_ids'],
            ];

            if ($result['changed']) {
                $report['changed_posts'][] = $row;
            } else {
                $report['skipped_posts'][] = $row;
            }
        }

        File::ensureDirectoryExists(dirname($reportPath));
        File::put($reportPath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $this->info('Report written to ' . $reportPath);
        $this->table(['Metric', 'Count'], [
            ['Processed posts', $report['processed_posts']],
            ['Changed posts', count($report['changed_posts'])],
            ['Skipped posts', count($report['skipped_posts'])],
        ]);

        return self::SUCCESS;
    }

    protected function countInternalLinks(string $content): int
    {
        preg_match_all('/<a[^>]+href=["\'](\/[^"\']*|https?:\/\/(www\.)?genznewz\.com[^"\']*)["\'][^>]*>/i', $content, $matches);

        return count($matches[0]);
    }
}
