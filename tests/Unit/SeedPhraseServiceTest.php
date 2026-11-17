<?php

namespace Tests\Unit;

use App\Services\SeedPhraseService;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Unit coverage for the 12-word BIP39 seed phrases used for AI reporter
 * account recovery (POST /api/v1/automation/recover).
 *
 * Every phrase below is a fake test fixture built from the public BIP39
 * English wordlist — never a real reporter's phrase.
 */
class SeedPhraseServiceTest extends TestCase
{
    private const PHRASE = 'abandon ability able about above absent absorb abstract absurd abuse access accident';

    private const LONG_PHRASE_A = 'accident accident accident accident accident accident accident accident accident accident accident zebra';

    private const LONG_PHRASE_B = 'accident accident accident accident accident accident accident accident accident accident accident zoo';

    private SeedPhraseService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new SeedPhraseService();
    }

    public function test_generate_returns_twelve_words_from_the_wordlist(): void
    {
        $phrase = $this->service->generate();

        $words = explode(' ', $phrase);

        $this->assertCount(12, $words);

        $wordlist = $this->service->wordlist();
        foreach ($words as $word) {
            $this->assertContains($word, $wordlist);
        }
    }

    public function test_generate_returns_a_different_phrase_each_time(): void
    {
        $this->assertNotSame($this->service->generate(), $this->service->generate());
    }

    public function test_normalize_lowercases_trims_and_collapses_whitespace(): void
    {
        $this->assertSame(
            self::PHRASE,
            $this->service->normalize("  Abandon\tABILITY  able\nabout   above absent absorb abstract absurd abuse access accident  ")
        );
    }

    public function test_normalize_empty_input_stays_empty(): void
    {
        $this->assertSame('', $this->service->normalize('   '));
    }

    public function test_is_valid_format_accepts_a_known_twelve_word_phrase(): void
    {
        $this->assertTrue($this->service->isValidFormat(self::PHRASE));
    }

    public function test_is_valid_format_accepts_messy_casing_and_spacing(): void
    {
        $this->assertTrue($this->service->isValidFormat('  ABANDON  ability   able about above absent absorb abstract absurd abuse access accident '));
    }

    public function test_is_valid_format_rejects_wrong_word_counts(): void
    {
        $eleven = 'abandon ability able about above absent absorb abstract absurd abuse access';
        $thirteen = self::PHRASE.' zoo';

        $this->assertFalse($this->service->isValidFormat($eleven));
        $this->assertFalse($this->service->isValidFormat($thirteen));
        $this->assertFalse($this->service->isValidFormat(''));
        $this->assertFalse($this->service->isValidFormat('   '));
    }

    public function test_is_valid_format_rejects_unknown_words(): void
    {
        $this->assertFalse($this->service->isValidFormat('abandon ability able about above absent absorb abstract absurd abuse access notaword'));
    }

    public function test_wordlist_has_2048_unique_lowercase_words(): void
    {
        $wordlist = $this->service->wordlist();

        $this->assertCount(2048, $wordlist);
        $this->assertCount(2048, array_unique($wordlist));
        foreach ($wordlist as $word) {
            $this->assertMatchesRegularExpression('/^[a-z]+$/', $word);
        }
    }

    public function test_wordlist_is_cached_between_calls(): void
    {
        $this->assertSame($this->service->wordlist(), $this->service->wordlist());
    }

    public function test_hash_does_not_store_the_phrase_itself(): void
    {
        $hash = $this->service->hash(self::PHRASE);

        $this->assertStringNotContainsString('abandon', $hash);
        $this->assertStringNotContainsString(self::PHRASE, $hash);
        $this->assertTrue($this->service->verify(self::PHRASE, $hash));
        $this->assertFalse($this->service->verify(self::LONG_PHRASE_A, $hash));
    }

    public function test_verify_accepts_legacy_bcrypt_hashes(): void
    {
        // Hashes stored before the SHA-256 pre-hash change were bcrypt of the
        // normalized phrase directly; they must keep verifying, so reporters
        // registered before the change (including live ones) can still recover.
        $legacyHash = Hash::make($this->service->normalize(self::PHRASE));

        $this->assertTrue($this->service->verify(self::PHRASE, $legacyHash));
        // A phrase differing before byte 72 is rejected even by a legacy hash.
        // A phrase differing only after byte 72 is NOT rejected by a legacy
        // hash — that is the truncation hole, and it is exactly what the
        // SHA-256 pre-hash closes (see the byte-72 test below).
        $this->assertFalse($this->service->verify('zoo ability able about above absent absorb abstract absurd abuse access accident', $legacyHash));
    }

    public function test_needs_upgrade_flags_legacy_hashes_only(): void
    {
        $legacyHash = Hash::make($this->service->normalize(self::PHRASE));
        $currentHash = $this->service->hash(self::PHRASE);

        $this->assertTrue($this->service->needsUpgrade(self::PHRASE, $legacyHash));
        $this->assertFalse($this->service->needsUpgrade(self::PHRASE, $currentHash));
    }

    /**
     * bcrypt silently truncates inputs longer than 72 bytes, so two phrases
     * that share their first 72 bytes must NOT verify against the same hash.
     * Both phrases below are 100+ bytes and identical for the first 72.
     * This is the regression test for the pre-hash fix: under the old
     * bcrypt-of-phrase scheme both phrases verified.
     */
    public function test_phrases_differing_only_after_byte_72_do_not_both_verify(): void
    {
        $this->assertGreaterThan(72, strlen($this->service->normalize(self::LONG_PHRASE_A)));
        $this->assertGreaterThan(72, strlen($this->service->normalize(self::LONG_PHRASE_B)));
        $this->assertSame(
            substr($this->service->normalize(self::LONG_PHRASE_A), 0, 72),
            substr($this->service->normalize(self::LONG_PHRASE_B), 0, 72)
        );

        $hash = $this->service->hash(self::LONG_PHRASE_A);

        $this->assertTrue($this->service->verify(self::LONG_PHRASE_A, $hash));
        $this->assertFalse($this->service->verify(self::LONG_PHRASE_B, $hash));
    }
}
