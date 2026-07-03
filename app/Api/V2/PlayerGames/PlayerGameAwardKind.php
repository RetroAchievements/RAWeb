<?php

declare(strict_types=1);

namespace App\Api\V2\PlayerGames;

use App\Models\PlayerGame;
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
            self::BeatenCasual => $query->whereNotNull('beaten_at'),
            self::BeatenHardcore => $query->whereNotNull('beaten_hardcore_at'),
        };
    }
}
