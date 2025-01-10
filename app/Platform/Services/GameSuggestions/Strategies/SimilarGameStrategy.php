<?php

declare(strict_types=1);

namespace App\Platform\Services\GameSuggestions\Strategies;

use App\Models\Game;
use App\Models\GameSet;
use App\Platform\Data\GameData;
use App\Platform\Data\GameSuggestionContextData;
use App\Platform\Enums\GameSetType;
use App\Platform\Enums\GameSuggestionReason;

class SimilarGameStrategy implements GameSuggestionStrategy
{
    private ?Game $selectedGame = null;

    public function __construct(
        private readonly Game $sourceGame,
        private readonly bool $attachContext = true,
    ) {
    }

    public function select(): ?Game
    {
        // Get game set IDs for similar games that contain our source game.
        $gameSetIds = GameSet::whereType(GameSetType::SimilarGames)
            ->whereGameId($this->sourceGame->id)
            ->pluck('id');

        if ($gameSetIds->isEmpty()) {
            return null;
        }

        $this->selectedGame = Game::query()
            ->whereHas('gameSets', function ($query) use ($gameSetIds) {
                $query->whereIn('game_sets.id', $gameSetIds);
            })
            ->where('ID', '!=', $this->sourceGame->id)
            ->whereHasPublishedAchievements()
            ->inRandomOrder()
            ->first();

        return $this->selectedGame;
    }

    public function reason(): GameSuggestionReason
    {
        return GameSuggestionReason::SimilarGame;
    }

    public function reasonContext(): ?GameSuggestionContextData
    {
        if (!$this->attachContext || !$this->selectedGame) {
            return null;
        }

        return GameSuggestionContextData::forSimilarGame(
            GameData::from($this->sourceGame)
        );
    }
}
