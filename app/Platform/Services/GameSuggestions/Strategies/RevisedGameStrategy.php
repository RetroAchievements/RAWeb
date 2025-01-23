<?php

declare(strict_types=1);

namespace App\Platform\Services\GameSuggestions\Strategies;

use App\Community\Enums\AwardType;
use App\Models\Game;
use App\Models\System;
use App\Models\User;
use App\Platform\Data\GameSuggestionContextData;
use App\Platform\Enums\GameSuggestionReason;
use Illuminate\Database\Eloquent\Builder;

class RevisedGameStrategy implements GameSuggestionStrategy
{
    public function __construct(
        private readonly User $user,
    ) {
    }

    public function select(): ?Game
    {
        /**
         * Find a random game that:
         * - Has achievements published.
         * - The user has not completed.
         * - The user has a prior mastery award for.
         */
        return Game::query()
            ->whereNotIn('ConsoleID', System::getNonGameSystems())
            ->whereHas('playerGames', function (Builder $query) {
                $query->whereUserId($this->user->id)->whereNotAllAchievementsUnlocked();
            })
            ->whereHas('playerBadges', function ($query) {
                $query->whereUserId($this->user->id)->where('AwardType', AwardType::Mastery);
            })
            ->whereHasPublishedAchievements()
            ->inRandomOrder()
            ->first();
    }

    public function reason(): GameSuggestionReason
    {
        return GameSuggestionReason::Revised;
    }

    public function reasonContext(): ?GameSuggestionContextData
    {
        return null;
    }
}
