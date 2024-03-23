<?php

declare(strict_types=1);

namespace App\Community\Actions;

use App\Community\Enums\UserGameListType;
use App\Models\Game;
use App\Models\User;

class RemoveGameFromListAction
{
    public function execute(User $user, Game $game, string $type): bool
    {
        if (!UserGameListType::isValid($type)) {
            return false;
        }

        return $user->gameLists($type)->where('GameID', $game->ID)->delete() === 1;
    }
}
