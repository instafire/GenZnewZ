<?php

namespace Tests\Feature;

use App\Models\AIReporter;
use App\Services\SeedPhraseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Coverage for AI reporter seed-phrase recovery:
 * POST /api/v1/automation/recover (and the adjacent issue endpoint).
 *
 * Every phrase and token below is a fake test fixture — never a real
 * reporter's phrase or token. Recovery is rate-limited to 5 attempts per
 * hour per IP, so each call in a test uses its own TEST-NET IP unless the
 * test is specifically about the limiter.
 */
class SeedPhraseRecoveryTest extends TestCase
{
    use RefreshDatabase;

    private const BASE = '/api/v1/automation';

    private const PHRASE = 'abandon ability able about above absent absorb abstract absurd abuse access accident';

    private const WRONG_PHRASE = 'abandon ability able about above absent absorb abstract absurd abuse access zoo';

    private const UNKNOWN_WORD_PHRASE = 'abandon ability able about above absent absorb abstract absurd abuse access notaword';

    private SeedPhraseService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new SeedPhraseService();
    }

    public function test_recovery_with_correct_phrase_rotates_token(): void
    {
        [$reporter, $oldToken] = $this->makeReporter();

        $response = $this->recover([
            'username' => $reporter->username,
            'seed_phrase' => self::PHRASE,
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['id', 'username', 'api_token', 'recovered_at']]);

        $newToken = $response->json('data.api_token');

        $this->assertIsString($newToken);
        $this->assertNotSame($oldToken, $newToken);
        $this->assertSame(AIReporter::hashApiToken($newToken), $reporter->fresh()->api_token);
        $this->assertTrue(Hash::check($newToken, $reporter->fresh()->password));
        $this->assertNotNull($reporter->fresh()->last_login_at);

        // The old token is dead immediately, the new one works.
        $this->getJson(self::BASE.'/me', ['X-API-Token' => $oldToken])->assertUnauthorized();
        $this->getJson(self::BASE.'/me', ['X-API-Token' => $newToken])
            ->assertOk()
            ->assertJsonPath('data.username', $reporter->username);
    }

    public function test_recovery_by_email_works(): void
    {
        [$reporter] = $this->makeReporter();

        $this->recover([
            'username' => $reporter->email,
            'seed_phrase' => self::PHRASE,
        ])->assertOk()->assertJsonPath('success', true);
    }

    public function test_recovery_with_wrong_phrase_fails_and_keeps_old_token(): void
    {
        [$reporter, $oldToken] = $this->makeReporter();

        $response = $this->recover([
            'username' => $reporter->username,
            'seed_phrase' => self::WRONG_PHRASE,
        ]);

        $response->assertUnauthorized()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Recovery failed: unknown account or wrong seed phrase');

        $this->assertSame(AIReporter::hashApiToken($oldToken), $reporter->fresh()->api_token);
        $this->assertNotNull(AIReporter::findByApiToken($oldToken));
    }

    public function test_recovery_with_unknown_username_returns_same_message_as_wrong_phrase(): void
    {
        [$reporter] = $this->makeReporter();

        $unknown = $this->recover([
            'username' => 'no-such-reporter',
            'seed_phrase' => self::PHRASE,
        ]);

        $wrongPhrase = $this->recover([
            'username' => $reporter->username,
            'seed_phrase' => self::WRONG_PHRASE,
        ], '203.0.113.201');

        // No user enumeration: identical status and message either way.
        $this->assertSame($wrongPhrase->status(), $unknown->status());
        $this->assertSame($wrongPhrase->json('message'), $unknown->json('message'));
    }

    public function test_recovery_for_reporter_without_seed_phrase_fails(): void
    {
        [$reporter] = $this->makeReporter(null, ['seed_phrase_hash' => null]);

        $this->recover([
            'username' => $reporter->username,
            'seed_phrase' => self::PHRASE,
        ])->assertUnauthorized()
            ->assertJsonPath('message', 'Recovery failed: unknown account or wrong seed phrase');
    }

    public function test_recovery_for_inactive_reporter_fails(): void
    {
        [$reporter] = $this->makeReporter(null, ['status' => 'pending']);

        $this->recover([
            'username' => $reporter->username,
            'seed_phrase' => self::PHRASE,
        ])->assertUnauthorized()
            ->assertJsonPath('message', 'Recovery failed: unknown account or wrong seed phrase');
    }

    public function test_recovery_rejects_short_phrase(): void
    {
        [$reporter] = $this->makeReporter();

        $this->recover([
            'username' => $reporter->username,
            'seed_phrase' => 'abandon ability able about above absent absorb abstract absurd abuse access',
        ])->assertUnauthorized()
            ->assertJsonPath('message', 'Recovery failed: seed phrase must be exactly 12 words');
    }

    public function test_recovery_rejects_long_phrase(): void
    {
        [$reporter] = $this->makeReporter();

        $this->recover([
            'username' => $reporter->username,
            'seed_phrase' => self::PHRASE.' zoo',
        ])->assertUnauthorized()
            ->assertJsonPath('message', 'Recovery failed: seed phrase must be exactly 12 words');
    }

    public function test_recovery_rejects_unknown_word(): void
    {
        [$reporter] = $this->makeReporter();

        $this->recover([
            'username' => $reporter->username,
            'seed_phrase' => self::UNKNOWN_WORD_PHRASE,
        ])->assertUnauthorized()
            ->assertJsonPath('message', 'Recovery failed: seed phrase must be exactly 12 words');
    }

    public function test_recovery_validates_required_fields(): void
    {
        [$reporter] = $this->makeReporter();

        $this->recover(['seed_phrase' => self::PHRASE])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('username');

        $this->recover(['username' => $reporter->username], '203.0.113.202')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('seed_phrase');

        $this->recover([
            'username' => $reporter->username,
            'seed_phrase' => ['abandon', 'ability'],
        ], '203.0.113.203')->assertUnprocessable();
    }

    public function test_recovery_accepts_uppercase_and_extra_whitespace(): void
    {
        [$reporter] = $this->makeReporter();

        $this->recover([
            'username' => $reporter->username,
            'seed_phrase' => "  ABANDON ability  ABLE\nabout above absent absorb abstract absurd abuse access ACCIDENT ",
        ])->assertOk()->assertJsonPath('success', true);
    }

    /**
     * Regression test for the bcrypt 72-byte truncation hole: the fixture
     * phrase is 84 bytes and shares its first 72 bytes with the variant
     * below, so under the old bcrypt-of-phrase scheme the wrong phrase
     * recovered the account. The SHA-256 pre-hash closes it.
     */
    public function test_recovery_rejects_phrase_differing_only_in_last_word(): void
    {
        $this->assertGreaterThan(72, strlen($this->service->normalize(self::PHRASE)));
        $this->assertSame(
            substr($this->service->normalize(self::PHRASE), 0, 72),
            substr($this->service->normalize(self::WRONG_PHRASE), 0, 72)
        );

        [$reporter] = $this->makeReporter();

        $this->recover([
            'username' => $reporter->username,
            'seed_phrase' => self::WRONG_PHRASE,
        ])->assertUnauthorized()
            ->assertJsonPath('message', 'Recovery failed: unknown account or wrong seed phrase');
    }

    public function test_recovery_is_rate_limited_to_five_attempts_per_hour(): void
    {
        [$reporter] = $this->makeReporter();

        $ip = '203.0.113.99';

        for ($i = 0; $i < 5; $i++) {
            $this->recover([
                'username' => $reporter->username,
                'seed_phrase' => self::WRONG_PHRASE,
            ], $ip)->assertUnauthorized();
        }

        // The 6th attempt is throttled even with the correct phrase.
        $response = $this->recover([
            'username' => $reporter->username,
            'seed_phrase' => self::PHRASE,
        ], $ip);
        $response->assertStatus(429)
            ->assertJsonPath('success', false);
        $this->assertArrayNotHasKey('data', $response->json());
    }

    public function test_recovery_response_never_contains_seed_material(): void
    {
        [$reporter] = $this->makeReporter();

        $failed = $this->recover([
            'username' => $reporter->username,
            'seed_phrase' => self::WRONG_PHRASE,
        ]);

        $this->assertStringNotContainsString('abandon', $failed->getContent());
        $this->assertStringNotContainsString('accident', $failed->getContent());

        $succeeded = $this->recover([
            'username' => $reporter->username,
            'seed_phrase' => self::PHRASE,
        ], '203.0.113.204');

        $succeeded->assertOk();
        $this->assertArrayNotHasKey('seed_phrase', $succeeded->json('data'));
        $this->assertStringNotContainsString('abandon', $succeeded->getContent());
        $this->assertStringNotContainsString('accident', $succeeded->getContent());
    }

    public function test_legacy_hash_recovers_and_upgrades_stored_hash(): void
    {
        $legacyHash = Hash::make($this->service->normalize(self::PHRASE));
        [$reporter] = $this->makeReporter(null, ['seed_phrase_hash' => $legacyHash]);

        $this->recover([
            'username' => $reporter->username,
            'seed_phrase' => self::PHRASE,
        ])->assertOk()->assertJsonPath('success', true);

        // The stored hash migrated to the current scheme on success.
        $upgraded = $reporter->fresh()->seed_phrase_hash;
        $this->assertNotSame($legacyHash, $upgraded);
        $this->assertTrue(Hash::check(hash('sha256', $this->service->normalize(self::PHRASE)), $upgraded));
        $this->assertFalse($this->service->needsUpgrade(self::PHRASE, $upgraded));
    }

    public function test_issue_seed_phrase_rotates_phrase(): void
    {
        [$reporter, $token] = $this->makeReporter();

        $issued = $this->postJson(
            self::BASE.'/seed-phrase',
            [],
            ['X-API-Token' => $token]
        )->assertOk()->assertJsonPath('success', true);

        $newPhrase = $issued->json('data.seed_phrase');

        $this->assertIsString($newPhrase);
        $this->assertTrue($this->service->isValidFormat($newPhrase));
        $this->assertNotSame(self::PHRASE, $newPhrase);

        // The old phrase is dead; the new one recovers.
        $this->recover([
            'username' => $reporter->username,
            'seed_phrase' => self::PHRASE,
        ])->assertUnauthorized();

        $this->recover([
            'username' => $reporter->username,
            'seed_phrase' => $newPhrase,
        ], '203.0.113.205')->assertOk()->assertJsonPath('success', true);
    }

    public function test_issue_seed_phrase_requires_auth(): void
    {
        $this->postJson(self::BASE.'/seed-phrase', [])->assertUnauthorized();
    }

    // -----------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------

    /**
     * @return array{0: AIReporter, 1: string}
     */
    private function makeReporter(?string $phrase = null, array $overrides = []): array
    {
        $phrase ??= self::PHRASE;
        $token = 'ai_'.bin2hex(random_bytes(28));
        $suffix = Str::lower(Str::random(8));

        $reporter = AIReporter::create(array_merge([
            'name' => 'Seed Recovery Probe',
            'email' => "seed-probe-{$suffix}@example.test",
            'username' => "seed-probe-{$suffix}",
            'password' => Hash::make($token),
            'api_token' => AIReporter::hashApiToken($token),
            'seed_phrase_hash' => $this->service->hash($phrase),
            'model_name' => 'seed-probe',
            'status' => 'active',
            'is_verified' => true,
        ], $overrides));

        return [$reporter, $token];
    }

    private function recover(array $payload, ?string $ip = null): TestResponse
    {
        return $this->postJson(
            self::BASE.'/recover',
            $payload,
            ['CF-Connecting-IP' => $ip ?? $this->freshIp()]
        );
    }

    private function freshIp(): string
    {
        return '203.0.113.'.random_int(1, 254);
    }
}
