<?php

declare(strict_types=1);

namespace App\Models;

use App\Community\Enums\AwardType;
use App\Support\Database\Eloquent\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlaytestAward extends BaseModel
{
    protected $table = 'playtest_awards';

    protected $fillable = [
        'label',
        'image_asset_path',
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
            ->wherePivot('award_type', AwardType::Playtest)
            ->withPivot(['awarded_at'])
            ->using(PlayerBadge::class);
    }

    /**
     * @return HasMany<PlayerBadge, $this>
     */
    public function playerBadges(): HasMany
    {
        return $this->hasMany(PlayerBadge::class, 'award_key', 'id')
            ->where('award_type', AwardType::Playtest);
    }

    // == scopes
}
