<?php

declare(strict_types=1);

namespace LegacyApp\Platform\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LegacyApp\Support\Database\Eloquent\BaseModel;

class PlayerAchievement extends BaseModel
{
    protected $table = 'Awarded';

    public function achievement(): BelongsTo
    {
        return $this->belongsTo(Achievement::class, 'AchievementID');
    }
}
