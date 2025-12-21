<?php

declare(strict_types=1);

namespace App\Support\Alerts;

use App\Support\Alerts\Jobs\SendAlertWebhookJob;
use Illuminate\Support\Str;

abstract class Alert
{
    /**
     * Get the alert type identifier, derived from class name.
     * "SuspiciousBeatTimeAlert" -> "suspicious_beat_time"
     *
     * @see config/services.php
     */
    public static function type(): string
    {
        $className = class_basename(static::class);

        return Str::snake(Str::beforeLast($className, 'Alert'));
    }

    /**
     * Get the Discord webhook URL for this alert type.
     */
    public static function webhookUrl(): ?string
    {
        return config('services.discord.alerts_webhook.' . static::type());
    }

    /**
     * Build the Discord message content.
     */
    abstract public function toDiscordMessage(): string;

    public function send(): bool
    {
        $webhookUrl = static::webhookUrl();
        if (!$webhookUrl) {
            return false;
        }

        dispatch(new SendAlertWebhookJob($this, $webhookUrl))
            ->onQueue('alerts');

        return true;
    }
}
