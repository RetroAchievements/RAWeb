<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Database\Eloquent\BaseModel;
use Database\Factories\AchievementGroupFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AchievementGroup extends BaseModel
{
    /** @use HasFactory<AchievementGroupFactory> */
    use HasFactory;

    protected $table = 'achievement_groups';

    protected $fillable = [
        'achievement_set_id',
        'label',
        'order_column',
    ];

    protected static function newFactory(): AchievementGroupFactory
    {
        return AchievementGroupFactory::new();
    }

    // == accessors

    // == mutators

    // == relations

    /**
     * @return BelongsTo<AchievementSet, $this>
     */
    public function achievementSet(): BelongsTo
    {
        return $this->belongsTo(AchievementSet::class);
    }

    /**
     * @return BelongsToMany<Achievement, $this>
     */
    public function achievements(): BelongsToMany
    {
        return $this->belongsToMany(
            Achievement::class,
            'achievement_set_achievements',
            'achievement_group_id',
            'achievement_id',
            'id',
            'ID'
        )->withPivot('order_column')->withTimestamps();
    }

    // == scopes
}
