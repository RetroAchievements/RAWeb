<?php

declare(strict_types=1);

namespace Tests\Feature\Support\Alerts;

use App\Models\Game;
use App\Models\GameScreenshot;
use App\Models\System;
use App\Models\User;
use App\Platform\Enums\ScreenshotType;
use App\Support\Alerts\InappropriateGameScreenshotAlert;
use App\Support\Alerts\Jobs\SendAlertWebhookJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class InappropriateGameScreenshotAlertTest extends TestCase
{
    use RefreshDatabase;

    public function testToDiscordMessageFormatsCorrectly(): void
    {
        // Arrange
        $system = System::factory()->create();
        $game = Game::factory()->create(['title' => 'Sonic the Hedgehog', 'system_id' => $system->id]);
        $submitter = User::factory()->create(['username' => 'PlayerOne', 'display_name' => 'PlayerOne']);
        $reviewer = User::factory()->create(['username' => 'SomeMod', 'display_name' => 'SomeMod']);
        $screenshot = GameScreenshot::factory()->create([
            'game_id' => $game->id,
            'captured_by_user_id' => $submitter->id,
            'type' => ScreenshotType::Title,
            'rejection_notes' => 'Contains explicit content.',
        ]);

        $alert = new InappropriateGameScreenshotAlert(
            screenshot: $screenshot->load(['game', 'capturedBy']),
            reviewer: $reviewer,
        );

        // Act
        $message = $alert->toDiscordMessage();

        // Assert
        $this->assertStringContainsString('SomeMod', $message);
        $this->assertStringContainsString('PlayerOne', $message);
        $this->assertStringContainsString('title screenshot submission', $message);
        $this->assertStringContainsString('Sonic the Hedgehog', $message);
        $this->assertStringContainsString('inappropriate content', $message);
        $this->assertStringContainsString('Contains explicit content.', $message);
        $this->assertStringContainsString(route('user.show', ['user' => $reviewer]), $message);
        $this->assertStringContainsString(route('user.show', ['user' => $submitter]), $message);
        $this->assertStringContainsString(route('game.show', ['game' => $game]), $message);
    }

    public function testSendDispatchesJobWhenWebhookUrlExists(): void
    {
        // Arrange
        Queue::fake();

        config(['services.discord.alerts_webhook.inappropriate_game_screenshot' => 'https://discord.com/api/webhooks/test']);

        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);
        $submitter = User::factory()->create();
        $reviewer = User::factory()->create();
        $screenshot = GameScreenshot::factory()->create([
            'game_id' => $game->id,
            'captured_by_user_id' => $submitter->id,
        ]);

        $alert = new InappropriateGameScreenshotAlert(
            screenshot: $screenshot->load(['game', 'capturedBy']),
            reviewer: $reviewer,
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
