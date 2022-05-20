<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use Illuminate\Console\Command;

class UpdatePlayerGameMetrics extends Command
{
    protected $signature = 'ra:platform:player:update-game-metrics';
    protected $description = '';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle(): void
    {
        // TODO refactor

        // if (is_string($userId)) {
        //     $userId = (int) GetUserData($userId)['ID'];
        // }
        //
        // $now = date('Y-m-d H:i:s');
        //
        // // $gameMetrics =
        //
        // $unlocks = getUserUnlocksForGame($userId, $gameId);
        // $unlocksHardcore = getUserUnlocksForGameHardcore($userId, $gameId);
        //
        // return updateUserGame($userId, $gameId, [
        //     'Unlocks' => random_int(0, mt_getrandmax()),
        //     'UnlocksHardcore' => random_int(0, mt_getrandmax()),
        //     'CompletionPercentage' => random_int(1, 100),
        //     'CompletionPercentageHardcore' => random_int(1, 100),
        //     'Completed' => $now,
        //     'CompletedHardcore' => $now,
        //     'LastUnlock' => $now,
        //     'LastUnlockHardcore' => $now,
        //     'FirstUnlock' => $now,
        //     'FirstUnlockHardcore' => $now,
        //     'Started' => $now,
        //     'StartedHardcore' => $now,
        // ]);
    }
}
