<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Database\Eloquent\BaseModel;
use Database\Factories\UserGameAchievementSetPreferenceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;

class UserGameAchievementSetPreference extends BaseModel
{
    /** @use HasFactory<UserGameAchievementSetPreferenceFactory> */
    use HasFactory;

    protected $table = 'user_game_achievement_set_preferences';

    protected $fillable = [
        'user_id',
        'game_achievement_set_id',
        'opted_in',
    ];

    protected $casts = [
        'opted_in' => 'boolean',
    ];

    protected static function newFactory(): UserGameAchievementSetPreferenceFactory
    {
        return UserGameAchievementSetPreferenceFactory::new();
    }

    // == accessors

    // == mutators

    // == relations

    /**
     * @return HasOne<GameAchievementSet>
     */
    public function gameAchievementSet(): HasOne
    {
        return $this->hasOne(GameAchievementSet::class);
    }

    /**
     * @return HasOne<User>
     */
    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'user_id', 'ID');
    }

    // == scopes
}
