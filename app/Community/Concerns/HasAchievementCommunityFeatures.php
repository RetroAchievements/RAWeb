<?php

declare(strict_types=1);

namespace App\Community\Concerns;

use App\Models\AchievementComment;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasAchievementCommunityFeatures
{
    public static function bootHasAchievementCommunityFeatures(): void
    {
    }

    /**
     * @return MorphMany<AchievementComment>
     */
    public function comments(): MorphMany
    {
        return $this->morphMany(AchievementComment::class, 'commentable')->with('user');
    }
}
