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
        public Lazy|string $description,
        public Lazy|string|null $format,
        public Lazy|GameData $game,
        public int $id,
        public Lazy|int $orderColumn,
        public string $title,
        public Lazy|LeaderboardEntryData|null $topEntry,
        public Lazy|LeaderboardEntryData|null $userEntry = null,
        public Lazy|bool|null $rankAsc = null,
    ) {
    }

    public static function fromLeaderboard(Leaderboard $leaderboard, ?LeaderboardEntryData $userEntry = null): self
    {
        return new self(
            description: Lazy::create(fn () => $leaderboard->description),
            format: Lazy::create(fn () => $leaderboard->format),
            game: Lazy::create(fn () => GameData::fromGame($leaderboard->game)),
            id: $leaderboard->id,
            orderColumn: Lazy::create(fn () => $leaderboard->DisplayOrder),
            title: $leaderboard->title,
            topEntry: Lazy::create(fn () => $leaderboard->topEntry
                ? LeaderboardEntryData::fromLeaderboardEntry($leaderboard->topEntry, $leaderboard->format)
                : null
            ),
            userEntry: $userEntry,
            rankAsc: Lazy::create(fn () => $leaderboard->rank_asc),
        );
    }
}
