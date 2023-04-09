<?php

namespace LegacyApp\Community\Actions;

use LegacyApp\Community\Enums\UserGameListType;
use LegacyApp\Community\Models\UserGameListEntry;
use LegacyApp\Platform\Models\Game;
use LegacyApp\Site\Models\User;

class AddGameToListAction
{
    public function execute(User &$user, Game &$game, string $type): bool
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
