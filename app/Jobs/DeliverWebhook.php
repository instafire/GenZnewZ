<?php

namespace App\Jobs;

use App\Models\WebhookSubscription;
use Botble\Blog\Models\Category;
use Botble\Blog\Models\Post;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DeliverWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /**
     * Disable after this many consecutive delivery failures.
     */
    public const MAX_CONSECUTIVE_FAILURES = 5;

    public function __construct(public int $postId)
    {
    }

    public function handle(): void
    {
        $post = Post::query()
            ->with(['categories', 'tags', 'author', 'slugable'])
            ->find($this->postId);

        if (! $post || (string) $post->status !== 'published') {
            return;
        }

        $subscriptions = WebhookSubscription::query()
            ->where('is_active', true)
            ->get();

        foreach ($subscriptions as $subscription) {
            if (! $subscription->subscribedTo(WebhookSubscription::EVENT_ARTICLE_PUBLISHED)) {
                continue;
            }

            $this->deliver($subscription, $post);
        }
    }

    protected function deliver(WebhookSubscription $subscription, Post $post): void
    {
        $payload = [
            'event' => WebhookSubscription::EVENT_ARTICLE_PUBLISHED,
            'delivered_at' => now()->toIso8601ZuluString(),
            'delivery_id' => (string) Str::uuid(),
            'article' => $this->articlePayload($post),
        ];

        $body = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        // Sign the exact bytes that are sent: HMAC-SHA256 of the raw body.
        // Receivers recompute hex(hash_hmac('sha256', raw_body, secret)) and
        // compare against the part after "sha256=".
        $signature = 'sha256=' . hash_hmac('sha256', $body, $subscription->secret);

        try {
            $response = Http::timeout(8)
                ->connectTimeout(4)
                ->withBody($body, 'application/json')
                ->withHeaders([
                    'X-GNZ-Event' => WebhookSubscription::EVENT_ARTICLE_PUBLISHED,
                    'X-GNZ-Signature' => $signature,
                    'User-Agent' => 'GenZNewZ-Webhooks/1.0',
                ])
                ->post($subscription->url);

            if ($response->successful()) {
                $subscription->update([
                    'consecutive_failures' => 0,
                    'last_delivery_at' => now(),
                ]);

                return;
            }

            $this->recordFailure($subscription, 'HTTP ' . $response->status());
        } catch (\Throwable $e) {
            $this->recordFailure($subscription, $e->getMessage());
        }
    }

    protected function recordFailure(WebhookSubscription $subscription, string $reason): void
    {
        $failures = $subscription->consecutive_failures + 1;

        $subscription->update([
            'consecutive_failures' => $failures,
            'is_active' => $failures < self::MAX_CONSECUTIVE_FAILURES,
        ]);

        Log::warning('Webhook: delivery failed', [
            'subscription_id' => $subscription->getKey(),
            'url' => $subscription->url,
            'reason' => $reason,
            'consecutive_failures' => $failures,
            'disabled' => $failures >= self::MAX_CONSECUTIVE_FAILURES,
        ]);
    }

    protected function articlePayload(Post $post): array
    {
        $slug = $post->slugable?->key;

        return [
            'id' => $post->getKey(),
            'title' => (string) $post->name,
            'slug' => $slug,
            'url' => (string) $post->url,
            'markdown_url' => $slug ? url('/' . $slug . '.md') : null,
            'description' => (string) $post->description,
            'published_at' => ($post->created_at ?? now())->toIso8601ZuluString(),
            'topics' => $post->categories->map(fn (Category $c) => [
                'id' => $c->getKey(),
                'name' => $c->name,
                'slug' => $c->slug,
            ])->all(),
            'tags' => $post->tags->pluck('name')->all(),
            'author' => $post->author->name ?? null,
            'image' => $post->image ? url($post->image) : null,
        ];
    }
}
