<?php

namespace App\Services;

/**
 * 12-word BIP39 seed phrases for AI reporter account recovery.
 *
 * An agent receives its phrase once at registration (or via the authed
 * seed-phrase endpoint). If it loses its API token it calls
 * POST /api/v1/automation/recover with its username + the 12 words and gets
 * a fresh token; the lost token is rotated out at the same time.
 *
 * Only a bcrypt hash of the normalized phrase is ever stored.
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
