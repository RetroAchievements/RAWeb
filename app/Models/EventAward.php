<?php

declare(strict_types=1);

namespace App\Models;

use App\Community\Enums\AwardType;
use App\Support\Database\Eloquent\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EventAward extends BaseModel
{
    protected $table = 'event_awards';

    protected $fillable = [
        'event_id',
        'tier_index',
        'label',
        'points_required',
        'image_asset_path',
    ];

    // == accessors

    public function getBadgeCountAttribute(): int
    {
        return PlayerBadge::query()
            ->where('AwardType', AwardType::Event)
            ->where('AwardData', $this->event_id)
            ->where('AwardDataExtra', $this->tier_index)
            ->whereHas('user', function ($query) {
                $query->tracked();
            })
            ->count();
    }

    public function getBadgeUrlAttribute(): string
    {
        return media_asset($this->image_asset_path);
    }

    // == mutators

    // == relations

    /**
     * @return BelongsToMany<User>
     */
    public function awardedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'SiteAwards', 'id', 'user_id')
            ->wherePivot('AwardType', AwardType::Event)
            ->wherePivot('AwardData', $this->event_id)
            ->wherePivot('AwardDataExtra', $this->tier_index)
            ->withPivot(['AwardDate'])
            ->using(PlayerBadge::class);
    }

    /**
     * @return BelongsTo<Event, EventAward>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'event_id', 'id');
    }

    /**
     * Get the badges awarded to players for this event tier.
     *
     * NOTE: We use a subquery to look up the `event_id` at query execution time because
     * the model attributes are not available during relationship build time. This ensures
     * we get the correct `event_id` for both counting badges and eager loading a user's badges.
     *
     * @return HasMany<PlayerBadge>
     */
    public function playerBadges(): HasMany
    {
        $relation = $this->hasMany(PlayerBadge::class, 'AwardDataExtra', 'tier_index')
            ->select([
                'SiteAwards.id',
                'SiteAwards.AwardDate',
                'SiteAwards.user_id',
                'SiteAwards.AwardDataExtra',
                'SiteAwards.AwardData',
            ])
            ->where('AwardType', AwardType::Event);

        // Look up the correct `event_id` using a subquery since we can't access
        // `$this->event_id` during relationship build time.
        $relation->getQuery()->whereIn('AwardData', function ($query) {
            $query->select('event_id')
                ->from('event_awards')
                ->whereColumn('tier_index', 'SiteAwards.AwardDataExtra');
        });

        return $relation;
    }

    // == scopes
}
