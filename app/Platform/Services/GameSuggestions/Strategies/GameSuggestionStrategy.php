<?php

declare(strict_types=1);

namespace App\Platform\Services\GameSuggestions\Strategies;

use App\Models\Game;
use App\Platform\Data\GameSuggestionContextData;
use App\Platform\Enums\GameSuggestionReason;

interface GameSuggestionStrategy
{
    /**
     * Select a game using this strategy.
     */
    public function select(): ?Game;

    /**
     * Get the reason why this game was selected.
     */
    public function reason(): GameSuggestionReason;

    /**
     * Get additional context about why this game was selected.
     */
    public function reasonContext(): ?GameSuggestionContextData;
}
