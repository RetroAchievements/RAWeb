<?php

declare(strict_types=1);

namespace App\Platform\Services\GameSuggestions\Strategies;

use App\Models\Game;
use App\Models\PlayerGame;
use App\Models\System;
use App\Models\User;
use App\Platform\Data\GameData;
use App\Platform\Data\GameSuggestionContextData;
use App\Platform\Enums\GameSuggestionReason;
use Illuminate\Support\Collection;

class CommonPlayersStrategy implements GameSuggestionStrategy
{
    public function __construct(
        private readonly User $user,
        private readonly Game $sourceGame,
        private readonly bool $attachContext = true,
    ) {
    }

    public function select(): ?Game
    {
        // Get a pseudorandom sample of users who mastered the source game.
        // Note that this random sample will always be the same, we're sampling using a modulo operator.
        $masterUserIds = $this->getMasterUserIds();

        if ($masterUserIds->isEmpty()) {
            return null;
        }

        // Find most common games mastered by these users.
        $gameId = PlayerGame::select('game_id')
            ->whereIn('user_id', $masterUserIds)
            ->whereAllAchievementsUnlocked()
            ->where('achievements_total', '>', 0)
            ->where('game_id', '!=', $this->sourceGame->id)
            ->withTrashed()
            ->groupBy('game_id')
            ->orderByRaw('COUNT(*) DESC')
            ->limit(10)
            ->value('game_id');

        // If we found a game, make sure it's not from a non-game system before returning it.
        // Otherwise, return null.
        if ($gameId) {
            $game = Game::find($gameId);

            return $game && !in_array($game->ConsoleID, System::getNonGameSystems()) ? $game : null;
        }

        return null;
    }

    public function reason(): GameSuggestionReason
    {
        return GameSuggestionReason::CommonPlayers;
    }

    public function reasonContext(): ?GameSuggestionContextData
    {
        if (!$this->attachContext) {
            return null;
        }

        return GameSuggestionContextData::forCommonPlayersGame(
            GameData::from($this->sourceGame)->include('badgeUrl')
        );
    }

    /**
     * @return Collection<int, int>
     */
    private function getMasterUserIds(): Collection
    {
        // Use the optimized index hint for MariaDB.
        // This is unfortunately not supported by SQLite.
        return PlayerGame::where('game_id', $this->sourceGame->id)
            ->where('user_id', '!=', $this->user->id)
            ->whereAllAchievementsUnlocked()
            ->withTrashed()
            ->whereRaw('id % 100 < 5')
            ->limit(5)
            ->pluck('user_id');
    }
}
