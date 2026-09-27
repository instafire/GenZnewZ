<?php

namespace Tests\Feature;

use App\Models\AIReporter;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;
use Tests\Traits\RefreshDatabaseWithPlugins;

/**
 * Adoption could not be measured: `last_login_at` was only written by the web
 * login form, but agents register and receive a token, so they never log in and
 * every one of them looked dormant. These tests pin the fix.
 */
class ReporterActivityTest extends TestCase
{
    use RefreshDatabaseWithPlugins;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_authenticated_api_calls_record_reporter_activity(): void
    {
        $reporter = $this->createReporter();
        $this->assertNull($reporter->last_login_at);

        $this->getJson('/api/v1/automation/categories', [
            'X-API-Token' => $reporter->api_token,
        ])->assertOk();

        $this->assertNotNull(
            $reporter->fresh()->last_login_at,
            'Using an API token must count as activity.'
        );
    }

    public function test_an_invalid_token_records_nothing_and_still_fails(): void
    {
        $reporter = $this->createReporter();

        $this->getJson('/api/v1/automation/categories', [
            'X-API-Token' => 'not-a-real-token',
        ])->assertStatus(401);

        $this->assertNull($reporter->fresh()->last_login_at);
    }

    /**
     * Without the throttle a busy reporter would cause a write on every request.
     */
    public function test_activity_is_throttled(): void
    {
        $reporter = $this->createReporter(['last_login_at' => now()->subMinute()]);

        $original = $reporter->last_login_at;

        $this->getJson('/api/v1/automation/categories', [
            'X-API-Token' => $reporter->api_token,
        ])->assertOk();

        $this->assertEquals(
            $original->toIso8601String(),
            $reporter->fresh()->last_login_at->toIso8601String(),
            'Activity inside the throttle window must not rewrite the timestamp.'
        );
    }

    public function test_activity_is_recorded_once_the_throttle_expires(): void
    {
        $reporter = $this->createReporter(['last_login_at' => now()->subMinutes(30)]);

        $this->getJson('/api/v1/automation/categories', [
            'X-API-Token' => $reporter->api_token,
        ])->assertOk();

        $this->assertTrue(
            $reporter->fresh()->last_login_at->gt(now()->subMinute()),
            'Stale activity must be refreshed.'
        );
    }

    public function test_the_adoption_report_runs_and_counts_the_funnel(): void
    {
        $this->createReporter(['username' => 'published_agent', 'posts_count' => 4]);
        $this->createReporter(['username' => 'silent_agent', 'posts_count' => 0]);

        $this->artisan('ai-reporters:adoption')
            ->expectsOutputToContain('AI reporter adoption')
            ->expectsOutputToContain('Ever published')
            ->assertExitCode(0);
    }

    public function test_the_adoption_report_handles_having_no_reporters(): void
    {
        $this->artisan('ai-reporters:adoption')->assertExitCode(0);
    }

    protected function createReporter(array $attributes = []): AIReporter
    {
        $username = $attributes['username'] ?? 'agent_' . uniqid();

        return AIReporter::create(array_merge([
            'name' => 'Test Agent',
            'username' => $username,
            'email' => $username . '@example.com',
            'password' => bcrypt('a-long-enough-password'),
            'api_token' => 'ai_' . \Illuminate\Support\Str::random(60),
            'status' => 'active',
            'is_verified' => true,
        ], $attributes));
    }
}
