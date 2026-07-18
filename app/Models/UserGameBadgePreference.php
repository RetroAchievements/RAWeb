<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Database\Eloquent\BaseModel;
use Database\Factories\UserGameBadgePreferenceFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserGameBadgePreference extends BaseModel
{
    /** @use HasFactory<UserGameBadgePreferenceFactory> */
    use HasFactory;

    protected $table = 'user_game_badge_preferences';

    protected $fillable = [
        'user_id',
        'game_id',
        'sha1',
    ];

    protected static function newFactory(): UserGameBadgePreferenceFactory
    {
        return UserGameBadgePreferenceFactory::new();
    }

    // == helpers

    /**
     * Raw-SQL fragments that make a profile-owned query prefer the user's chosen display badge
     * over the game's canonical icon. Shared by the badge wall and completion-progress queries so
     * the join + COALESCE never drifts between them. When $apply is false (every public API
     * surface, by default), the canonical icon is used unchanged.
     *
     * @param string $userColumn the profile owner's user_id column (e.g. "saw.user_id", "pg.user_id")
     * @param string $gameKeyColumn the game id column (e.g. "saw.award_key", "pg.game_id")
     * @return array{0: string, 1: string} [joinSql, imageIconExpression]
     */
    public static function imageIconJoin(bool $apply, string $userColumn, string $gameKeyColumn): array
    {
        if (!$apply) {
            return ['', 'gd.image_icon_asset_path'];
        }

        $join = "
            LEFT JOIN user_game_badge_preferences AS ugbp ON (ugbp.user_id = {$userColumn} AND ugbp.game_id = {$gameKeyColumn})
            LEFT JOIN game_badges AS gb ON (gb.game_id = ugbp.game_id AND gb.sha1 = ugbp.sha1 AND gb.deleted_at IS NULL)";

        return [$join, 'COALESCE(gb.image_asset_path, gd.image_icon_asset_path)'];
    }

    /**
     * Delete every user's preference that points at the given badge content for a game.
     * Called whenever a game_badges row is removed so no one keeps displaying a pulled badge.
     *
     * @param list<string> $sha1s
     */
    public static function pruneForBadges(int $gameId, array $sha1s): void
    {
        if (empty($sha1s)) {
            return;
        }

        static::query()
            ->where('game_id', $gameId)
            ->whereIn('sha1', $sha1s)
            ->delete();
    }

    /**
     * Prune preferences for a set of game_badges rows that are about to be deleted.
     *
     * @param Builder<GameBadge> $badges
     */
    public static function pruneForBadgeRows(Builder $badges): void
    {
        $badges->clone()
            ->get(['game_id', 'sha1'])
            ->groupBy('game_id')
            ->each(fn ($rows, $gameId) => static::pruneForBadges((int) $gameId, $rows->pluck('sha1')->all()));
    }

    // == accessors

    // == mutators

    // == relations

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Game, $this>
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    // == scopes
}
