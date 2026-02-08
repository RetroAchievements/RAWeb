<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Community\Enums\UserGameListType;
use App\Data\UserData;
use App\Models\Game;
use App\Models\Role;
use App\Models\UserGameListEntry;
use Illuminate\Support\Collection;

class BuildGameInterestedDevelopersDataAction
{
    /**
     * @return Collection<int, UserData>
     */
    public function execute(Game $game): Collection
    {
        $users = UserGameListEntry::whereType(UserGameListType::Develop)
            ->where('game_id', $game->id)
            ->with(['user' => function ($query) {
                $query->orderBy('username');
            }])
            ->whereHas('user.roles', function ($query) {
                $query->whereIn('name', [Role::DEVELOPER, Role::DEVELOPER_JUNIOR]);
            })
            ->get()
            ->map(fn (UserGameListEntry $entry) => UserData::fromUser($entry->user));

        return $users;
    }
}
