<?php

declare(strict_types=1);

namespace App\Support\Alerts\Listeners;

use App\Models\PlayerGame;
use App\Platform\Events\PlayerGameBeaten;
use App\Support\Alerts\SuspiciousBeatTimeAlert;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Flag beaten games when players beat the game in
 * less than 5% of the median time.
 */
class TriggerSuspiciousBeatTimeAlert implements ShouldQueue
{
    public string $queue = 'alerts';

    public function handle(PlayerGameBeaten $event): void
    {
        if (!$event->hardcore) {
            return;
        }

        $playerGame = PlayerGame::query()
            ->where('user_id', $event->user->id)
            ->where('game_id', $event->game->id)
            ->first();

        if (!$playerGame) {
            return;
        }

        $game = $event->game;

        // Bail if we don't have a sufficient sample size.
        if ($game->times_beaten_hardcore < 20) {
            return;
        }

        $playerTime = $playerGame->time_to_beat_hardcore;
        $medianTime = $game->median_time_to_beat_hardcore;
        if (!$playerTime || !$medianTime) {
            return;
        }

        // Flag if the player beat the game in less than 5% of the median time.
        if ($playerTime >= $medianTime / 20) {
            return;
        }

        (new SuspiciousBeatTimeAlert(
            user: $event->user,
            game: $game,
            timeToBeatSeconds: $playerTime,
            medianTimeToBeatSeconds: $medianTime,
        ))->send();
    }
}
