<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Database\Eloquent\BaseModel;
use Database\Factories\AchievementSetFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;

class AchievementSet extends BaseModel
{
    /** @use HasFactory<AchievementSetFactory> */
    use HasFactory;
    use SoftDeletes;
    // TODO use LogsActivity;

    protected $table = 'achievement_sets';

    protected $fillable = [
        'user_id',
        'players_total',
        'players_hardcore',
        'achievements_published',
        'achievements_unpublished',
        'points_total',
        'points_weighted',
        'created_at',
        'updated_at',
    ];

    protected static function newFactory(): AchievementSetFactory
    {
        return AchievementSetFactory::new();
    }

    // == accessors

    // == mutators

    // == relations

    /**
     * @return HasMany<GameAchievementSet>
     */
    public function gameAchievementSets(): HasMany
    {
        return $this->hasMany(GameAchievementSet::class);
    }

    /**
     * @return BelongsToMany<GameHash>
     */
    public function gameHashes(): BelongsToMany
    {
        return $this->belongsToMany(GameHash::class, 'achievement_set_game_hashes')
            ->withPivot('compatible')
            ->withTimestamps();
    }

    /**
     * @return BelongsToMany<Achievement>
     */
    public function achievements(): BelongsToMany
    {
        return $this->belongsToMany(Achievement::class, 'achievement_set_achievements', 'achievement_set_id', 'achievement_id', 'id', 'ID')
            ->withPivot('order_column')
            ->withTimestamps();
    }

    /**
     * @return BelongsToMany<Game>
     */
    public function games(): BelongsToMany
    {
        return $this->belongsToMany(Game::class, 'game_achievement_sets', 'achievement_set_id', 'game_id', 'ID');
    }

    /**
     * @return BelongsTo<User, AchievementSet>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'ID');
    }

    // == scopes
}
