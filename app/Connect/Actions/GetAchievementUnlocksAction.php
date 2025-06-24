<?php

declare(strict_types=1);

namespace App\Connect\Actions;

use App\Connect\Support\BaseAuthenticatedApiAction;
use App\Models\Achievement;
use Illuminate\Http\Request;

class GetAchievementUnlocksAction extends BaseAuthenticatedApiAction
{
    protected int $achievementId;
    protected int $offset;
    protected int $count;
    protected bool $friendsOnly;

    public function execute(int $achievementId, bool $friendsOnly = false, int $offset = 0, int $count = 10): array
    {
        $this->achievementId = $achievementId;
        $this->friendsOnly = $friendsOnly;
        $this->offset = $offset;
        $this->count = $count;

        return $this->process();
    }

    protected function initialize(Request $request): ?array
    {
        if (!$request->has(['a'])) {
            return $this->missingParameters();
        }

        $this->achievementId = request()->integer('a', 0);
        $this->friendsOnly = request()->boolean('f', false);
        $this->offset = request()->integer('o', 0);
        $this->count = request()->integer('c', 10);

        return null;
    }

    protected function process(): array
    {
        $achievement = Achievement::query()
            ->where('ID', $this->achievementId)
            ->with('game')
            ->first();
        if (!$achievement) {
            return $this->achievementNotFound();
        }

        $playerAchievements = $achievement->playerAchievements()
            ->when($this->friendsOnly, function ($q) {
                $friendIds = $this->user->followedUsers()->pluck('related_user_id');
                $q->whereIn('user_id', $friendIds);
            })
            ->with('user')
            ->orderByRaw('COALESCE(unlocked_hardcore_at, unlocked_at) DESC')
            ->offset($this->offset)
            ->limit($this->count)
            ->get();

        $recentWinners = [];
        foreach ($playerAchievements as $playerAchievement) {
            $recentWinners[] = [
                'User' => $playerAchievement->user->display_name,
                'AvatarUrl' => media_asset('UserPic/' . $playerAchievement->user->User . '.png'),
                'RAPoints' => $playerAchievement->user->RAPoints,
                'DateAwarded' => $playerAchievement->unlocked_hardcore_at ?
                    $playerAchievement->unlocked_hardcore_at->unix() :
                    $playerAchievement->unlocked_at->unix(),
            ];
        }

        return [
            'Success' => true,
            'AchievementID' => $this->achievementId,
            'FriendsOnly' => $this->friendsOnly,
            'Offset' => $this->offset,
            'Count' => $this->count,
            'Response' => [
                'NumEarned' => $achievement->unlocks_total,
                'GameID' => $achievement->game->ID,
                'TotalPlayers' => $achievement->game->players_total,
                'RecentWinner' => $recentWinners,
            ],
        ];
    }
}
