<?php

declare(strict_types=1);

namespace App\Platform\Data;

use App\Models\Leaderboard;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('Leaderboard')]
class LeaderboardData extends Data
{
    public function __construct(
        public int $id,
        public string $title,
        public Lazy|string $description,
        public Lazy|GameData $game,
    ) {
    }

    public static function fromLeaderboard(Leaderboard $leaderboard): self
    {
        return new self(
            id: $leaderboard->id,
            title: $leaderboard->title,
            description: Lazy::create(fn () => $leaderboard->description),
            game: Lazy::create(fn () => GameData::fromGame($leaderboard->game)),
        );
    }
}
