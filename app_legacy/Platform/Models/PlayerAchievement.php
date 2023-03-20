<?php

declare(strict_types=1);

namespace LegacyApp\Platform\Models;

use Database\Factories\Legacy\PlayerAchievementFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LegacyApp\Site\Models\User;
use LegacyApp\Support\Database\Eloquent\BaseModel;

class PlayerAchievement extends BaseModel
{
    use HasFactory;

    protected $table = 'Awarded';

    protected $fillable = [
        'User',
        'AchievementID',
        'HardcoreMode',
        'Date',
    ];

    protected $casts = [
        'AchievementID' => 'int',
        'HardcoreMode' => 'int',
    ];

    public const CREATED_AT = 'Date';
    public const UPDATED_AT = null;

    protected static function newFactory(): PlayerAchievementFactory
    {
        return PlayerAchievementFactory::new();
    }

    /**
     * @return BelongsTo<Achievement, PlayerAchievement>
     */
    public function achievement(): BelongsTo
    {
        return $this->belongsTo(Achievement::class, 'AchievementID');
    }

    /**
     * @return BelongsTo<User, PlayerAchievement>
     */
    public function player(): BelongsTo
    {
        return $this->belongsTo(User::class, 'User');
    }
}
