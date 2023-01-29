<?php

declare(strict_types=1);

namespace LegacyApp\Platform\Models;

use Database\Factories\Legacy\AchievementFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LegacyApp\Platform\Enums\AchievementType;
use LegacyApp\Site\Models\User;
use LegacyApp\Support\Database\Eloquent\BaseModel;

class Achievement extends BaseModel
{
    use HasFactory;

    protected $table = 'Achievements';

    public const CREATED_AT = 'DateCreated';

    protected $dates = [
        'DateModified',
    ];

    protected static function newFactory(): AchievementFactory
    {
        return AchievementFactory::new();
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class, 'GameID');
    }

    public function players(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'Awarded', 'AchievementID', 'User')
            ->using(PlayerAchievement::class);
    }

    /**
     * Return unlocks separated by unlock mode; both softcore and hardcore in "raw" form
     */
    public function rawUnlocks(): HasMany
    {
        return $this->hasMany(PlayerAchievement::class, 'AchievementID');
    }

    /**
     * Merge softcore with hardcore entries if the unlock mode is not specified
     */
    public function unlocks(int $mode = null): HasMany
    {
        if ($mode !== null) {
            return $this->rawUnlocks()->where('HardcoreMode', $mode);
        }

        return $this->rawUnlocks()->selectRaw('AchievementID, User, MAX(Date) Date, MAX(HardcoreMode) HardcoreMode')
            ->groupBy(['AchievementID', 'User']);
    }

    public function scopeType(Builder $query, int $type): Builder
    {
        return $query->where('Flags', $type);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $this->scopeType($query, AchievementType::OfficialCore);
    }

    public function scopeUnpublished(Builder $query): Builder
    {
        return $this->scopeType($query, AchievementType::Unofficial);
    }
}
