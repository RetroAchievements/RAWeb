<?php

declare(strict_types=1);

namespace App\Notifications\Messages;

class DiscordWebhookMessage
{
    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, string>  $headers
     */
    public function __construct(
        public readonly array $payload,
        public readonly array $headers = ['Content-Type' => 'application/json'],
    ) {
    }
}
