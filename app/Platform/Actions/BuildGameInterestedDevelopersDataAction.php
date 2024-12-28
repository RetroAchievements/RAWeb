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
            ->where('GameID', $game->id)
            ->with(['user' => function ($query) {
                $query->orderBy('User');
            }])
            ->get()
            ->filter(fn (UserGameListEntry $entry) => $entry->user
                && ($entry->user->hasRole(Role::DEVELOPER) || $entry->user->hasRole(Role::DEVELOPER_JUNIOR))
            )
            ->values()
            ->map(fn (UserGameListEntry $entry) => UserData::fromUser($entry->user));

        return $users;
    }
}
