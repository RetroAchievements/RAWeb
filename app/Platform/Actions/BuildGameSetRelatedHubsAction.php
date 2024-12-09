<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\GameSet;
use App\Models\System;
use App\Platform\Data\GameSetData;
use App\Platform\Enums\GameSetType;

class BuildGameSetRelatedHubsAction
{
    /**
     * @return GameSetData[]
     */
    public function execute(GameSet $gameSet): array
    {
        return GameSet::whereHas('children', function ($query) use ($gameSet) {
            $query->where('child_game_set_id', $gameSet->id);
        })
            ->whereType(GameSetType::Hub)
            ->select([
                'id',
                'title',
                'image_asset_path',
                'type',
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
            ->get()
            ->map(fn (GameSet $hub) => GameSetData::fromGameSetWithCounts($hub))
            ->values()
            ->all();
    }
}
