<?php

declare(strict_types=1);

namespace Tests\Feature\Support\Alerts;

use App\Models\Achievement;
use App\Models\ConnectWarning;
use App\Models\Game;
use App\Models\Leaderboard;
use App\Models\System;
use App\Models\User;
use App\Support\Alerts\Jobs\SendAlertWebhookJob;
use App\Support\Alerts\SuspiciousConnectWarningAlert;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SuspiciousConnectWarningAlertTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function testToWrongClientDiscordMessageFormatsCorrectlyForAchievement(): void
    {
        // Arrange
        $system = System::factory()->create(['name' => 'Genesis/Mega Drive']);
        $user = User::factory()->create(['username' => 'Scott', 'display_name' => 'Scott']);
        $game = Game::factory()->create(['title' => 'Sonic the Hedgehog', 'system_id' => $system->id]);
        $achievement = Achievement::factory()->create(['game_id' => $game->id]);

        $validationHash = md5($achievement->id . $user->display_name . '1');
        $warning = new ConnectWarning([
            'method' => 'awardachievement',
            'username' => $user->display_name,
            'user_agent' => 'TestUserAgent/1.0',
            'validation_hash' => $validationHash,
            'related_type' => 'achievement',
            'related_id' => $achievement->id,
            'hardcore' => 1,
            'smells' => 'wrong_client',
        ]);
        $alert = new SuspiciousConnectWarningAlert($warning);

        // Act
        $message = $alert->toDiscordMessage();

        // Assert
        $this->assertStringContainsString('Scott', $message);
        $this->assertStringContainsString('Sonic the Hedgehog', $message);
        $this->assertStringContainsString('TestUserAgent', $message);
        $this->assertStringContainsString('Genesis/Mega Drive', $message);
        $this->assertStringContainsString('achievements', $message);
        $this->assertStringContainsString(route('user.show', ['user' => $user]), $message);
        $this->assertStringContainsString(route('game.show', ['game' => $game]), $message);
        $this->assertStringContainsString(route('user.game.activity.show', ['user' => $user, 'game' => $game]), $message);
    }

    public function testToWrongClientDiscordMessageFormatsCorrectlyForLeaderboard(): void
    {
        // Arrange
        $system = System::factory()->create(['name' => 'Genesis/Mega Drive']);
        $user = User::factory()->create(['username' => 'Scott', 'display_name' => 'Scott']);
        $game = Game::factory()->create(['title' => 'Sonic the Hedgehog', 'system_id' => $system->id]);
        $leaderboard = Leaderboard::factory()->create(['game_id' => $game->id]);

        $validationHash = md5($leaderboard->id . $user->display_name . '1234');
        $warning = new ConnectWarning([
            'method' => 'submitlbentry',
            'username' => $user->display_name,
            'user_agent' => 'TestUserAgent/1.0',
            'validation_hash' => $validationHash,
            'related_type' => 'leaderboard',
            'related_id' => $leaderboard->id,
            'extra' => 1234,
            'smells' => 'wrong_client',
        ]);
        $alert = new SuspiciousConnectWarningAlert($warning);

        // Act
        $message = $alert->toDiscordMessage();

        // Assert
        $this->assertStringContainsString('Scott', $message);
        $this->assertStringContainsString('Sonic the Hedgehog', $message);
        $this->assertStringContainsString('TestUserAgent', $message);
        $this->assertStringContainsString('Genesis/Mega Drive', $message);
        $this->assertStringContainsString('leaderboard entries', $message);
        $this->assertStringContainsString(route('user.show', ['user' => $user]), $message);
        $this->assertStringContainsString(route('game.show', ['game' => $game]), $message);
        $this->assertStringContainsString(route('user.game.activity.show', ['user' => $user, 'game' => $game]), $message);
    }

    public function testToRepeatedValidationDiscordMessageFormatsCorrectlyForAchievement(): void
    {
        // Arrange
        $system = System::factory()->create();
        $user = User::factory()->create(['username' => 'Scott', 'display_name' => 'Scott']);
        $game = Game::factory()->create(['title' => 'Sonic the Hedgehog', 'system_id' => $system->id]);
        $achievement = Achievement::factory()->create(['game_id' => $game->id]);

        $validationHash = md5($achievement->id . $user->display_name . '1');
        $warning = new ConnectWarning([
            'method' => 'awardachievement',
            'username' => $user->display_name,
            'user_agent' => 'TestUserAgent/1.0',
            'validation_hash' => $validationHash,
            'related_type' => 'achievement',
            'related_id' => $achievement->id,
            'hardcore' => 1,
            'smells' => 'repeated_validation',
        ]);
        $alert = new SuspiciousConnectWarningAlert($warning);

        // Act
        $message = $alert->toDiscordMessage();

        // Assert
        $this->assertStringContainsString('Scott', $message);
        $this->assertStringContainsString('same incorrect validation', $message);
        $this->assertStringContainsString(route('user.show', ['user' => $user]), $message);
        $this->assertStringContainsString(route('user.game.activity.show', ['user' => $user, 'game' => $game]), $message);
    }

    public function testSendDispatchesJobWhenWebhookUrlExists(): void
    {
        // Arrange
        Queue::fake();

        config(['services.discord.alerts_webhook.suspicious_connect_warning' => 'https://discord.com/api/webhooks/test']);

        $system = System::factory()->create();
        $user = User::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);
        $achievement = Achievement::factory()->create(['game_id' => $game->id]);

        $validationHash = md5($achievement->id . $user->display_name . '1');
        $warning = new ConnectWarning([
            'method' => 'awardachievement',
            'username' => $user->display_name,
            'user_agent' => 'TestUserAgent/1.0',
            'validation_hash' => $validationHash,
            'related_type' => 'achievement',
            'related_id' => $achievement->id,
            'hardcore' => 1,
            'smells' => 'wrong_client',
        ]);
        $alert = new SuspiciousConnectWarningAlert($warning);

        // Act
        $result = $alert->send();

        // Assert
        $this->assertTrue($result);

        Queue::assertPushedOn('alerts', SendAlertWebhookJob::class, function ($job) {
            return $job->webhookUrl === 'https://discord.com/api/webhooks/test';
        });
    }
}
