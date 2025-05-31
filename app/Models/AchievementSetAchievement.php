<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Database\Eloquent\BasePivot;

class AchievementSetAchievement extends BasePivot
{
    protected $table = 'achievement_set_achievements';

    protected $fillable = [
        'achievement_set_id',
        'achievement_id',
        'order_column',
        'created_at',
        'updated_at',
    ];
}
