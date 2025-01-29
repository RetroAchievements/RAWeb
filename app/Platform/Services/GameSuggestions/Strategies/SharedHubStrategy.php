<?php

declare(strict_types=1);

namespace App\Platform\Services\GameSuggestions\Strategies;

use App\Models\Game;
use App\Models\GameSet;
use App\Models\System;
use App\Platform\Data\GameData;
use App\Platform\Data\GameSetData;
use App\Platform\Data\GameSuggestionContextData;
use App\Platform\Enums\GameSetType;
use App\Platform\Enums\GameSuggestionReason;
use App\Platform\Services\GameSuggestions\Enums\SourceGameKind;
use Illuminate\Support\Facades\DB;

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
        $this->selectedHub = $this->getRandomHub();

        if (!$this->selectedHub) {
            return null;
        }

        // Then, get a random game from the hub that isn't our source game.
        $this->selectedGame = Game::query()
            ->whereNotIn('ConsoleID', System::getNonGameSystems())
            ->whereHas('gameSets', function ($query) {
                $query->whereGameSetId($this->selectedHub->id);
            })
            ->where('ID', '!=', $this->sourceGame->id)
            ->whereHasPublishedAchievements()
            ->inRandomOrder()
            ->first();

        return $this->selectedGame;
    }

    private function getRandomHub(): ?GameSet
    {
        $connection = DB::connection()->getDriverName();

        return match ($connection) {
            'sqlite' => $this->getRandomHubSQLite(),
            default => $this->getRandomHubMariaDB(),
        };
    }

    private function getRandomHubMariaDB(): ?GameSet
    {
        return GameSet::whereType(GameSetType::Hub)
            ->whereHas('games', function ($query) {
                $query->whereGameId($this->sourceGame->id); // needs our source game.
            })
            ->whereHas('games', function ($query) {
                $query->where('game_id', '!=', $this->sourceGame->id); // needs other games too.
            })
            /**
             * Exclude certain hubs via regex.
             * We don't want to recommend something just because it's in the Noncompliant Writing hub.
             */
            ->whereRaw("game_sets.title NOT REGEXP '^\\\\[(Meta[| -]|Misc\\\\.[ -])'")
            ->inRandomOrder()
            ->first();
    }

    private function getRandomHubSQLite(): ?GameSet
    {
        // For SQLite, we use LIKE with multiple patterns instead of REGEXP.
        return GameSet::whereType(GameSetType::Hub)
            ->whereHas('games', function ($query) {
                $query->whereGameId($this->sourceGame->id); // needs our source game.
            })
            ->whereHas('games', function ($query) {
                $query->where('game_id', '!=', $this->sourceGame->id); // needs other games too.
            })
            ->where(function ($query) {
                $query->where('title', 'NOT LIKE', '[Meta - %')
                    ->where('title', 'NOT LIKE', '[Meta|%')
                    ->where('title', 'NOT LIKE', '[Misc. - %');
            })
            ->inRandomOrder()
            ->first();
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
