<?php

declare(strict_types=1);

namespace App\Platform\Data;

use App\Platform\Enums\GameSuggestionReason;
use Spatie\LaravelData\Data;

class GameSuggestionData extends Data
{
    public function __construct(
        public int $gameId,
        public GameSuggestionReason $reason,
        public ?GameSuggestionContextData $context,
    ) {
    }
}
