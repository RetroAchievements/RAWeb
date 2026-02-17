<?php

declare(strict_types=1);

namespace App\Models;

use App\Community\Enums\AwardType;
use App\Support\Database\Eloquent\BaseModel;
use Database\Factories\EventAwardFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class EventAward extends BaseModel
{
    /** @use HasFactory<EventAwardFactory> */
    use HasFactory;

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
            ->where('award_type', AwardType::Event)
            ->where('award_key', $this->event_id)
            ->where('award_tier', $this->tier_index)
            ->whereHas('user', function ($query) {
                /** @var Builder<User> $query */
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
     * @return BelongsToMany<User, $this>
     */
    public function awardedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_awards', 'id', 'user_id')
            ->wherePivot('award_type', AwardType::Event)
            ->wherePivot('award_key', $this->event_id)
            ->wherePivot('award_tier', $this->tier_index)
            ->withPivot(['awarded_at'])
            ->using(PlayerBadge::class);
    }

    /**
     * @return BelongsTo<Event, $this>
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
     * @return HasMany<PlayerBadge, $this>
     */
    public function playerBadges(): HasMany
    {
        $relation = $this->hasMany(PlayerBadge::class, 'award_tier', 'tier_index')
            ->select([
                'user_awards.id',
                'user_awards.awarded_at',
                'user_awards.user_id',
                'user_awards.award_tier',
                'user_awards.award_key',
            ])
            ->where('award_type', AwardType::Event);

        // Look up the correct `event_id` using a subquery since we can't access
        // `$this->event_id` during relationship build time.
        $relation->getQuery()->whereIn('award_key', function ($query) {
            $query->select('event_id')
                ->from('event_awards')
                ->whereColumn('tier_index', DB::raw('user_awards.award_tier'));
        });

        return $relation;
    }

    // == scopes
}
