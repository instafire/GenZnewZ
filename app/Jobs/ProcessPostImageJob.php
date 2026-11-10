<?php

namespace App\Jobs;

use App\Services\PexelsImageService;
use Botble\Base\Facades\MetaBox;
use Botble\Blog\Models\Post;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessPostImageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 120;
    public $tries = 4;
    public $backoff = [300, 1800, 7200];

    protected int $postId;

    public function __construct(int $postId)
    {
        $this->postId = $postId;
    }

    public function handle(PexelsImageService $pexels): void
    {
        $post = Post::find($this->postId);

        if (!$post) {
            return;
        }

        // Skip only if the post already has its own featured image. An image
        // shared with another published post is a borrowed category-fallback
        // image (the 2026-10-25 fallback stamps the same file on many posts),
        // so fall through and fetch this post a real one — otherwise the
        // fallback permanently blocks the retry it was meant to precede.
        if (!empty($post->image)) {
            $shared = Post::query()
                ->where('image', $post->image)
                ->where('status', 'published')
                ->where('id', '!=', $post->id)
                ->exists();

            if (! $shared) {
                return;
            }

            Log::info('Pexels Job: Post has a borrowed fallback image, fetching a real one', [
                'post_id' => $post->id,
                'image' => $post->image,
            ]);
        }

        // Skip non-published posts
        if ((string) $post->status !== 'published') {
            return;
        }

        $post->loadMissing('categories');
        $category = $post->categories->first()->name ?? null;
        $description = $post->description ?? null;

        $customImageQuery = MetaBox::getMetaData($post, 'image_search_query', true);
        $imageDescription = MetaBox::getMetaData($post, 'image_description', true);
        $focusKeyword = MetaBox::getMetaData($post, 'focus_keyword', true);
        
        if (!empty($customImageQuery)) {
            Log::info('Pexels Job: Using custom image search query from AI agent', [
                'post_id' => $post->id,
                'title' => $post->name,
                'custom_query' => $customImageQuery,
            ]);
        }

        Log::info('Pexels Job: Fetching image for post', [
            'post_id' => $post->id,
            'title' => $post->name,
            'category' => $category,
            'has_custom_query' => !empty($customImageQuery),
            'attempt' => $this->attempts(),
        ]);

        $result = $pexels->fetchAndUploadImage(
            $post->name,
            $description,
            $category,
            $customImageQuery ?: null,
            $imageDescription ?: null,
            $focusKeyword ?: null
        );

        if (!$result) {
            if ($this->attempts() < $this->tries) {
                $delay = $this->backoff[min($this->attempts() - 1, count($this->backoff) - 1)];

                Log::warning('Pexels Job: No image found, releasing for retry', [
                    'post_id' => $post->id,
                    'attempt' => $this->attempts(),
                    'delay_seconds' => $delay,
                    'cooldown_active' => $pexels->isCoolingDown(),
                ]);

                $this->release($delay);
                return;
            }

            Log::warning('Pexels Job: No image found for post after final attempt', [
                'post_id' => $post->id,
                'attempt' => $this->attempts(),
            ]);

            return;
        }

        $pexels->assignImageToPost($post, $result);

        Log::info('Pexels Job: Image assigned to post', [
            'post_id' => $post->id,
            'image' => $result['media_url'],
            'photographer' => $result['photographer'],
            'pexels_photo_id' => $result['pexels_photo_id'],
            'used_custom_query' => !empty($customImageQuery),
        ]);
    }
}
