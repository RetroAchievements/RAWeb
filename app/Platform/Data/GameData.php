<?php

declare(strict_types=1);

namespace App\Platform\Data;

use App\Models\Game;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('Game')]
class GameData extends Data
{
    public function __construct(
        public int $id,
        public string $title,
        public Lazy|string $badgeUrl,
        public Lazy|int $forumTopicId,
        public Lazy|SystemData $system,
    ) {
    }

    public static function fromGame(Game $game): self
    {
        return new self(
            id: $game->id,
            title: $game->title,
            badgeUrl: Lazy::create(fn () => $game->badge_url),
            forumTopicId: Lazy::create(fn () => $game->ForumTopicID),
            system: Lazy::create(fn () => SystemData::fromSystem($game->system))
        );
    }
}
