<?php

declare(strict_types=1);

namespace App\Community\Concerns;

use App\Models\User;
use App\Models\Viewable;
use Illuminate\Database\Eloquent\Relations\MorphMany;

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
