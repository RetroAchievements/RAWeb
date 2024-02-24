<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Database\Eloquent\BaseModel;
use Database\Factories\AchievementSetAchievementFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AchievementSetAchievement extends BaseModel
{
    use HasFactory;

    protected $table = 'achievement_set_achievements';

    protected $fillable = [
        'achievement_set_id',
        'achievement_id',
        'order_column',
    ];

    protected $casts = [
        'achievement_set_id' => 'integer',
        'achievement_id' => 'integer',
        'order_column' => 'integer',
    ];

    protected static function newFactory(): AchievementSetAchievementFactory
    {
        return AchievementSetAchievementFactory::new();
    }

    // == accessors

    // == mutators

    // == relations

    /**
     * @return BelongsTo<Achievement, AchievementSetAchievement>
     */
    public function achievement(): BelongsTo
    {
        return $this->belongsTo(Achievement::class, 'achievement_id');
    }

    // == scopes
}
