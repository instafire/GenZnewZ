<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Reloop\Exceptions\ReloopApiException;
use Reloop\Exceptions\ReloopValidationException;
use Reloop\Reloop;
use RuntimeException;

/**
 * Transactional email via the Reloop API (reloop/reloop-email SDK).
 *
 * NOTE: the SDK is vendored manually under vendor/reloop/reloop-email
 * (registered via composer.json autoload PSR-4 "Reloop\\") because this
 * site's root composer.json cannot resolve dependencies — its botble/*
 * plugin requires have no matching path repositories, so any
 * `composer require` fails before reaching the new package. If the root
 * composer setup is ever repaired, replace this with a normal
 * `composer require reloop/reloop-email` and drop the PSR-4 entry.
 */
class ReloopMailer
{
    private Reloop $client;

    public function __construct()
    {
        $key = config('services.reloop.key');

        if (empty($key)) {
            throw new RuntimeException('RELOOP_API_KEY is not set (see .env / config/services.php).');
        }

        $this->client = Reloop::client($key);
    }

    /**
     * Send a transactional email.
     *
     * @param  string|array  $to  One address or a list of addresses.
     * @return array{messageId: mixed, id: mixed}  Reloop message identifiers.
     *
     * @throws RuntimeException  On validation or API errors (also logged).
     */
    public function send(string $from, string|array $to, string $subject, ?string $text = null, ?string $html = null): array
    {
        $payload = array_filter([
            'from' => $from,
            'to' => $to,
            'subject' => $subject,
            'text' => $text,
            'html' => $html,
        ], fn ($value) => $value !== null);

        try {
            $result = $this->client->mail->send($payload);
        } catch (ReloopValidationException $e) {
            Log::error('Reloop validation error', ['field' => $e->field, 'error' => $e->getMessage()]);

            throw new RuntimeException("Reloop rejected the email (field: {$e->field}): {$e->getMessage()}", 0, $e);
        } catch (ReloopApiException $e) {
            Log::error('Reloop API error', ['status' => $e->status, 'error' => $e->getMessage()]);

            throw new RuntimeException("Reloop API error {$e->status}: {$e->getMessage()}", 0, $e);
        }

        return [
            'messageId' => $result['messageId'] ?? null,
            'id' => $result['id'] ?? null,
        ];
    }
}
