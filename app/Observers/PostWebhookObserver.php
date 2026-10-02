<?php

namespace App\Observers;

use App\Jobs\DeliverWebhook;
use Botble\Blog\Models\Post;
use Illuminate\Support\Facades\Log;

class PostWebhookObserver
{
    /**
     * A post created already published (the AI-reporter automation path
     * publishes on creation) counts as a publish.
     */
    public function created(Post $post): void
    {
        if ($this->isPublished($post)) {
            $this->dispatch($post);
        }
    }

    /**
     * A post whose status flips to published (draft -> published from the
     * admin panel or the automation update path) counts as a publish.
     * Plain edits to an already-published post do not re-fire.
     */
    public function updated(Post $post): void
    {
        if ($this->isPublished($post) && $post->wasChanged('status')) {
            $this->dispatch($post);
        }
    }

    protected function isPublished(Post $post): bool
    {
        // $post->status is a PostStatusEnum instance, not a string:
        // strict comparison against 'published' is always false, so cast.
        return (string) $post->status === 'published';
    }

    protected function dispatch(Post $post): void
    {
        try {
            DeliverWebhook::dispatch($post->getKey());

            Log::info('Webhook: publish event queued', ['post_id' => $post->getKey()]);
        } catch (\Throwable $e) {
            // A webhook failure must never break publishing.
            Log::warning('Webhook: failed to queue publish event', [
                'post_id' => $post->getKey(),
                'error' => $e->getMessage(),
            ]);
        }
    }
}
