<?php

namespace Tests\Feature;

use Botble\Base\Events\AdminNotificationEvent;
use Botble\Base\Models\AdminNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tests\Concerns\CreatesPosts;
use Tests\TestCase;
use Tests\Traits\RefreshDatabaseWithPlugins;

/**
 * A news site that quietly stops publishing loses crawl priority, and nothing
 * else in the app makes that visible. These tests pin the watchdog's contract:
 * fresh is silent, stale is loud, and it does not turn into hourly noise.
 */
class FreshnessWatchdogTest extends TestCase
{
    use CreatesPosts;
    use RefreshDatabaseWithPlugins;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    /**
     * Fake only the admin notification. Faking every event would also silence
     * Eloquent observers, and the post fixtures depend on one of them.
     */
    protected function fakeAdminNotifications(): void
    {
        Event::fake([AdminNotificationEvent::class]);
    }

    public function test_fresh_content_reports_ok_and_sends_no_alert(): void
    {
        $this->fakeAdminNotifications();

        $this->createPost(['name' => 'Just Published']);

        $this->artisan('content:freshness-watchdog')->assertExitCode(0);

        Event::assertNotDispatched(AdminNotificationEvent::class);
    }

    public function test_stale_newsroom_fails_and_alerts_admins(): void
    {
        $this->fakeAdminNotifications();

        $this->createPost(['name' => 'Old News', 'created_at' => now()->subDays(5)]);

        $this->artisan('content:freshness-watchdog')->assertExitCode(1);

        Event::assertDispatched(AdminNotificationEvent::class, function (AdminNotificationEvent $event): bool {
            return str_contains($event->item->getTitle(), 'quiet')
                && str_contains($event->item->getDescription(), 'Old News');
        });
    }

    public function test_drafts_do_not_count_as_fresh_content(): void
    {
        $this->fakeAdminNotifications();

        $this->createPost(['name' => 'Stale Published', 'created_at' => now()->subDays(6)]);
        $this->createPost(['name' => 'Fresh Draft', 'status' => 'draft']);

        $this->artisan('content:freshness-watchdog')->assertExitCode(1);

        Event::assertDispatched(AdminNotificationEvent::class);
    }

    public function test_an_empty_newsroom_alerts(): void
    {
        $this->fakeAdminNotifications();

        $this->artisan('content:freshness-watchdog')->assertExitCode(1);

        Event::assertDispatched(
            AdminNotificationEvent::class,
            fn (AdminNotificationEvent $event): bool => str_contains(
                $event->item->getDescription(),
                'No articles have ever been published'
            )
        );
    }

    /**
     * The command is scheduled hourly, so without a cooldown a week-long gap
     * would mean 168 identical alerts and the signal would be ignored.
     */
    public function test_repeated_runs_during_the_cooldown_alert_only_once(): void
    {
        $this->fakeAdminNotifications();

        $this->createPost(['name' => 'Old News', 'created_at' => now()->subDays(5)]);

        $this->artisan('content:freshness-watchdog')->assertExitCode(1);
        $this->artisan('content:freshness-watchdog')->assertExitCode(1);
        $this->artisan('content:freshness-watchdog')->assertExitCode(1);

        Event::assertDispatchedTimes(AdminNotificationEvent::class, 1);
    }

    public function test_a_dry_run_does_not_alert_or_start_a_cooldown(): void
    {
        $this->fakeAdminNotifications();

        $this->createPost(['name' => 'Old News', 'created_at' => now()->subDays(5)]);

        $this->artisan('content:freshness-watchdog', ['--dry-run' => true])->assertExitCode(1);

        Event::assertNotDispatched(AdminNotificationEvent::class);

        // A real run afterwards must still be able to alert.
        $this->artisan('content:freshness-watchdog')->assertExitCode(1);

        Event::assertDispatchedTimes(AdminNotificationEvent::class, 1);
    }

    public function test_publishing_again_clears_the_cooldown_for_the_next_gap(): void
    {
        $this->fakeAdminNotifications();

        $this->createPost(['name' => 'Old News', 'created_at' => now()->subDays(5)]);

        $this->artisan('content:freshness-watchdog')->assertExitCode(1);

        // A fresh article resets the cooldown...
        $this->createPost(['name' => 'Fresh News']);
        $this->artisan('content:freshness-watchdog')->assertExitCode(0);

        // ...so the next quiet spell alerts immediately rather than being suppressed.
        DB::table('posts')->update(['created_at' => now()->subDays(10)]);

        $this->artisan('content:freshness-watchdog')->assertExitCode(1);

        Event::assertDispatchedTimes(AdminNotificationEvent::class, 2);
    }

    public function test_a_custom_threshold_is_honoured(): void
    {
        $this->fakeAdminNotifications();

        $this->createPost(['name' => 'Thirty Hours Old', 'created_at' => now()->subHours(30)]);

        // Stale at the 24h default...
        $this->artisan('content:freshness-watchdog')->assertExitCode(1);
        Event::assertDispatchedTimes(AdminNotificationEvent::class, 1);

        // ...but healthy at 48h.
        Cache::flush();

        $this->artisan('content:freshness-watchdog', ['--hours' => 48])->assertExitCode(0);
        Event::assertDispatchedTimes(AdminNotificationEvent::class, 1);
    }

    /**
     * The bell icon is permission-filtered, so an empty permission string would
     * make the alert invisible to every non-super-user admin.
     */
    public function test_the_alert_is_persisted_with_a_usable_permission(): void
    {
        // Deliberately not faked: this asserts the real listener writes a row.
        $this->createPost(['name' => 'Old News', 'created_at' => now()->subDays(5)]);

        $this->artisan('content:freshness-watchdog')->assertExitCode(1);

        $notification = AdminNotification::query()->latest('id')->first();

        $this->assertNotNull($notification, 'The watchdog must leave a notification behind.');
        $this->assertSame('posts.index', $notification->permission);
        $this->assertNotNull($notification->action_url);
    }
}
