<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Database\Eloquent\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaderboardEntry extends BaseModel
{
    // == accessors

    // == mutators

    // == relations

    /**
     * Relationship to the associated leaderboard.
     *
     * @return BelongsTo<Leaderboard, LeaderboardEntry>
     */
    public function leaderboard(): BelongsTo
    {
        return $this->belongsTo(Leaderboard::class, 'leaderboard_id');
    }

    /**
     * Relationship to the associated user.
     *
     * @return BelongsTo<User, LeaderboardEntry>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // == scopes

    /**
     * Scope to filter entries by leaderboard.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $leaderboardId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfLeaderboard($query, $leaderboardId)
    {
        return $query->where('leaderboard_id', $leaderboardId);
    }

     /**
     * Scope to apply ranking logic.
     *
     * @param Builder $query
     * @param string $orderDirection 'asc' or 'desc'
     * @return Builder
     */
    public function scopeWithRank(Builder $query, string $orderDirection): Builder {
        return $query->selectRaw("*, RANK() OVER (ORDER BY score $orderDirection) as rank");
    }
}
