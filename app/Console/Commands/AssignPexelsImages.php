<?php

namespace App\Console\Commands;

use App\Services\PexelsImageService;
use Botble\Base\Facades\MetaBox;
use Botble\Base\Models\MetaBox as MetaBoxModel;
use Botble\Blog\Models\Post;
use Illuminate\Console\Command;

class AssignPexelsImages extends Command
{
    protected $signature = 'posts:assign-pexels-images
                            {--limit=50 : Maximum number of posts to process}
                            {--dry-run : Show which posts would be updated without making changes}
                            {--reassign : Re-fetch images for posts that already have Pexels images}';

    protected $description = 'Assign Pexels images to published posts that have no featured image';

    public function handle(PexelsImageService $pexels): int
    {
        $limit = (int) $this->option('limit');
        $dryRun = $this->option('dry-run');
        $reassign = $this->option('reassign');

        if ($reassign) {
            // Find posts that have a pexels_photo_id meta (were assigned by this system)
            $pexelsPostIds = MetaBoxModel::where('meta_key', 'pexels_photo_id')
                ->where('reference_type', Post::class)
                ->pluck('reference_id');

            $posts = Post::where('status', 'published')
                ->whereIn('id', $pexelsPostIds)
                ->orderByDesc('created_at')
                ->limit($limit)
                ->get();

            $this->info("Reassign mode: Found {$posts->count()} posts with Pexels images to re-fetch.");
        } else {
            $posts = Post::where('status', 'published')
                ->where(function ($q) {
                    $q->whereNull('image')->orWhere('image', '');
                })
                ->orderByDesc('created_at')
                ->limit($limit)
                ->get();
        }

        if ($posts->isEmpty()) {
            $this->info('No posts found to process.');
            return self::SUCCESS;
        }

        if (!$reassign) {
            $this->info("Found {$posts->count()} posts without featured images.");
        }

        if ($dryRun) {
            $this->info('Dry run mode - no changes will be made.');
        }

        $bar = $this->output->createProgressBar($posts->count());
        $bar->start();

        $success = 0;
        $failed = 0;

        foreach ($posts as $post) {
            $category = $post->categories->first()->name ?? null;
            $description = $post->description ?? null;
            $customImageQuery = MetaBox::getMetaData($post, 'image_search_query', true);
            $imageDescription = MetaBox::getMetaData($post, 'image_description', true);
            $focusKeyword = MetaBox::getMetaData($post, 'focus_keyword', true);

            if ($dryRun) {
                $this->newLine();
                $guidance = $pexels->prepareImageGuidance(
                    $post->name,
                    $description,
                    $category,
                    $focusKeyword ?: null,
                    $customImageQuery ?: null,
                    $imageDescription ?: null
                );
                $this->line("  ID: {$post->id} | {$post->name}");
                $this->line("    Search query: " . ($guidance['search_query'] ?? ''));
                $this->line("    Visual description: " . ($guidance['visual_description'] ?? ''));
                $this->line("    Queries: " . implode(' -> ', $guidance['queries'] ?? []));
                $bar->advance();
                continue;
            }

            $result = $pexels->fetchAndUploadImage(
                $post->name,
                $description,
                $category,
                $customImageQuery ?: null,
                $imageDescription ?: null,
                $focusKeyword ?: null
            );

            if ($result) {
                $pexels->assignImageToPost($post, $result);

                $success++;
            } else {
                $failed++;
                $this->newLine();
                $this->warn("  Failed to find image for: {$post->name} (ID: {$post->id})");
            }

            $bar->advance();

            // Respect Pexels rate limits (200 req/hour)
            usleep(500000); // 0.5s delay between requests
        }

        $bar->finish();
        $this->newLine(2);

        if (!$dryRun) {
            $this->info("Done! Assigned images to {$success} posts. Failed: {$failed}.");
        }

        return self::SUCCESS;
    }
}
