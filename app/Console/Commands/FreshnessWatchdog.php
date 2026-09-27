<?php

namespace App\Console\Commands;

use Botble\Base\Events\AdminNotificationEvent;
use Botble\Base\Supports\AdminNotificationItem;
use Botble\Blog\Models\Post;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/**
 * Watches for a silent newsroom.
 *
 * This site's indexing problem is driven by publication gaps: a news site that
 * stops publishing loses crawl priority, and nothing in the app makes that
 * visible. This command turns "we quietly stopped shipping" into an admin
 * notification the same day instead of a month later.
 *
 * Exit code is non-zero while the newsroom is stale, so external monitoring can
 * also catch it without parsing output.
 */
class FreshnessWatchdog extends Command
{
    /** Cache key holding the timestamp of the last alert, used for cooldown. */
    public const COOLDOWN_KEY = 'content:freshness_watchdog:last_alert';

    protected $signature = 'content:freshness-watchdog
                            {--hours= : Alert when the newest published article is older than this (default 24, or CONTENT_FRESHNESS_HOURS)}
                            {--cooldown= : Minimum hours between alerts (default 24, or CONTENT_FRESHNESS_COOLDOWN_HOURS)}
                            {--dry-run : Report the verdict without alerting or starting a cooldown}';

    protected $description = 'Alert when nothing has been published recently, so a stalled newsroom never goes unnoticed';

    public function handle(): int
    {
        $thresholdHours = $this->resolveHours('hours', 'CONTENT_FRESHNESS_HOURS', 24);
        $cooldownHours = $this->resolveHours('cooldown', 'CONTENT_FRESHNESS_COOLDOWN_HOURS', 24);

        $newest = Post::wherePublished()
            ->orderByDesc('created_at')
            ->first(['id', 'name', 'created_at']);

        if ($newest && ! $newest->created_at) {
            $newest = null;
        }

        $ageHours = $newest
            ? $this->ageInHours($newest)
            : null;

        // Fresh enough: clear the cooldown so the next quiet spell alerts at once.
        if ($ageHours !== null && $ageHours < $thresholdHours) {
            Cache::forget(self::COOLDOWN_KEY);

            $this->info(sprintf(
                'Fresh: newest article #%d "%s" is %d hour(s) old (threshold %dh).',
                $newest->id,
                Str::limit((string) $newest->name, 60),
                $ageHours,
                $thresholdHours
            ));

            return self::SUCCESS;
        }

        $headline = 'Newsroom has gone quiet';
        $detail = $this->describe($newest, $ageHours, $thresholdHours);

        $this->warn($headline . ': ' . $detail);

        if ($this->option('dry-run')) {
            $this->line('Dry run: no alert sent and no cooldown started.');

            return self::FAILURE;
        }

        if (Cache::has(self::COOLDOWN_KEY)) {
            $this->line(sprintf(
                'Suppressed: an alert was already sent within the last %d hour(s).',
                $cooldownHours
            ));

            return self::FAILURE;
        }

        Log::warning('Freshness watchdog: ' . $detail, [
            'newest_post_id' => $newest?->id,
            'newest_post_at' => $newest?->created_at?->toIso8601String(),
            'age_hours' => $ageHours,
            'threshold_hours' => $thresholdHours,
        ]);

        $this->notifyAdmins($headline, $detail);

        Cache::put(self::COOLDOWN_KEY, now()->toIso8601String(), now()->addHours($cooldownHours));

        $this->line('Admin notification sent.');

        return self::FAILURE;
    }

    /**
     * Resolve a duration in hours: explicit CLI option wins, then env, then default.
     */
    protected function resolveHours(string $option, string $env, int $default): int
    {
        $value = $this->option($option);

        if ($value === null || $value === '') {
            $value = env($env, $default);
        }

        return max(1, (int) $value);
    }

    /**
     * Whole hours since an article was published. Computed from timestamps
     * rather than Carbon's diff helpers so the sign is never ambiguous.
     */
    protected function ageInHours(Post $post): int
    {
        return (int) floor((now()->getTimestamp() - $post->created_at->getTimestamp()) / 3600);
    }

    protected function describe(?Post $newest, ?int $ageHours, int $thresholdHours): string
    {
        if (! $newest) {
            return 'No articles have ever been published.';
        }

        $days = intdiv($ageHours, 24);

        $age = $days >= 2
            ? sprintf('%d days', $days)
            : sprintf('%d hours', $ageHours);

        return sprintf(
            'The newest article is #%d "%s", published %s (%s ago). Threshold is %d hour(s).',
            $newest->id,
            Str::limit((string) $newest->name, 60),
            $newest->created_at->toDateTimeString(),
            $age,
            $thresholdHours
        );
    }

    /**
     * Admin URL for the post list, resolved defensively.
     *
     * The admin panel's routes are not guaranteed to be registered in every
     * context this command can run in. An unresolvable route used to throw while
     * building the notification, so the alert was silently lost - the exact
     * failure the watchdog exists to prevent.
     */
    protected function postsAdminUrl(): string
    {
        try {
            if (Route::has('posts.index')) {
                return route('posts.index');
            }
        } catch (\Throwable $e) {
            // Fall through to the conventional admin path.
        }

        return url('/admin/blog/posts');
    }

    protected function notifyAdmins(string $title, string $detail): void
    {
        try {
            // AdminNotificationEvent does not use the Dispatchable trait, so it has
            // no ::dispatch() - it must go through the dispatcher directly.
            Event::dispatch(
                new AdminNotificationEvent(AdminNotificationItem::make()
                    ->title($title)
                    ->description($detail)
                    ->action('Review published posts', $this->postsAdminUrl())
                    ->permission('posts.index')
                )
            );
        } catch (\Throwable $e) {
            // The log entry above is the durable signal; never fail the command
            // just because the in-admin bell could not be written.
            Log::warning('Freshness watchdog: could not create admin notification', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
