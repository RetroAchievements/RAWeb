<?php

declare(strict_types=1);

namespace App\Models;

use App\Community\Enums\AwardType;
use App\Support\Database\Eloquent\BaseModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SiteAward extends BaseModel
{
    protected $table = 'site_awards';

    protected $fillable = [
        'award_type',
        'label',
        'image_asset_path',
    ];

    protected $casts = [
        'award_type' => AwardType::class,
    ];

    // == accessors

    public function getBadgeCountAttribute(): int
    {
        return $this->playerBadges()->count();
    }

    public function getBadgeUrlAttribute(): string
    {
        return media_asset($this->image_asset_path);
    }

    // == mutators

    // == relations

    /**
     * @return BelongsToMany<User, $this>
     */
    public function awardedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_awards', 'award_key', 'user_id')
            ->wherePivot('award_type', $this->award_type)
            ->withPivot(['awarded_at'])
            ->using(PlayerBadge::class);
    }

    /**
     * @return HasMany<PlayerBadge, $this>
     */
    public function playerBadges(): HasMany
    {
        return $this->hasMany(PlayerBadge::class, 'award_key', 'id')
            ->where('award_type', $this->award_type);
    }

    // == scopes

    /**
     * @param Builder<SiteAward> $query
     * @return Builder<SiteAward>
     */
    public function scopeAwardType(Builder $query, AwardType $awardType): Builder
    {
        return $query->where('award_type', $awardType->value);
    }
}
