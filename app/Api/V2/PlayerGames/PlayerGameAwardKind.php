<?php

declare(strict_types=1);

namespace App\Api\V2\PlayerGames;

use App\Community\Enums\AwardType;
use App\Models\PlayerGame;
use App\Platform\Enums\UnlockMode;
use Illuminate\Database\Eloquent\Builder;

/**
 * Beaten milestones a player can reach on a game, matched on the denormalized
 * player_games timestamps rather than PlayerBadge award rows. The values
 * intentionally mirror the user-awards filter[kind] vocabulary.
 *
 * Completion and mastery are deliberately absent: games are beaten, but
 * achievement sets are completed or mastered. That filtering belongs on the
 * player-achievement-sets resource.
 */
enum PlayerGameAwardKind: string
{
    case BeatenCasual = 'beaten-casual';
    case BeatenHardcore = 'beaten-hardcore';

    /**
     * @param Builder<PlayerGame> $query
     * @return Builder<PlayerGame>
     */
    public function apply(Builder $query): Builder
    {
        return match ($this) {
            self::BeatenCasual => $this->applyBeaten($query, 'beaten_at', UnlockMode::Casual),
            self::BeatenHardcore => $this->applyBeaten($query, 'beaten_hardcore_at', UnlockMode::Hardcore),
        };
    }

    /**
     * @param Builder<PlayerGame> $query
     * @return Builder<PlayerGame>
     */
    private function applyBeaten(Builder $query, string $timestampColumn, int $unlockMode): Builder
    {
        return $query->where(function (Builder $query) use ($timestampColumn, $unlockMode) {
            $query
                ->whereNotNull($timestampColumn)
                ->orWhereHas('badges', function (Builder $query) use ($unlockMode) {
                    $query
                        ->whereColumn('user_awards.user_id', 'player_games.user_id')
                        ->where('award_type', AwardType::Mastery)
                        ->where('award_tier', $unlockMode);
                });
        });
    }
}
