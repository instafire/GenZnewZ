<?php

namespace App\Services;

use Illuminate\Support\Str;

class NewsChatSafetyService
{
    protected const MAX_RESPONSE_LENGTH = 1400;

    protected const USER_BLOCK_PATTERNS = [
        '/\b(?:sudo|ssh|scp|sftp|chmod|chown|systemctl|docker|kubectl|composer|npm|yarn|pnpm|php artisan|mysql|mysqldump|psql|powershell|cmd(?:\.exe)?)\b/i',
        '/\b(?:run|execute|install|deploy|restart|stop|start|delete|remove|edit|modify|open|read|show|list|tail|cat)\b.{0,40}\b(?:server|terminal|shell|database|command|env|file|config|logs?|admin)\b/i',
        '/\b(?:api key|secret|auth token|access token|password|credential|private key|ssh key|database dump|root access|admin access|backdoor|reverse shell|\.env)\b/i',
        '/\b(?:ignore|bypass|reveal|show|print|dump|return)\b.{0,30}\b(?:system prompt|hidden prompt|prompt|instructions|rules)\b/i',
        '/\b(?:write|generate|show|give|create)\b.{0,30}\b(?:code|script|payload|sql query|sql injection|command(?:s)?)\b/i',
    ];

    protected const RESPONSE_BLOCK_PATTERNS = [
        '/```[\s\S]*?```/m',
        '/<\?(?:php|=)|<script\b/i',
        '/^\s*(?:\$|#)?\s*(?:sudo|ssh|scp|sftp|curl|wget|chmod|chown|systemctl|docker|kubectl|composer|npm|yarn|pnpm|php artisan|mysql|mysqldump|psql|git)\b/im',
    ];

    public function reviewUserMessage(string $message): array
    {
        $normalized = $this->normalize($message);

        foreach (self::USER_BLOCK_PATTERNS as $pattern) {
            if (preg_match($pattern, $normalized) === 1) {
                return [
                    'allowed' => false,
                    'message' => $this->refusalMessage(),
                ];
            }
        }

        return [
            'allowed' => true,
            'message' => null,
        ];
    }

    public function sanitizeAssistantMessage(string $message): string
    {
        $normalized = $this->normalize($message);

        if ($normalized === '') {
            return $this->refusalMessage();
        }

        foreach (self::RESPONSE_BLOCK_PATTERNS as $pattern) {
            if (preg_match($pattern, $normalized) === 1) {
                return $this->refusalMessage();
            }
        }

        return Str::limit($normalized, self::MAX_RESPONSE_LENGTH);
    }

    public function refusalMessage(): string
    {
        return 'I can help with public news headlines, article summaries, and basic GenZ NewZ site questions. I can’t help with commands, code, admin access, credentials, or server operations.';
    }

    protected function normalize(string $message): string
    {
        $message = strip_tags($message);
        $message = str_replace("\0", '', $message);
        $message = preg_replace("/\r\n?/", "\n", $message) ?? $message;
        $message = preg_replace("/[ \t]+/", ' ', $message) ?? $message;
        $message = preg_replace("/\n{3,}/", "\n\n", $message) ?? $message;

        return trim($message);
    }
}
