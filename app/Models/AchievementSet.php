<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Database\Eloquent\BaseModel;
use Database\Factories\AchievementSetFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AchievementSet extends BaseModel
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'achievement_sets';

    protected $fillable = [
        'achievements_published',
        'achievements_unpublished',
        'players_hardcore',
        'players_total',
        'points_total',
        'points_weighted',
        'user_id',
    ];

    protected $casts = [
        'achievements_published' => 'integer',
        'achievements_unpublished' => 'integer',
        'players_hardcore' => 'integer',
        'players_total' => 'integer',
        'points_total' => 'integer',
        'points_weighted' => 'integer',
        'user_id' => 'integer',
    ];

    protected static function newFactory(): AchievementSetFactory
    {
        return AchievementSetFactory::new();
    }

    // == accessors

    // == mutators

    // == relations

    /**
     * @return BelongsTo<User, AchievementSet>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return HasMany<AchievementSetAchievement>
     */
    public function achievementSetAchievements(): HasMany
    {
        return $this->hasMany(AchievementSetAchievement::class, 'achievement_set_id');
    }

    // == scopes
}
