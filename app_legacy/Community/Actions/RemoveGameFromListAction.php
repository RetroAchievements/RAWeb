<?php

namespace LegacyApp\Community\Actions;

use LegacyApp\Platform\Models\Game;
use LegacyApp\Site\Models\User;

class RemoveGameFromListAction
{
    public function execute(User &$user, Game &$game, int $type): bool
    {
        return ($user->gameList($type)->where('GameID', $game->ID)->delete() === 1);
    }
}