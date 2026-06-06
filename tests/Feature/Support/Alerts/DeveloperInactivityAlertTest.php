<?php

declare(strict_types=1);

use App\Support\Alerts\DeveloperInactivityAlert;
use App\Support\Alerts\Jobs\SendAlertWebhookJob;
use Illuminate\Support\Facades\Queue;

it('formats a site inactivity discord message', function (): void {
    // Arrange
    $alert = new DeveloperInactivityAlert([
        [
            'displayName' => 'Scott',
            'finding' => [
                'reason' => 'overall_inactivity',
                'threshold' => '3-month',
                'lastActivityAt' => '2026-01-01 00:00:00',
            ],
        ],
    ]);

    // Act
    $message = $alert->toDiscordMessage();

    // Assert
    expect($message)->toContain('Developer inactivity alerts:')
        ->toContain('Scott')
        ->toContain(route('user.show', ['user' => 'Scott']))
        ->toContain('no site activity since Jan 1, 2026 (3-month threshold)');

    expect(str_contains($message, '2026-01-01 00:00:00'))->toBeFalse();
    expect(str_contains($message, 'last:'))->toBeFalse();
});

it('formats a tracked developer activity discord message', function (): void {
    // Arrange
    $alert = new DeveloperInactivityAlert([
        [
            'displayName' => 'Scott',
            'finding' => [
                'reason' => 'developer_inactivity',
                'threshold' => '6-month',
                'lastActivityAt' => '2025-11-01 00:00:00',
            ],
        ],
    ]);

    // Act
    $message = $alert->toDiscordMessage();

    // Assert
    expect($message)->toContain('last tracked developer activity was Nov 1, 2025 (6-month threshold)');
});

it('dispatches the alert webhook job when configured', function (): void {
    // Arrange
    Queue::fake();

    config(['services.discord.alerts_webhook.developer_inactivity' => 'https://discord.com/api/webhooks/test']);

    $alert = new DeveloperInactivityAlert([
        [
            'displayName' => 'Scott',
            'finding' => [
                'reason' => 'developer_inactivity',
                'threshold' => '6-month',
                'lastActivityAt' => null,
            ],
        ],
    ]);

    // Act
    $result = $alert->send();

    // Assert
    expect($result)->toBeTrue();
    Queue::assertPushedOn('alerts', SendAlertWebhookJob::class, function (SendAlertWebhookJob $job): bool {
        return $job->webhookUrl === 'https://discord.com/api/webhooks/test';
    });
});
