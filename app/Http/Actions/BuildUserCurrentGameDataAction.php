<?php

declare(strict_types=1);

namespace App\Http\Actions;

use App\Models\Game;
use App\Models\User;
use App\Platform\Data\GameData;
use Carbon\Carbon;

class BuildUserCurrentGameDataAction
{
    /**
     * @return array{0: GameData, 1: int}|null
     */
    public function execute(?User $user): ?array
    {
        if (!$user) {
            return null;
        }

        if (
            !$user->rich_presence_game_id
            || !$user->rich_presence_updated_at
            || $user->rich_presence_updated_at < Carbon::now()->subMinutes(15)
        ) {
            return null;
        }

        /** @var ?Game $game */
        $game = Game::with('system')->find($user->rich_presence_game_id);
        if (!$game) {
            return null;
        }

        $minutesAgo = (int) $user->rich_presence_updated_at->diffInMinutes(Carbon::now());

        return [
            GameData::fromGame($game)->include('badgeUrl'),
            $minutesAgo,
        ];
    }
}
