<?php

declare(strict_types=1);

namespace App\Models;

use App\Community\Enums\UserGameListType;
use App\Support\Database\Eloquent\BaseModel;
use Database\Factories\UserGameListEntryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class UserGameListEntry extends BaseModel
{
    /** @use HasFactory<UserGameListEntryFactory> */
    use HasFactory;

    // TODO if we ever create a user_game_lists table for multiple "play" etc lists, drop user_game_list_entry_username_game_id_type_unique
    protected $table = 'user_game_list_entries';

    protected $fillable = [
        'user_id',
        'type',
        'game_id',
    ];

    protected $casts = [
        'type' => UserGameListType::class,
    ];

    protected static function newFactory(): UserGameListEntryFactory
    {
        return UserGameListEntryFactory::new();
    }

    // == helpers

    public static function getUserSetRequestsInformation(User $user): array
    {
        $requests = [];
        $requests['total'] = 0;
        $requests['pointsForNext'] = 0;
        $points = 0;

        $points += $user->points_hardcore + $user->points;

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
        $requests['total'] += ((int) Carbon::now()->diffInYears($user->created_at, true));

        // adding the number of event awards
        $requests['total'] += getUserEventAwardCount($user);

        settype($requests['total'], 'integer');
        settype($requests['pointsForNext'], 'integer');

        return $requests;
    }

    // == accessors

    // == mutators

    // == relations

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo<Game, $this>
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class, 'game_id', 'id');
    }

    // == scopes

    /**
     * @param Builder<UserGameListEntry> $query
     * @return Builder<UserGameListEntry>
     */
    public function scopeWithoutAchievements(Builder $query): Builder
    {
        return $query
            ->select('user_game_list_entries.*')
            ->leftJoin('achievements', 'user_game_list_entries.game_id', '=', 'achievements.game_id')
            ->groupBy('user_game_list_entries.game_id')
            ->havingRaw('count(achievements.id) = 0');
    }
}
