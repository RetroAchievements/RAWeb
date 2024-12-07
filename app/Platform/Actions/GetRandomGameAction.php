<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\Game;
use App\Models\User;
use App\Platform\Concerns\BuildsGameListQueries;
use App\Platform\Enums\GameListType;

class GetRandomGameAction
{
    use BuildsGameListQueries;

    public function execute(
        GameListType $listType,
        ?User $user = null,
        array $filters = [],
        ?int $targetSystemId = null,
    ): ?Game {
        // Build a common base query which can use the reusable filters.
        // This is the same base query that is used to build the game
        // list datatables.
        $query = $this->buildBaseQuery($listType, $user, $targetSystemId);

        // After building the base query, tack on whatever filters we need.
        $this->applyFilters($query, $filters, $user);

        // Return a random game on the list.
        return $query->inRandomOrder()->first();
    }
}
