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
     * The username to use for the Discord webhook.
     * If null, the webhook will use whatever is configured on Discord.
     */
    public static function webhookUsername(): ?string
    {
        return null;
    }

    /**
     * The avatar to use for the Discord webhook.
     * If null, the webhook will use whatever is configured on Discord.
     */
    public static function webhookAvatarUrl(): ?string
    {
        return null;
    }

    /**
     * Build the Discord message content.
     */
    abstract public function toDiscordMessage(): string;

    /**
     * A single emoji identifying this alert kind, so a busy feed can be
     * scanned by kind at a glance. Return null to send no prefix.
     */
    public function emoji(): ?string
    {
        return null;
    }

    public function toWebhookContent(): string
    {
        $emoji = $this->emoji();
        $message = $this->toDiscordMessage();

        return $emoji ? sprintf('%s · %s', $emoji, $message) : $message;
    }

    public function send(): bool
    {
        $webhookUrl = static::webhookUrl();
        if (!$webhookUrl) {
            return false;
        }

        $webhookUsername = static::webhookUsername();
        $webhookAvatarUrl = static::webhookAvatarUrl();

        dispatch(new SendAlertWebhookJob($this, $webhookUrl, $webhookUsername, $webhookAvatarUrl))
            ->onQueue('alerts');

        return true;
    }
}
