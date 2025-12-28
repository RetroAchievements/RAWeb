<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Achievement;
use App\Models\ForumTopic;
use App\Models\Game;
use App\Models\PlayerAchievement;
use App\Models\StaticData;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class StaticTableSeeder extends Seeder
{
    public function run(): void
    {
        if (StaticData::count() > 0) {
            return;
        }

        $game = Game::orderByDesc('Updated')->first();
        $achievement = Achievement::orderByDesc('updated_at')->first();
        $lastUnlock = PlayerAchievement::orderByDesc('unlocked_at')->first();
        $user = User::orderByDesc('email_verified_at')->first();
        $forumTopic = ForumTopic::first();

        $points = User::tracked()
            ->select([
                DB::raw('SUM(RAPoints) AS HardcorePoints'),
                DB::raw('SUM(RASoftcorePoints) AS SoftcorePoints'),
            ])
            ->first();

        StaticData::create([
            'NumAchievements' => Achievement::count(),
            'NumAwarded' => PlayerAchievement::count(),
            'NumGames' => Game::count(),
            'NumRegisteredUsers' => User::verified()->count(),
            'TotalPointsEarned' => $points['HardcorePoints'] + $points['SoftcorePoints'],
            'LastAchievementEarnedID' => $lastUnlock?->achievement_id ?? $achievement->id,
            'LastAchievementEarnedByUser' => $lastUnlock?->user_id ?? $user->id,
            'LastRegisteredUser' => $user->User,
            'LastUpdatedGameID' => $game->id,
            'LastUpdatedAchievementID' => $achievement->id,
            'LastCreatedGameID' => $game->id,
            'LastCreatedAchievementID' => $achievement->id,
            'last_game_hardcore_mastered_user_id' => $user->id,
            'last_game_hardcore_beaten_user_id' => $user->id,
            'NextGameToScan' => $game->id,
            'Event_AOTW_AchievementID' => $achievement->id,
            'Event_AOTW_ForumID' => $forumTopic->id,
        ]);

        Artisan::call('ra:platform:static:update-awards-data');
    }
}
