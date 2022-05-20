<?php

declare(strict_types=1);

namespace App\Platform\Models;

use App\Site\Models\User;
use App\Support\Database\Eloquent\BasePivot;
use Database\Factories\PlayerAchievementLegacyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlayerAchievementLegacy extends BasePivot
{
    use HasFactory;

    // TODO drop Awarded, migrate to PlayerAchievement model
    protected $table = 'Awarded';

    public const CREATED_AT = 'Date';
    public const UPDATED_AT = null;

    protected static function newFactory(): PlayerAchievementLegacyFactory
    {
        return PlayerAchievementLegacyFactory::new();
    }

    protected $fillable = [
        'User',
        'AchievementID',
        'Date',
        'HardcoreMode',
    ];

    protected $casts = [
        'Date' => 'datetime',
    ];

    // == accessors

    // == mutators

    // == relations

    /**
     * @return BelongsTo<Achievement, PlayerAchievementLegacy>
     */
    public function achievement(): BelongsTo
    {
        return $this->belongsTo(Achievement::class, 'AchievementID');
    }

    /**
     * @return BelongsTo<User, PlayerAchievementLegacy>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'User');
    }

    /**
     * @return BelongsTo<User, PlayerAchievementLegacy>
     */
    public function player(): BelongsTo
    {
        return $this->player();
    }

    // == scopes
}
