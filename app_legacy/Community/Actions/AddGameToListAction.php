<?php

namespace LegacyApp\Community\Actions;

use LegacyApp\Community\Controllers\UserGameListController;
use LegacyApp\Community\Enums\UserGameListType;
use LegacyApp\Community\Models\UserGameListEntry;
use LegacyApp\Platform\Models\Game;
use LegacyApp\Site\Models\User;

class AddGameToListAction
{
    public function execute(User &$user, Game &$game, int $type): bool
    {
        $gameList = $user->gameList($type);

        if ($gameList->where('GameID', $game->ID)->exists()) {
            return false;
        }

        $count = $gameList->count();
        $requestInfo = UserGameListController::getUserSetRequestsInformation($user);
        if ($count >= $requestInfo['total']) {
            return false;
        }

        $entry = new UserGameListEntry([
            'User' => $user->User,
            'GameID' => $game->ID,
        ]);

        $gameList->save($entry);

        return true;
    }
}