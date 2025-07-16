<?php

declare(strict_types=1);

namespace App\Models;

use App\Platform\Enums\PlayerProgressResetType;
use App\Support\Database\Eloquent\BaseModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlayerProgressReset extends BaseModel
{
    protected $table = 'player_progress_resets';

    protected $fillable = [
        'user_id',
        'initiated_by_user_id',
        'type',
        'type_id',
    ];

    protected $casts = [
        'type' => PlayerProgressResetType::class,
    ];

    // == accessors

    // == mutators

    // == relations

    /**
     * Get the achievement if this is an achievement reset.
     *
     * @return BelongsTo<Achievement, PlayerProgressReset>
     */
    public function achievement(): BelongsTo
    {
        return $this->belongsTo(Achievement::class, 'type_id', 'ID');
    }

    /**
     * Get the achievement set if this is an achievement set reset.
     *
     * @return BelongsTo<AchievementSet, PlayerProgressReset>
     */
    public function achievementSet(): BelongsTo
    {
        return $this->belongsTo(AchievementSet::class, 'type_id');
    }

    /**
     * Get the game if this is a game reset.
     *
     * @return BelongsTo<Game, PlayerProgressReset>
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class, 'type_id', 'ID');
    }

    /**
     * Get the user who initiated this reset.
     *
     * @return BelongsTo<User, PlayerProgressReset>
     */
    public function initiatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'ID');
    }

    /**
     * @return BelongsTo<User, PlayerProgressReset>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'ID');
    }

    /**
     * Get the target model based on the reset type.
     */
    public function target(): Achievement|AchievementSet|Game|null
    {
        return match ($this->type) {
            PlayerProgressResetType::Account => null,
            PlayerProgressResetType::Achievement => $this->achievement,
            PlayerProgressResetType::AchievementSet => $this->achievementSet,
            PlayerProgressResetType::Game => $this->game,
        };
    }

    // == scopes

    /**
     * @param Builder<PlayerProgressReset> $query
     * @return Builder<PlayerProgressReset>
     */
    public function scopeForUserAndGame(Builder $query, User $user, Game $game): Builder
    {
        return $query->where('user_id', $user->id)
            ->where(function ($q) use ($game) {
                $q->where(function ($subQuery) use ($game) {
                    $subQuery->where('type', PlayerProgressResetType::Game)
                        ->where('type_id', $game->id);
                })
                ->orWhere('type', PlayerProgressResetType::Account);
            })
            ->orderByDesc('created_at');
    }
}
