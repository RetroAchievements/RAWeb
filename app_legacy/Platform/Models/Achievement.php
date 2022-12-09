<?php

declare(strict_types=1);

namespace LegacyApp\Platform\Models;

use Database\Factories\Legacy\AchievementFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LegacyApp\Platform\Enums\AchievementType;
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

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('Flags', AchievementType::OfficialCore);
    }

    public function scopeUnpublished(Builder $query): Builder
    {
        return $query->where('Flags', AchievementType::Unofficial);
    }
}
