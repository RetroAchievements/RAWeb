<?php

declare(strict_types=1);

namespace App\Platform\Data;

use App\Data\UserData;
use App\Platform\Services\GameSuggestions\Enums\SourceGameKind;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('GameSuggestionContext')]
class GameSuggestionContextData extends Data
{
    private function __construct(
        public ?GameData $relatedGame = null,
        public ?GameSetData $relatedGameSet = null,
        public ?SourceGameKind $sourceGameKind = null,
        public ?UserData $relatedAuthor = null,
    ) {
    }

    public static function forCommonPlayersGame(GameData $game): self
    {
        return new self(relatedGame: $game);
    }

    public static function forSimilarGame(GameData $game, ?SourceGameKind $sourceGameKind): self
    {
        return new self(
            relatedGame: $game,
            sourceGameKind: $sourceGameKind,
        );
    }

    public static function forSharedHub(
        GameSetData $gameSet,
        ?GameData $sourceGame,
        ?SourceGameKind $sourceGameKind,
    ): self {
        return new self(
            relatedGameSet: $gameSet,
            relatedGame: $sourceGame,
            sourceGameKind: $sourceGameKind,
        );
    }

    public static function forSharedAuthor(
        UserData $author,
        ?GameData $sourceGame,
        ?SourceGameKind $sourceGameKind,
    ): self {
        return new self(
            relatedAuthor: $author,
            relatedGame: $sourceGame,
            sourceGameKind: $sourceGameKind,
        );
    }
}
