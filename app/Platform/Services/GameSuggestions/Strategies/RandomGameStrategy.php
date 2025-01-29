<?php

declare(strict_types=1);

namespace App\Platform\Services\GameSuggestions\Strategies;

use App\Models\Game;
use App\Models\System;
use App\Platform\Data\GameSuggestionContextData;
use App\Platform\Enums\GameSuggestionReason;
use Illuminate\Support\Facades\DB;

class RandomGameStrategy implements GameSuggestionStrategy
{
    public function select(): ?Game
    {
        $connection = DB::connection()->getDriverName();

        // Our optimized query doesn't work for SQLite.
        $fastRandomId = match ($connection) {
            'sqlite' => $this->selectRandomGameSQLite(),
            default => $this->selectFastRandomGameMariaDB(),
        };

        // Fetch the full game model using the ID.
        return $fastRandomId ? Game::find($fastRandomId) : null;
    }

    public function reason(): GameSuggestionReason
    {
        return GameSuggestionReason::Random;
    }

    public function reasonContext(): ?GameSuggestionContextData
    {
        return null;
    }

    private function selectFastRandomGameMariaDB(): ?int
    {
        // ->inRandomOrder() is very slow.
        // Instead, we'll get the total count of eligible games
        // and randomly pick one of the values.

        $baseQuery = Game::whereHasPublishedAchievements()
            ->whereNotIn('ConsoleID', System::getNonGameSystems());

        $totalCount = $baseQuery->count();

        if ($totalCount === 0) {
            return null;
        }

        $randomOffset = random_int(0, $totalCount - 1);

        return $baseQuery->select('ID')
            ->skip($randomOffset)
            ->value('ID');
    }

    private function selectRandomGameSQLite(): ?int
    {
        // SQLite unfortunately does not support RAND().
        return Game::whereHasPublishedAchievements()
            ->select('ID')
            ->orderByRaw('RANDOM()')
            ->value('ID');
    }
}
