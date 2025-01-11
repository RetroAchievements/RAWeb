<?php

declare(strict_types=1);

namespace App\Platform\Services\GameSuggestions\Strategies;

use App\Models\Game;
use App\Models\GameSet;
use App\Platform\Data\GameData;
use App\Platform\Data\GameSetData;
use App\Platform\Data\GameSuggestionContextData;
use App\Platform\Enums\GameSetType;
use App\Platform\Enums\GameSuggestionReason;
use App\Platform\Services\GameSuggestions\Enums\SourceGameKind;

class SharedHubStrategy implements GameSuggestionStrategy
{
    private ?Game $selectedGame = null;
    private ?GameSet $selectedHub = null;

    public function __construct(
        private readonly Game $sourceGame,
        private readonly ?SourceGameKind $sourceGameKind = null,
        private readonly bool $attachSourceGame = false,
    ) {
    }

    public function select(): ?Game
    {
        // First, get a random hub that contains our source game.
        $this->selectedHub = GameSet::whereType(GameSetType::Hub)
            ->whereHas('games', function ($query) {
                $query->whereGameId($this->sourceGame->id); // needs our source game
            })
            ->whereHas('games', function ($query) {
                $query->where('game_id', '!=', $this->sourceGame->id); // needs other games too!
            })
            ->inRandomOrder()
            ->first();

        if (!$this->selectedHub) {
            return null;
        }

        // Then, get a random game from the hub that isn't our source game.
        $this->selectedGame = Game::query()
            ->whereHas('gameSets', function ($query) {
                $query->whereGameSetId($this->selectedHub->id);
            })
            ->where('ID', '!=', $this->sourceGame->id)
            ->whereHasPublishedAchievements()
            ->inRandomOrder()
            ->first();

        return $this->selectedGame;
    }

    public function reason(): GameSuggestionReason
    {
        return GameSuggestionReason::SharedHub;
    }

    public function reasonContext(): ?GameSuggestionContextData
    {
        if (!$this->selectedHub) {
            return null;
        }

        return GameSuggestionContextData::forSharedHub(
            GameSetData::fromGameSetWithCounts($this->selectedHub)->include('gameId'),
            sourceGame: $this->attachSourceGame
                ? GameData::fromGame($this->sourceGame)->include('badgeUrl')
                : null,
            sourceGameKind: $this->attachSourceGame ? $this->sourceGameKind : null,
        );
    }
}
