<?php

namespace App\Jobs;

use Botble\Blog\Models\Post;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\SerializesModels;

class TrackHomepageViewsJob
{
    use Dispatchable;
    use Queueable;
    use SerializesModels;

    public function __construct(protected array $postIds)
    {
    }

    public function handle(): void
    {
        $postIds = collect($this->postIds)
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($postIds->isEmpty()) {
            return;
        }

        Post::withoutEvents(function () use ($postIds): void {
            Post::withoutTimestamps(function () use ($postIds): void {
                Post::whereIn('id', $postIds)->increment('views');
            });
        });
    }
}
