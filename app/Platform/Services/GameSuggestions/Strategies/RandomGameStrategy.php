<?php

declare(strict_types=1);

namespace App\Platform\Services\GameSuggestions\Strategies;

use App\Models\Game;
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
        // Optimize the random selection using a COUNT-based weight rather than
        // ORDER BY. This performs better than using RAND() alone since it helps
        // MariaDB optimize the random selection.
        return Game::whereHasPublishedAchievements()
            ->select('ID')
            ->orderByRaw('RAND() * (SELECT COUNT(*) FROM GameData WHERE achievements_published > 0)')
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
