<?php

declare(strict_types=1);

namespace App\Platform\Data;

use App\Platform\Enums\GameSuggestionReason;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('GameSuggestionEntry')]
class GameSuggestionEntryData extends GameListEntryData
{
    public function __construct(
        GameData $game,
        ?PlayerGameData $playerGame,
        ?bool $isInBacklog,
        public GameSuggestionReason $suggestionReason,
        public ?GameSuggestionContextData $suggestionContext = null,
    ) {
        parent::__construct($game, $playerGame, $isInBacklog);
    }
}
