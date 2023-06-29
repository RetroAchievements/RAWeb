<?php

declare(strict_types=1);

namespace App\Community\Concerns;

use App\Community\Models\GameComment;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasGameCommunityFeatures
{
    public static function bootHasGameCommunityFeatures(): void
    {
    }

    /**
     * @return MorphMany<GameComment>
     */
    public function comments(): MorphMany
    {
        return $this->morphMany(GameComment::class, 'commentable')->with('user');
    }
}
