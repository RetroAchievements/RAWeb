<?php

declare(strict_types=1);

namespace App\Community\Actions;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use JsonException;

class VerifyTurnstileTokenAction
{
    private const SITEVERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    private const CONNECT_TIMEOUT_SECONDS = 2;
    private const TIMEOUT_SECONDS = 5;

    /**
     * @return bool true when the submission may proceed
     */
    public function execute(?string $token, ?string $remoteIp = null): bool
    {
        if (empty($token)) {
            return false;
        }

        try {
            $response = Http::asForm()
                ->connectTimeout(self::CONNECT_TIMEOUT_SECONDS)
                ->timeout(self::TIMEOUT_SECONDS)
                ->post(self::SITEVERIFY_URL, [
                    'secret' => config('services.cloudflare.turnstile_secret_key'),
                    'response' => $token,
                    'remoteip' => $remoteIp,
                ]);
        } catch (ConnectionException $exception) {
            return $this->allowBecauseUnverifiable('could not reach Cloudflare: ' . $exception->getMessage());
        }

        if ($response->failed()) {
            return $this->allowBecauseUnverifiable('Cloudflare returned HTTP ' . $response->status());
        }

        try {
            $body = $response->json();
        } catch (JsonException) {
            $body = null;
        }

        if (!is_array($body) || !array_key_exists('success', $body)) {
            return $this->allowBecauseUnverifiable('Cloudflare returned an unreadable body');
        }

        if ($body['success'] !== true) {
            return false;
        }

        return true;
    }

    private function allowBecauseUnverifiable(string $reason): bool
    {
        // We can only log the reason, not the user. We don't know who the user is.
        Log::warning("Turnstile verification skipped, allowing submission: {$reason}");

        return true;
    }
}
