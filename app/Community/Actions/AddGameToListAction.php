<?php

declare(strict_types=1);

namespace App\Community\Actions;

use App\Community\Enums\UserGameListType;
use App\Models\Game;
use App\Models\User;
use App\Models\UserGameListEntry;
use Illuminate\Support\Facades\DB;

class AddGameToListAction
{
    public function execute(User $user, Game $game, UserGameListType $type): ?UserGameListEntry
    {
        if ($user->gameListEntries($type)->where('game_id', $game->id)->exists()) {
            return null;
        }

        if ($type === UserGameListType::AchievementSetRequest) {
            return DB::transaction(function () use ($user, $game, $type) {
                // Lock the user's set request entries to prevent concurrent modifications.
                // This lock will automatically be freed when the transaction completes.
                UserGameListEntry::where('user_id', $user->id)
                    ->where('type', UserGameListType::AchievementSetRequest)
                    ->lockForUpdate()
                    ->get();

                $userRequestInfo = getUserRequestsInformation($user);

                // If the user has no remaining set requests, bail.
                if ($userRequestInfo['remaining'] <= 0) {
                    return null;
                }

                $entry = new UserGameListEntry([
                    'user_id' => $user->id,
                    'type' => $type,
                    'game_id' => $game->id,
                ]);
                $user->gameListEntries($type)->save($entry);

                return $entry;
            });
        }

        $entry = new UserGameListEntry([
            'user_id' => $user->id,
            'type' => $type,
            'game_id' => $game->id,
        ]);

        $user->gameListEntries($type)->save($entry);

        return $entry;
    }
}
