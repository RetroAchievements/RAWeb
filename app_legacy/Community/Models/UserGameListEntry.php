<?php

declare(strict_types=1);

namespace LegacyApp\Community\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use LegacyApp\Platform\Models\Game;
use LegacyApp\Site\Models\User;
use LegacyApp\Support\Database\Eloquent\BaseModel;

class UserGameListEntry extends BaseModel
{
    protected $table = 'SetRequest';

    protected $fillable = [
        'User',
        'GameID',
    ];

    protected $casts = [
        'GameID' => 'integer',
    ];

    public const CREATED_AT = 'Updated';
    public const UPDATED_AT = null;

    /**
     * @return BelongsTo<User, UserGameListEntry>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'User', 'User');
    }

    /**
     * @return BelongsTo<Game, UserGameListEntry>
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class, 'GameID');
    }

    /**
     * @param Builder<UserGameListEntry> $query
     * @return Builder<UserGameListEntry>
     */
    public function scopeWithoutAchievements(Builder $query): Builder
    {
        return $query
            ->select('SetRequest.*')
            ->leftJoin('Achievements', 'SetRequest.GameID', '=', 'Achievements.GameID')
            ->groupBy('Achievements.GameID')
            ->havingRaw('count(Achievements.ID) = 0');
    }

    public static function getUserSetRequestsInformation(User $user): array
    {
        $requests = [];
        $requests['total'] = 0;
        $requests['pointsForNext'] = 0;
        $requests['maxSoftcoreReached'] = false;
        $points = 0;
        $maxSoftcoreThreshold = 10000; // Softcore points count towards requests up to 10000 points

        $points += $user->RAPoints + min($user->RASoftcorePoints, $maxSoftcoreThreshold);
        $requests['maxSoftcoreReached'] = ($user->RASoftcorePoints >= $maxSoftcoreThreshold);

        // logic behind the amount of requests based on player's score:
        $boundariesAndChunks = [
            100000 => 10000, // from 100k to infinite, +1 for each 10k chunk of points
            10000 => 5000,   // from 10k to 100k, +1 for each 5k chunk
            2500 => 2500,    // from 2.5k to 10k, +1 for each 2.5k chunk
            0 => 1250,       // from 0 to 2.5k, +1 for each 1.25k chunk
        ];

        $pointsLeft = $points;
        foreach ($boundariesAndChunks as $boundary => $chunk) {
            if ($pointsLeft >= $boundary) {
                $aboveBoundary = $pointsLeft - $boundary;
                $requests['total'] += floor($aboveBoundary / $chunk);

                if ($requests['pointsForNext'] === 0) {
                    $nextThreshold = $boundary + (floor($aboveBoundary / $chunk) + 1) * $chunk;
                    $requests['pointsForNext'] = $nextThreshold - $pointsLeft;
                }

                $pointsLeft = $boundary;
            }
        }

        // adding the number of years the user is here
        $requests['total'] += Carbon::now()->diffInYears($user->Created);

        // adding the number of event awards
        $requests['total'] += getUserEventAwardCount($user->User);

        settype($requests['total'], 'integer');
        settype($requests['pointsForNext'], 'integer');

        return $requests;
    }
}
