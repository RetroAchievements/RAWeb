<?php

declare(strict_types=1);

namespace App\Platform\Data;

use App\Models\Game;
use Illuminate\Support\Carbon;
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
        public Lazy|int $achievementsPublished,
        public Lazy|int $pointsTotal,
        public Lazy|int $pointsWeighted,
        public Lazy|Carbon|null $releasedAt,
        public Lazy|string|null $releasedAtGranularity,
        public Lazy|int $playersTotal,
        public Lazy|Carbon $lastUpdated,
        public Lazy|int $numVisibleLeaderboards,
        public Lazy|int $numUnresolvedTickets,
    ) {
    }

    public static function fromGame(Game $game): self
    {
        return new self(
            id: $game->id,
            title: $game->title,
            badgeUrl: Lazy::create(fn () => $game->badge_url),
            forumTopicId: Lazy::create(fn () => $game->ForumTopicID),
            system: Lazy::create(fn () => SystemData::fromSystem($game->system)),
            achievementsPublished: Lazy::create(fn () => $game->achievements_published),
            pointsTotal: Lazy::create(fn () => $game->points_total),
            pointsWeighted: Lazy::create(fn () => $game->TotalTruePoints),
            releasedAt: Lazy::create(fn () => $game->released_at),
            releasedAtGranularity: Lazy::create(fn () => $game->released_at_granularity),
            playersTotal: Lazy::create(fn () => $game->players_total),
            lastUpdated: Lazy::create(fn () => $game->lastUpdated),
            numVisibleLeaderboards: Lazy::create(fn () => $game->num_visible_leaderboards ?? 0),
            numUnresolvedTickets: Lazy::create(fn () => $game->num_unresolved_tickets ?? 0),
        );
    }
}
