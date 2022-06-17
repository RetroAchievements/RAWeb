<?php

declare(strict_types=1);

namespace App\Platform\Models;

use App\Community\Concerns\HasAchievementCommunityFeatures;
use App\Community\Contracts\HasComments;
use App\Site\Models\User;
use App\Support\Database\Eloquent\BaseModel;
use App\Support\Database\Eloquent\Concerns\PreventLazyLoading;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Scout\Searchable;

class Achievement extends BaseModel implements HasComments
{
    /*
     * Community Traits
     */
    use HasAchievementCommunityFeatures;
    /*
     * Shared Traits
     */
    use PreventLazyLoading;
    use HasFactory;
    use Searchable;
    use SoftDeletes;

    // published = core
    // const Core = 3; # published = core
    // const Unofficial = 5; # unpublished ... doesn't matter. anything that is not 3 is unpublished/unofficial
    public const PUBLISHED = 3;

    protected $fillable = [
        'title',
        'description',
    ];

    protected $with = [
        // 'media',
    ];

    protected array $allowedLazyRelations = [
    ];

    public function toSearchableArray(): array
    {
        return $this->only([
            'id',
            'title',
            'description',
        ]);
    }

    public function shouldBeSearchable(): bool
    {
        // TODO: return $this->isPublished();
        return true;
    }

    // == helpers

    public function unlockValidationHash(User $user, int $hardcore): string
    {
        return md5($this->id . $user->username . $hardcore . $this->id);
    }

    // == accessors

    public function getCanonicalUrlAttribute(): string
    {
        return route('achievement.show', [$this, $this->getSlugAttribute()]);
    }

    public function getPermalinkAttribute(): string
    {
        return route('achievement.show', $this);
    }

    public function getSlugAttribute(): string
    {
        return $this->title ? '-' . Str::slug($this->title) : '';
    }

    public function getBadgeUrlAttribute(): string
    {
        return $this->getBadgeUnlockedUrlAttribute();
    }

    public function getBadgeLockedUrlAttribute(): string
    {
        /**
         * TODO: read from media library
         */
        // $badge = 'Badge/' . $this->badge_name . '_lock.png';
        // if (!file_exists(public_path($badge))) {
        $badge = 'assets/images/achievement/badge-locked.png';
        // }
        return $badge;
    }

    public function getBadgeUnlockedUrlAttribute(): string
    {
        /**
         * TODO: read from media library
         */
        // $badge = 'Badge/' . $this->badge_name . '.png';
        // if (!file_exists(public_path($badge))) {
        $badge = 'assets/images/achievement/badge.png';
        // }
        return $badge;
    }

    // public function getTitleAttribute(): string
    // {
    //     return !empty(trim($this->attributes['Title'])) ? $this->attributes['Title'] : 'Untitled';
    // }

    // == mutators

    // == relations

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Site\Models\User::class);
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function playerAchievements(): HasMany
    {
        return $this->hasMany(\App\Platform\Models\PlayerAchievement::class);
    }

    // == scopes

    public function scopeWithUnlocksByUser(Builder $query, User $user): Builder
    {
        $query->leftJoin('player_achievements', function ($join) use ($user) {
            $join->on('player_achievements.achievement_id', '=', 'achievements.id');
            $join->where('player_achievements.user_id', '=', $user->id);
        });
        $query->addSelect('achievements.*');
        $query->addSelect('player_achievements.unlocked_at');
        $query->addSelect('player_achievements.unlocked_hardcore_at');
        $query->addSelect(DB::raw('player_achievements.id as player_achievement_id'));

        return $query;
    }
}
