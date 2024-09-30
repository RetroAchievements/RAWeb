<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Database\Eloquent\BaseModel;

class UserGameAchievementSetPreference extends BaseModel
{
    protected $table = 'user_game_achievement_set_preferences';

    protected $fillable = [
        'user_id',
        'game_achievement_set_id',
        'opted_in',
    ];

    protected $casts = [
        'opted_in' => 'boolean',
    ];

    // == accessors

    // == mutators

    // == relations

    // == scopes
}
