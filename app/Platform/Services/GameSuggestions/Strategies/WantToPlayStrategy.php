<?php

declare(strict_types=1);

namespace App\Platform\Services\GameSuggestions\Strategies;

use App\Community\Enums\UserGameListType;
use App\Models\Game;
use App\Models\System;
use App\Models\User;
use App\Platform\Data\GameSuggestionContextData;
use App\Platform\Enums\GameSuggestionReason;

class WantToPlayStrategy implements GameSuggestionStrategy
{
    public function __construct(
        private readonly User $user
    ) {
    }

    public function select(): ?Game
    {
        return $this->user->gameListEntries()
            ->whereType(UserGameListType::Play)
            ->whereHas('game', function ($query) {
                $query->whereHasPublishedAchievements()
                    ->whereNotIn('ConsoleID', System::getNonGameSystems());
            })
            ->with('game')
            ->inRandomOrder()
            ->first()
            ?->game;
    }

    public function reason(): GameSuggestionReason
    {
        return GameSuggestionReason::WantToPlay;
    }

    public function reasonContext(): ?GameSuggestionContextData
    {
        return null;
    }
}
