<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Platform\Models\Game;
use App\Platform\Models\GameHash;
use App\Platform\Models\GameHashSet;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class LinkHashToGame
{
    public function execute(string $hash, Game $game, ?string $gameHashTitle = null): GameHash
    {
        /*
         * TODO: lowercase hashes only
         */
        // $hash = Str::lower($hash);

        /*
         * Check if game hash is already linked
         * TODO: load from hash-sets with compatible flag only -> linking a hash that already is assigned to
         * incompatible hash set on another game has to be moved
         */

        $game->load(['gameHashSets.hashes' => function (BelongsToMany $query) use ($hash) {
            $query->where('Hash', $hash);
        }]);
        $linkedHashes = $game->gameHashSets->pluck('hashes')->collapse()->unique();
        /** @var ?GameHash $linkedHash */
        $linkedHash = $linkedHashes->first();

        if ($linkedHash) {
            // hash is already linked to game via a game hash set, nothing to do
            return $linkedHash;
        }

        // assume compatible hash linking only for now
        $compatible = true;

        /** @var ?GameHashSet $gameHashSet */
        $gameHashSet = $game->gameHashSets()->compatible($compatible)->first();

        if (!$gameHashSet) {
            $game->gameHashSets()->save(
                new GameHashSet([
                    'compatible' => $compatible,
                ]),
            );
            $gameHashSet = $game->gameHashSets()->compatible()->first();
        }

        $gameHash = GameHash::firstOrCreate(['hash' => $hash, 'system_id' => $game->system_id], [
            'Name' => $gameHashTitle,
            'description' => $gameHashTitle,
        ]);
        $gameHashSet->hashes()->save($gameHash);

        return $gameHash;
    }
}
