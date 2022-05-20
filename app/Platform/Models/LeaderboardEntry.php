<?php

declare(strict_types=1);

namespace App\Platform\Models;

use App\Support\Database\Eloquent\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaderboardEntry extends BaseModel
{
    // == accessors

    // == mutators

    // == relations

    /**
     * @return BelongsTo<Leaderboard, LeaderboardEntry>
     */
    public function leaderboard(): BelongsTo
    {
        return $this->belongsTo(Leaderboard::class);
    }

    // == scopes
}
