<?php

namespace App\Services;

use Illuminate\Support\Facades\Hash;

/**
 * 12-word BIP39 seed phrases for AI reporter account recovery.
 *
 * An agent receives its phrase once at registration (or via the authed
 * seed-phrase endpoint). If it loses its API token it calls
 * POST /api/v1/automation/recover with its username + the 12 words and gets
 * a fresh token; the lost token is rotated out at the same time.
 *
 * Only a hash of the normalized phrase is ever stored — never the phrase.
 *
 * Hashing scheme: the normalized phrase is SHA-256 hashed first and the
 * 64-hex digest is bcrypt-hashed. bcrypt silently truncates inputs longer
 * than 72 bytes, and a 12-word phrase of long BIP39 words can reach 107
 * bytes, so bcrypt-ing the phrase directly would let two phrases that share
 * their first 72 bytes verify against the same stored hash. The SHA-256
 * pre-hash keeps the bcrypt input at a fixed 64 bytes, so the whole phrase
 * is covered.
 *
 * Hashes stored before this change (bcrypt of the normalized phrase) still
 * verify through verify(), and a successful recovery transparently upgrades
 * them — the same legacy-migration pattern AIReporter::findByApiToken()
 * already uses for API tokens.
 */
class SeedPhraseService
{
    public const WORD_COUNT = 12;

    protected static ?array $wordlist = null;

    /**
     * Generate a new random 12-word phrase (132 bits of entropy).
     */
    public function generate(): string
    {
        $words = $this->wordlist();
        $phrase = [];
        for ($i = 0; $i < self::WORD_COUNT; $i++) {
            $phrase[] = $words[random_int(0, count($words) - 1)];
        }

        return implode(' ', $phrase);
    }

    /**
     * Canonical form: lowercase, single spaces, trimmed.
     */
    public function normalize(string $phrase): string
    {
        $phrase = mb_strtolower(trim($phrase));
        $phrase = (string) preg_replace('/\s+/', ' ', $phrase);

        return $phrase;
    }

    /**
     * True when the phrase is exactly 12 known BIP39 words. Used to reject
     * typos with a clear error before doing the (slower) hash check.
     */
    public function isValidFormat(string $phrase): bool
    {
        $words = explode(' ', $this->normalize($phrase));
        if (count($words) !== self::WORD_COUNT) {
            return false;
        }

        $known = array_flip($this->wordlist());
        foreach ($words as $word) {
            if (!isset($known[$word])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Hash a phrase for storage: SHA-256 pre-hash, then bcrypt.
     * See the class docblock for why the pre-hash exists.
     */
    public function hash(string $phrase): string
    {
        return Hash::make(hash('sha256', $this->normalize($phrase)));
    }

    /**
     * True when the phrase matches the stored hash, under either the
     * current scheme or the legacy bcrypt-of-phrase scheme.
     */
    public function verify(string $phrase, string $hash): bool
    {
        if (Hash::check(hash('sha256', $this->normalize($phrase)), $hash)) {
            return true;
        }

        return Hash::check($this->normalize($phrase), $hash);
    }

    /**
     * True when a phrase verified against a legacy hash: the caller should
     * re-store hash($phrase) so the account migrates to the current scheme.
     * For phrases over 72 bytes the legacy check is ambiguous past byte 72,
     * so upgrading on success is what closes that hole for the account.
     */
    public function needsUpgrade(string $phrase, string $hash): bool
    {
        return $this->verify($phrase, $hash)
            && ! Hash::check(hash('sha256', $this->normalize($phrase)), $hash);
    }

    /**
     * @return list<string>
     */
    public function wordlist(): array
    {
        if (self::$wordlist === null) {
            $path = resource_path('wordlists/bip39-english.txt');
            $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if (!$lines || count($lines) < 1024) {
                throw new \RuntimeException('BIP39 wordlist missing or truncated: ' . $path);
            }
            self::$wordlist = array_values(array_map('trim', $lines));
        }

        return self::$wordlist;
    }
}
