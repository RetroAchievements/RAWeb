<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\GameSet;
use App\Models\System;
use App\Platform\Data\GameSetData;
use App\Platform\Enums\GameSetType;
use Illuminate\Support\Facades\DB;

class BuildGameSetRelatedHubsAction
{
    /**
     * @return GameSetData[]
     */
    public function execute(GameSet $gameSet): array
    {
        // Get the parent hub IDs where this game set is used.
        $parentHubIds = DB::table('game_set_links as gsl')
            ->join('game_sets as gs', 'gs.id', '=', 'gsl.parent_game_set_id')
            ->where('gsl.child_game_set_id', $gameSet->id)
            ->where('gs.type', GameSetType::Hub)
            ->where('gs.deleted_at', null)
            ->select('gs.id')
            ->pluck('id');

        // Fetch all parent hubs with counts in a single query.
        $relatedHubs = GameSet::whereIn('id', $parentHubIds)
            ->select([
                'game_sets.id',
                'game_sets.title',
                'game_sets.image_asset_path',
                'game_sets.type',
                'game_sets.updated_at',
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
            ->orderBy('game_sets.title')
            ->get()
            ->map(fn (GameSet $hub) => GameSetData::fromGameSetWithCounts($hub))
            ->values()
            ->all();

        return $relatedHubs;
    }
}
