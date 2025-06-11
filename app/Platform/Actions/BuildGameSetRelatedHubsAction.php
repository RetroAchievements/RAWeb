<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\GameSet;
use App\Models\System;
use App\Models\User;
use App\Platform\Data\GameSetData;
use App\Platform\Enums\GameSetType;

class BuildGameSetRelatedHubsAction
{
    /**
     * @return GameSetData[]
     */
    public function execute(GameSet $gameSet, ?User $user = null): array
    {
        $relatedHubs = GameSet::whereHas('children', function ($query) use ($gameSet) {
            $query->where('child_game_set_id', $gameSet->id);
        })
            ->whereType(GameSetType::Hub)
            ->select([
                'id',
                'title',
                'image_asset_path',
                'type',
                'has_mature_content',
                'updated_at',
            ])
            ->withCount([
                'games' => function ($query) {
                    $query->whereNull('GameData.deleted_at')
                        ->where('GameData.ConsoleID', '!=', System::Hubs);
                },
                'parents as link_count' => function ($query) {
                    $query->whereNull('game_sets.deleted_at');
                },
            ])
            ->orderBy('title')
            ->get();

        /**
         * If the user doesn't have permission to view a related hub,
         * we should filter it out of the list.
         * @see GameSetPolicy.php
         */
        $visibleHubs = $relatedHubs->filter(function ($hub) use ($user) {
            // If the user is a guest, only show hubs without view restrictions.
            if (!$user) {
                return !$hub->has_view_role_requirement;
            }

            return $user->can('view', $hub);
        });

        return $visibleHubs
            ->map(fn (GameSet $hub) => GameSetData::fromGameSetWithCounts($hub))
            ->values()
            ->all();
    }
}
