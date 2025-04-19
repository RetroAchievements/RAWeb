<?php

declare(strict_types=1);

namespace App\Platform\Data;

use App\Community\Enums\ClaimType;
use App\Models\Game;
use App\Platform\Enums\ReleasedAtGranularity;
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
        public Lazy|string $developer,
        public Lazy|string $publisher,
        public Lazy|string $genre,
        public Lazy|string $badgeUrl,
        public Lazy|int $forumTopicId,
        public Lazy|SystemData $system,
        public Lazy|int $achievementsPublished,
        public Lazy|int $pointsTotal,
        public Lazy|int $pointsWeighted,
        public Lazy|Carbon|null $releasedAt,
        public Lazy|ReleasedAtGranularity|null $releasedAtGranularity,
        public Lazy|int $playersHardcore,
        public Lazy|int $playersTotal,
        public Lazy|Carbon $lastUpdated,
        public Lazy|int $numVisibleLeaderboards,
        public Lazy|int $numUnresolvedTickets,
        public Lazy|bool $hasActiveOrInReviewClaims,
        public Lazy|string $imageBoxArtUrl,
        public Lazy|string $imageIngameUrl,
        public Lazy|string $imageTitleUrl,
        public Lazy|bool $isSubsetGame,
        /** @var Lazy|array<GameClaimantData> */
        public Lazy|array $claimants,
        /** @var Lazy|array<GameAchievementSetData> */
        public Lazy|array $gameAchievementSets,
    ) {
    }

    public static function fromGame(Game $game): self
    {
        return new self(
            id: $game->id,
            title: $game->title,
            developer: Lazy::create(fn () => $game->Developer),
            publisher: Lazy::create(fn () => $game->Publisher),
            genre: Lazy::create(fn () => $game->Genre),
            achievementsPublished: Lazy::create(fn () => $game->achievements_published),
            badgeUrl: Lazy::create(fn () => $game->badge_url),
            forumTopicId: Lazy::create(fn () => $game->ForumTopicID),
            hasActiveOrInReviewClaims: Lazy::create(fn () => $game->has_active_or_in_review_claims ?? false),
            imageBoxArtUrl: Lazy::create(fn () => $game->image_box_art_url),
            imageIngameUrl: Lazy::create(fn () => $game->image_ingame_url),
            imageTitleUrl: Lazy::create(fn () => $game->image_title_url),
            isSubsetGame: Lazy::create(fn () => $game->is_subset_game),
            lastUpdated: Lazy::create(fn () => $game->lastUpdated),
            numUnresolvedTickets: Lazy::create(fn () => $game->num_unresolved_tickets ?? 0),
            numVisibleLeaderboards: Lazy::create(fn () => $game->num_visible_leaderboards ?? 0),
            playersHardcore: Lazy::create(fn () => $game->players_hardcore),
            playersTotal: Lazy::create(fn () => $game->players_total),
            pointsTotal: Lazy::create(fn () => $game->points_total),
            pointsWeighted: Lazy::create(fn () => $game->TotalTruePoints),
            releasedAt: Lazy::create(fn () => $game->released_at),
            releasedAtGranularity: Lazy::create(fn () => $game->released_at_granularity),
            system: Lazy::create(fn () => SystemData::fromSystem($game->system)),

            claimants: Lazy::create(fn () => $game->achievementSetClaims->map(
                fn ($claim) => GameClaimantData::fromUser(
                    $claim->user,
                    $claim->ClaimType === ClaimType::Primary ? 'primary' : 'collaboration'
                )
            )->all()),

            gameAchievementSets: Lazy::create(fn () => $game->gameAchievementSets->map(
                fn ($gameAchievementSet) => GameAchievementSetData::from($gameAchievementSet)
            )->all())
        );
    }
}
