<?php

namespace App\Console\Commands;

use App\Services\ArticleContentImageSanitizer;
use Botble\Blog\Models\Post;
use Illuminate\Console\Command;

class CleanPexelsBodyImages extends Command
{
    protected $signature = 'posts:clean-pexels-body-images
                            {--apply : Persist the cleanup instead of reporting only}
                            {--limit= : Process at most this many matching posts}';

    protected $description = 'Remove Pexels image markup from published article bodies while preserving featured images';

    public function handle(ArticleContentImageSanitizer $sanitizer): int
    {
        $apply = (bool) $this->option('apply');
        $limit = $this->option('limit');
        $limit = $limit !== null ? max(1, (int) $limit) : null;
        $matched = 0;
        $changed = 0;

        $query = Post::query()
            ->where('status', 'published')
            ->where(function ($query): void {
                $query
                    ->where('content', 'like', '%storage/pexels%')
                    ->orWhere('content', 'like', '%pexels.com%')
                    ->orWhere('content', 'like', '%![](%');
            })
            ->orderBy('id');

        if ($limit !== null) {
            $query->limit($limit);
        }

        $query->get(['id', 'name', 'content'])->each(function (Post $post) use ($sanitizer, $apply, &$matched, &$changed): void {
            $matched++;
            $cleaned = $sanitizer->sanitize((string) $post->content);

            if ($cleaned === (string) $post->content) {
                return;
            }

            $changed++;
            $this->line(sprintf('%s #%d %s', $apply ? 'Cleaning' : 'Would clean', $post->id, $post->name));

            if ($apply) {
                $post->content = $cleaned;
                $post->saveQuietly();
            }
        });

        $this->newLine();
        $this->info(sprintf(
            '%s: %d matching posts, %d %s.',
            $apply ? 'Cleanup complete' : 'Dry run complete',
            $matched,
            $changed,
            $apply ? 'updated' : 'would be updated'
        ));

        if (! $apply && $changed > 0) {
            $this->comment('Run with --apply after reviewing the list to persist these changes.');
        }

        return self::SUCCESS;
    }
}
