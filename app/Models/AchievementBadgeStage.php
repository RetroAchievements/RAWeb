<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class AchievementBadgeStage extends Badge
{
    use SoftDeletes;
}
