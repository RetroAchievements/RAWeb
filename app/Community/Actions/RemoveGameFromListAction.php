<?php

namespace App\Community\Actions;

use App\Community\Enums\UserGameListType;
use App\Platform\Models\Game;
use App\Site\Models\User;

class RemoveGameFromListAction
{
    public function execute(User $user, Game $game, string $type): bool
    {
        if (!UserGameListType::isValid($type)) {
            return false;
        }

        return $user->gameList($type)->where('GameID', $game->ID)->delete() === 1;
    }
}
