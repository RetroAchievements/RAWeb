<?php

declare(strict_types=1);

namespace App\Http\Actions;

use App\Models\User;
use App\Platform\Data\GameData;
use Carbon\Carbon;

class BuildUserCurrentGameDataAction
{
    public function execute(?User $user): ?GameData
    {
        if (!$user) {
            return null;
        }

        // Get the most recent player session (similar to user profile query).
        $recentSession = $user->playerSessions()
            ->with(['game', 'game.system'])
            ->orderByDesc('created_at')
            ->first();

        // Check if it's within the last 10 minutes.
        // We should do this filtering outside of the query. Filtering at the
        // query level for player_sessions is far too slow.
        if (
            !$recentSession
            || !$recentSession->game
            || $recentSession->updated_at < Carbon::now()->subMinutes(10)
        ) {
            return null;
        }

        return GameData::fromGame($recentSession->game)->include('badgeUrl');
    }
}
