<?php

declare(strict_types=1);

namespace App\Community\Concerns;

use App\Models\User;
use App\Models\Viewable;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Provides polymorphic view tracking for models.
 *
 * This trait allows models to track when individual users have viewed them,
 * storing timestamps in the `viewables` table. Useful for features like
 * unread indicators, view counts, or determining if a user has seen content.
 *
 * @example
 * ```php
 * class News extends BaseModel {
 *     use HasViews;
 * }
 *
 * $news->markAsViewedBy($user);
 * if ($news->wasViewedBy($user)) {
 *     // ... user has viewed this news item ...
 * }
 * ```
 */
trait HasViews
{
    /**
     * @return MorphMany<Viewable, $this>
     */
    public function views(): MorphMany
    {
        return $this->morphMany(Viewable::class, 'viewable');
    }

    /**
     * Mark this item as viewed by the given user.
     */
    public function markAsViewedBy(User $user): void
    {
        $this->views()->firstOrCreate(
            [
                'user_id' => $user->id,
            ],
            [
                'viewed_at' => now(),
            ]
        );
    }

    /**
     * Mark this item as viewed by the given user, tracking only the latest view.
     * Deletes any previous view records for this user and morph type, then creates
     * a new record. Useful for content where we only need to know if the user has
     * seen the most recent item (eg: site release notes).
     */
    public function markLatestAsViewedBy(User $user): void
    {
        Viewable::where('user_id', $user->id)
            ->where('viewable_type', $this->getMorphClass())
            ->delete();

        $this->views()->create([
            'user_id' => $user->id,
            'viewed_at' => now(),
        ]);
    }

    /**
     * Check if this item was viewed by the given user.
     */
    public function wasViewedBy(User $user): bool
    {
        return $this->views()->where('user_id', $user->id)->exists();
    }

    /**
     * Determine if this model should track only the latest view per user.
     * Default implementation returns false (track all views).
     *
     * If this is truthy, on a view, we'll delete any previous view records
     * for the user and morph type and create a new record.
     */
    public function shouldTrackLatestViewOnly(): bool
    {
        return false;
    }
}
