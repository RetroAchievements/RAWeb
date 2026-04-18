<?php

declare(strict_types=1);

namespace App\Notifications\Channels;

use App\Notifications\Contracts\SendsDiscordWebhook;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class DiscordWebhookChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        $route = $notifiable->routeNotificationFor(self::class);
        if (!$route) {
            return;
        }

        if (!$notification instanceof SendsDiscordWebhook) {
            throw new RuntimeException(sprintf(
                '%s must implement %s to be delivered via %s.',
                $notification::class,
                SendsDiscordWebhook::class,
                self::class,
            ));
        }

        $message = $notification->toDiscordWebhook($notifiable);

        Http::withHeaders($message->headers)
            ->asJson()
            ->post($route, $message->payload)
            ->throw();
    }
}
