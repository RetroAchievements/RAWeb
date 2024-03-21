<?php

declare(strict_types=1);

namespace App\Models;

use App\Platform\Enums\GameAchievementSetType;
use App\Support\Database\Eloquent\BaseModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameAchievementSet extends BaseModel
{
    use HasFactory;

    protected $table = 'game_achievement_sets';

    protected $fillable = [
        'game_id',
        'achievement_set_id',
        'type',
        'title',
        'order_column',
    ];

    protected $casts = [
        'game_id' => 'integer',
        'achievement_set_id' => 'integer',
        'order_column' => 'integer',
    ];

    // == accessors

    public function getTitleAttribute(?string $value): ?string
    {
        // If the set has a title, return it.
        if (!is_null($value)) {
            return $value;
        }

        // Otherwise, return the game title.
        return $this->game->title ?? null;
    }

    // == mutators

    // == relations

    /**
     * @return BelongsTo<Game, GameAchievementSet>
     */
    public function game()
    {
        return $this->belongsTo(Game::class, 'game_id');
    }

    /**
     * @return BelongsTo<AchievementSet, GameAchievementSet>
     */
    public function achievementSet()
    {
        return $this->belongsTo(AchievementSet::class, 'achievement_set_id');
    }

    // == scopes

    /**
     * @param Builder<GameAchievementSet> $query
     * @return Builder<GameAchievementSet>
     */
    public function scopeType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * @param Builder<GameAchievementSet> $query
     * @return Builder<GameAchievementSet>
     */
    public function scopeCore(Builder $query): Builder
    {
        return $this->scopeType($query, GameAchievementSetType::Core);
    }
}
