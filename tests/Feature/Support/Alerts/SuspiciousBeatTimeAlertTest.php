<?php

declare(strict_types=1);

namespace Tests\Feature\Support\Alerts;

use App\Models\Game;
use App\Models\System;
use App\Models\User;
use App\Support\Alerts\Jobs\SendAlertWebhookJob;
use App\Support\Alerts\SuspiciousBeatTimeAlert;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SuspiciousBeatTimeAlertTest extends TestCase
{
    use RefreshDatabase;

    public function testToDiscordMessageFormatsCorrectly(): void
    {
        // Arrange
        $system = System::factory()->create();
        $user = User::factory()->create(['username' => 'Scott', 'display_name' => 'Scott']);
        $game = Game::factory()->create(['title' => 'Sonic the Hedgehog', 'system_id' => $system->id]);

        $alert = new SuspiciousBeatTimeAlert(
            user: $user,
            game: $game,
            timeToBeatSeconds: 125, // 2m 5s
            medianTimeToBeatSeconds: 3600, // 1 hour
        );

        // Act
        $message = $alert->toDiscordMessage();

        // Assert
        $this->assertStringContainsString('Scott', $message);
        $this->assertStringContainsString('Sonic the Hedgehog', $message);
        $this->assertStringContainsString('2m 5s', $message);
        $this->assertStringContainsString('3.5%', $message);
        $this->assertStringContainsString('1h', $message);
        $this->assertStringContainsString(route('user.show', ['user' => $user]), $message);
        $this->assertStringContainsString(route('game.show', ['game' => $game]), $message);
    }

    public function testSendDispatchesJobWhenWebhookUrlExists(): void
    {
        // Arrange
        Queue::fake();

        config(['services.discord.alerts_webhook.suspicious_beat_time' => 'https://discord.com/api/webhooks/test']);

        $system = System::factory()->create();
        $user = User::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);

        $alert = new SuspiciousBeatTimeAlert(
            user: $user,
            game: $game,
            timeToBeatSeconds: 60,
            medianTimeToBeatSeconds: 3600,
        );

        // Act
        $result = $alert->send();

        // Assert
        $this->assertTrue($result);

        Queue::assertPushedOn('alerts', SendAlertWebhookJob::class, function ($job) {
            return $job->webhookUrl === 'https://discord.com/api/webhooks/test';
        });
    }
}
