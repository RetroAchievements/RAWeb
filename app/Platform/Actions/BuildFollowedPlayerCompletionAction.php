<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Community\Enums\UserRelationStatus;
use App\Data\UserData;
use App\Models\Game;
use App\Models\PlayerGame;
use App\Models\User;
use App\Models\UserRelation;
use App\Platform\Data\FollowedPlayerCompletionData;
use App\Platform\Data\PlayerGameData;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BuildFollowedPlayerCompletionAction
{
    /**
     * @return Collection<int, FollowedPlayerCompletionData>
     */
    public function execute(?User $user, Game $game): Collection
    {
        if ($user === null) {
            return collect();
        }

        $followedPlayerCompletion = null;

        $limitedFollowedUsers = UserRelation::query()
            ->join('users', 'user_relations.related_user_id', '=', 'users.id')
            ->where(DB::raw('user_relations.user_id'), '=', $user->id)
            ->where(DB::raw('user_relations.status'), '=', UserRelationStatus::Following)
            ->select('users.id')
            ->limit(1000)
            ->pluck('id')
            ->toArray();

        $fields = [
            'user_id',
            'achievements_unlocked',
            'achievements_unlocked_hardcore',
            'achievements_unlocked_softcore',
            'beaten_at',
            'beaten_hardcore_at',
            'completed_at',
            'completed_hardcore_at',
            'achievements_total',
            'points',
            'points_hardcore',
            'points_total',
            'last_played_at',
        ];

        $followedPlayerCompletion = PlayerGame::where('game_id', $game->id)
            ->whereIn('user_id', $limitedFollowedUsers)
            ->where(function ($query) {
                $query->where('achievements_unlocked', '>', 0)
                    ->orWhere('achievements_unlocked_hardcore', '>', 0);
            })
            ->select($fields)
            ->orderBy('achievements_unlocked_hardcore', 'DESC')
            ->orderBy('achievements_unlocked', 'DESC')
            ->limit(50)
            ->get();

        $userIds = $followedPlayerCompletion->pluck('user_id')->toArray();
        $followedPlayers = User::whereIn('id', $userIds)->get()->keyBy('id');

        return $followedPlayerCompletion->map(function (PlayerGame $playerGame) use ($followedPlayers) {
            $user = $followedPlayers[$playerGame->user_id] ?? null;

            if (!$user || $user->banned_at) {
                return null;
            }

            return new FollowedPlayerCompletionData(
                user: UserData::fromUser($user),
                playerGame: PlayerGameData::from($playerGame),
            );
        })->filter()->values();
    }
}
