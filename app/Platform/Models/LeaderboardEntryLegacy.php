<?php

declare(strict_types=1);

namespace App\Platform\Models;

use App\Support\Database\Eloquent\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaderboardEntryLegacy extends BaseModel
{
    // TODO drop LeaderboardEntry table, migrate to LeaderboardEntry/leaderboard_entries
    protected $table = 'LeaderboardEntry';

    // == accessors

    // == mutators

    // == relations

    /**
     * @return BelongsTo<Leaderboard, LeaderboardEntryLegacy>
     */
    public function leaderboard(): BelongsTo
    {
        return $this->belongsTo(Leaderboard::class);
    }

    // == scopes
}
