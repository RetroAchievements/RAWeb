<?php

declare(strict_types=1);

namespace Tests\Feature\Support\Alerts\Listeners;

use App\Models\Game;
use App\Models\PlayerGame;
use App\Models\System;
use App\Models\User;
use App\Platform\Events\PlayerGameBeaten;
use App\Support\Alerts\Jobs\SendAlertWebhookJob;
use App\Support\Alerts\Listeners\TriggerSuspiciousBeatTimeAlert;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class TriggerSuspiciousBeatTimeAlertTest extends TestCase
{
    use RefreshDatabase;

    public function testItTriggersAlertWhenTimeBelowThreshold(): void
    {
        // Arrange
        Queue::fake();

        config(['services.discord.alerts_webhook.suspicious_beat_time' => 'https://discord.com/api/webhooks/test']);

        $system = System::factory()->create();
        $user = User::factory()->create();
        $game = Game::factory()->create([
            'system_id' => $system->id,
            'times_beaten_hardcore' => 100,
            'median_time_to_beat_hardcore' => 3600, // 1 hour
        ]);

        // ... the player beat the game in 60 seconds, which is ~1.5% of the median ...
        PlayerGame::factory()->create([
            'user_id' => $user->id,
            'game_id' => $game->id,
            'time_to_beat_hardcore' => 60,
        ]);

        $event = new PlayerGameBeaten($user, $game, hardcore: true);

        // Act
        (new TriggerSuspiciousBeatTimeAlert())->handle($event);

        // Assert
        Queue::assertPushedOn('alerts', SendAlertWebhookJob::class);
    }

    public function testItDoesNotTriggerWhenTimeIsAboveThreshold(): void
    {
        // Arrange
        Queue::fake();

        config(['services.discord.alerts_webhook.suspicious_beat_time' => 'https://discord.com/api/webhooks/test']);

        $system = System::factory()->create();
        $user = User::factory()->create();
        $game = Game::factory()->create([
            'system_id' => $system->id,
            'times_beaten_hardcore' => 100,
            'median_time_to_beat_hardcore' => 3600, // 1 hour
        ]);

        // ... the player beat the game in 5 minutes, which is ~8% of the median (and above our 5% threshold) ...
        PlayerGame::factory()->create([
            'user_id' => $user->id,
            'game_id' => $game->id,
            'time_to_beat_hardcore' => 300, // 5 minutes
        ]);

        $event = new PlayerGameBeaten($user, $game, hardcore: true);

        // Act
        (new TriggerSuspiciousBeatTimeAlert())->handle($event);

        // Assert
        Queue::assertNothingPushed();
    }

    public function testItDoesNotTriggerForFinalFantasyXIGames(): void
    {
        // Arrange
        Queue::fake();

        config(['services.discord.alerts_webhook.suspicious_beat_time' => 'https://discord.com/api/webhooks/test']);

        $system = System::factory()->create();
        $user = User::factory()->create();
        $game = Game::factory()->create([
            'title' => 'Final Fantasy XI: Rise of the Zilart',
            'system_id' => $system->id,
            'times_beaten_hardcore' => 100,
            'median_time_to_beat_hardcore' => 3600,
        ]);

        PlayerGame::factory()->create([
            'user_id' => $user->id,
            'game_id' => $game->id,
            'time_to_beat_hardcore' => 60, // would normally trigger an alert
        ]);

        $event = new PlayerGameBeaten($user, $game, hardcore: true);

        // Act
        (new TriggerSuspiciousBeatTimeAlert())->handle($event);

        // Assert
        Queue::assertNothingPushed();
    }
}
