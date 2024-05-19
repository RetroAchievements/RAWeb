<?php

declare(strict_types=1);

namespace App\Platform\Services;

use App\Community\Enums\UserGameListType;
use App\Models\Game;
use App\Models\UserGameListEntry;

class GameDevInterestPageService
{
    public function buildViewData(Game $game): array
    {
        $listUsers = UserGameListEntry::where('type', UserGameListType::Develop)
            ->where('GameID', $game->id)
            ->join('UserAccounts', 'UserAccounts.ID', '=', 'SetRequest.user_id')
            ->orderBy('UserAccounts.User')
            ->pluck('UserAccounts.User');

        return [
            'pageDescription' => "Developers interested in working on {$game->title}",
            'pageTitle' => "{$game->title} - Developer Interest",
            'users' => $listUsers,
        ];
    }
}
