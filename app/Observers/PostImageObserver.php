<?php

namespace App\Observers;

use App\Services\ArticleContentImageSanitizer;
use App\Services\PexelsImageService;
use App\Jobs\ProcessPostImageJob;
use Botble\Base\Facades\MetaBox;
use Botble\Blog\Models\Post;
use Illuminate\Support\Facades\Log;

class PostImageObserver
{
    /**
     * After a post is created, check if it needs a Pexels image.
     */
    public function created(Post $post): void
    {
        $this->assignImageIfNeeded($post);
    }

    /**
     * After a post is updated, check if it needs a Pexels image.
     * Covers cases where a post is created as draft and later published.
     */
    public function updated(Post $post): void
    {
        // Body content is sanitized on every content edit so legacy or
        // automation-submitted Pexels markup cannot return to an article.
        if (!$post->wasChanged('status') && !$post->wasChanged('image') && !$post->wasChanged('content')) {
            return;
        }

        $this->assignImageIfNeeded($post);
    }

    protected function assignImageIfNeeded(Post $post): void
    {
        $this->sanitizeArticleBody($post);

        if ($this->shouldSkipForAutomationRequest()) {
            return;
        }

        // Skip if post already has a featured image
        if (!empty($post->image)) {
            return;
        }

        // Skip non-published posts
        if ((string) $post->status !== 'published') {
            return;
        }

        // Atomically prevent duplicate processing across concurrent requests.
        $lockKey = 'pexels_processing_' . $post->id;
        if (! cache()->add($lockKey, true, 60)) {
            return;
        }

        // Featured posts should never wait on queue workers for image assignment.
        // This keeps homepage featured/media sections fresh immediately after publish.
        if ((bool) $post->is_featured) {
            $this->processImageImmediately($post);
            return;
        }

        // Defer to a queue job so it doesn't block the request
        if (config('queue.default') !== 'sync') {
            ProcessPostImageJob::dispatch($post->id)->onQueue('media');
            return;
        }

        // Fallback for sync queue (local/dev)
        $this->processImageImmediately($post);
    }

    protected function processImageImmediately(Post $post): void
    {
        try {
            $pexels = app(PexelsImageService::class);
            // Reload categories from DB in case they haven't been synced yet
            $post->load('categories');
            $category = $post->categories->first()->name ?? null;
            $description = $post->description ?? null;
            $customImageQuery = MetaBox::getMetaData($post, 'image_search_query', true);
            $imageDescription = MetaBox::getMetaData($post, 'image_description', true);
            $focusKeyword = MetaBox::getMetaData($post, 'focus_keyword', true);

            Log::info('Pexels Observer: Fetching image for post', [
                'post_id' => $post->id,
                'title' => $post->name,
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
                Log::warning('Pexels Observer: No image found', ['post_id' => $post->id]);
                return;
            }

            $pexels->assignImageToPost($post, $result);

            Log::info('Pexels Observer: Image assigned', [
                'post_id' => $post->id,
                'image' => $result['media_url'],
            ]);
        } catch (\Exception $e) {
            Log::error('Pexels Observer: Exception', [
                'post_id' => $post->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function sanitizeArticleBody(Post $post): void
    {
        $sanitizedContent = app(ArticleContentImageSanitizer::class)->sanitize((string) $post->content);

        if ($sanitizedContent !== (string) $post->content) {
            $post->content = $sanitizedContent;
            $post->saveQuietly();
        }
    }

    protected function shouldSkipForAutomationRequest(): bool
    {
        $request = request();

        if (!$request) {
            return false;
        }

        return $request->is('api/v1/automation/posts/create')
            || $request->is('api/v1/automation/posts/*/update');
    }
}
