<?php

declare(strict_types=1);

namespace App\Notifications\Contracts;

use App\Notifications\Messages\DiscordWebhookMessage;

interface SendsDiscordWebhook
{
    public function toDiscordWebhook(?object $notifiable = null): DiscordWebhookMessage;
}
