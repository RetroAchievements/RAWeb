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

    public function unlocks(int $mode = null): HasMany
    {
        $hasMany = $this->hasMany(PlayerAchievement::class, 'AchievementID');

        if ($mode === null) {
            return $hasMany;
        }

        return $hasMany->where('HardcoreMode', $mode);
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
