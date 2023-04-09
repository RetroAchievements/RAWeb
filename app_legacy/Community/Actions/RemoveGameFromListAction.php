<?php

namespace LegacyApp\Community\Actions;

use LegacyApp\Community\Enums\UserGameListType;
use LegacyApp\Platform\Models\Game;
use LegacyApp\Site\Models\User;

class RemoveGameFromListAction
{
    public function execute(User &$user, Game &$game, string $type): bool
    {
        if (!UserGameListType::isValid($type)) {
            return false;
        }

        return $user->gameList($type)->where('GameID', $game->ID)->delete() === 1;
    }
}
