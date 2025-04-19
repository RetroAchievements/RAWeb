<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Models\PlayerGame;
use App\Models\System;
use App\Platform\Services\PlayerGameActivityService;
use Illuminate\Console\Command;

class UpdatePlayerEstimatedTimes extends Command
{
    protected $signature = 'ra:platform:player:update-estimated-times';

    protected $description = 'Updates estimated play times for player_games';

    public function handle(): void
    {
        $playerGames = PlayerGame::whereNull('time_taken');
        $count = $playerGames->count();

        $progressBar = $this->output->createProgressBar($count);
        $progressBar->start();

        $playerGames->with(['game.system', 'user'])
            ->chunkById(500, function ($playerGames) use ($progressBar) {
                /** @var PlayerGame $playerGame */
                foreach ($playerGames as $playerGame) {
                    if (System::isGameSystem($playerGame->game->system->id)) {
                        $activityService = new PlayerGameActivityService();
                        $activityService->initialize($playerGame->user, $playerGame->game);
                        $summary = $activityService->analyze($playerGame);

                        $playerGame->fill([
                            'playtime_total' => $summary['totalPlaytime'],
                            'time_taken' => $summary['achievementPlaytimeSoftcore'],
                            'time_taken_hardcore' => $summary['achievementPlaytimeHardcore'],
                            'time_to_beat' => $summary['beatPlaytimeSoftcore'],
                            'time_to_beat_hardcore' => $summary['beatPlaytimeHardcore'],
                            'time_to_complete' => $summary['completePlaytimeSoftcore'],
                            'time_to_complete_hardcore' => $summary['completePlaytimeHardcore'],
                            'playtime_estimated' => $summary['generatedSessionAdjustment'] != 0,
                        ]);
                        $playerGame->save();
                    }
                    $progressBar->advance();
                }
            });
    }
}
