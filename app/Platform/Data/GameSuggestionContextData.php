<?php

declare(strict_types=1);

namespace App\Platform\Data;

use App\Data\UserData;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('GameSuggestionContext')]
class GameSuggestionContextData extends Data
{
    private function __construct(
        public ?GameData $relatedGame = null,
        public ?GameSetData $relatedGameSet = null,
        public ?UserData $relatedAuthor = null,
    ) {
    }

    public static function forSimilarGame(GameData $game): self
    {
        return new self(relatedGame: $game);
    }

    public static function forSharedHub(GameSetData $gameSet): self
    {
        return new self(relatedGameSet: $gameSet);
    }

    public static function forSharedAuthor(UserData $author): self
    {
        return new self(relatedAuthor: $author);
    }
}
