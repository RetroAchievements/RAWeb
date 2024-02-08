<?php

declare(strict_types=1);

namespace App\Community\Actions;

use App\Community\Enums\UserGameListType;
use App\Models\Game;
use App\Models\User;
use App\Models\UserGameListEntry;

class AddGameToListAction
{
    public function execute(User $user, Game $game, string $type): ?UserGameListEntry
    {
        if (!UserGameListType::isValid($type)) {
            return null;
        }

        if ($user->gameList($type)->where('GameID', $game->ID)->exists()) {
            return null;
        }

        if ($type === UserGameListType::AchievementSetRequest) {
            $requestInfo = UserGameListEntry::getUserSetRequestsInformation($user);

            $count = $user->gameList($type)->withoutAchievements()->count();
            if ($count >= $requestInfo['total']) {
                return null;
            }
        }

        $entry = new UserGameListEntry([
            'user_id' => $user->ID,
            'User' => $user->User,
            'type' => $type,
            'GameID' => $game->ID,
        ]);

        $user->gameList($type)->save($entry);

        return $entry;
    }
}
