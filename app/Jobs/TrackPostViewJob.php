<?php

namespace App\Jobs;

use Botble\Blog\Models\Post;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\SerializesModels;

class TrackPostViewJob
{
    use Dispatchable;
    use Queueable;
    use SerializesModels;

    public function __construct(protected string $slug)
    {
    }

    public function handle(): void
    {
        if ($this->slug === '') {
            return;
        }

        $post = Post::query()
            ->whereHas('slugable', function ($query): void {
                $query->where('key', $this->slug);
            })
            ->first(['id']);

        if (! $post) {
            return;
        }

        Post::withoutEvents(function () use ($post): void {
            Post::withoutTimestamps(function () use ($post): void {
                $post->increment('views', 1);
            });
        });
    }
}
