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
     * Check if this item was viewed by the given user.
     */
    public function wasViewedBy(User $user): bool
    {
        return $this->views()->where('user_id', $user->id)->exists();
    }
}
