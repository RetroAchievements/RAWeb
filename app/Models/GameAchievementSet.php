<?php

declare(strict_types=1);

namespace App\Models;

use App\Platform\Enums\AchievementSetType;
use App\Support\Database\Eloquent\BaseModel;
use Database\Factories\GameAchievementSetFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameAchievementSet extends BaseModel
{
    /** @use HasFactory<GameAchievementSetFactory> */
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
        'type' => AchievementSetType::class,
    ];

    protected static function newFactory(): GameAchievementSetFactory
    {
        return GameAchievementSetFactory::new();
    }

    // == accessors

    // == mutators

    // == relations

    /**
     * @return BelongsTo<AchievementSet, GameAchievementSet>
     */
    public function achievementSet(): BelongsTo
    {
        return $this->belongsTo(AchievementSet::class);
    }

    /**
     * @return BelongsTo<Game, GameAchievementSet>
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class, 'game_id', 'ID');
    }

    // == scopes

    /**
     * @param Builder<GameAchievementSet> $query
     * @return Builder<GameAchievementSet>
     */
    public function scopeType(Builder $query, AchievementSetType $type): Builder
    {
        return $query->where('type', $type->value);
    }

    /**
     * @param Builder<GameAchievementSet> $query
     * @return Builder<GameAchievementSet>
     */
    public function scopeCore(Builder $query): Builder
    {
        return $this->scopeType($query, AchievementSetType::Core);
    }
}
