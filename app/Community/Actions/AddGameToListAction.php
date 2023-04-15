<?php

namespace App\Community\Actions;

use App\Community\Enums\UserGameListType;
use App\Community\Models\UserGameListEntry;
use App\Platform\Models\Game;
use App\Site\Models\User;

class AddGameToListAction
{
    public function execute(User $user, Game $game, string $type): bool
    {
        if (!UserGameListType::isValid($type)) {
            return false;
        }

        if ($user->gameList($type)->where('GameID', $game->ID)->exists()) {
            return false;
        }

        if ($type === UserGameListType::SetRequest) {
            $requestInfo = UserGameListEntry::getUserSetRequestsInformation($user);

            $count = $user->gameList($type)->withoutAchievements()->count();
            if ($count >= $requestInfo['total']) {
                return false;
            }
        }

        $entry = new UserGameListEntry([
            'User' => $user->User,
            'GameID' => $game->ID,
        ]);

        $user->gameList($type)->save($entry);

        return true;
    }
}
