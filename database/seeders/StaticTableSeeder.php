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

class StaticTableSeeder extends Seeder
{
    public function run(): void
    {
        if (StaticData::count() > 0) {
            return;
        }

        $game = Game::first();
        $achievement = Achievement::first();
        $user = User::first();
        $forumTopic = ForumTopic::first();

        StaticData::create([
            'NumAchievements' => Achievement::count(),
            'NumAwarded' => PlayerAchievement::count(),
            'NumGames' => Game::count(),
            'NumRegisteredUsers' => User::verified()->count(),
            'TotalPointsEarned' => 1,
            'LastAchievementEarnedID' => $achievement->id,
            'LastAchievementEarnedByUser' => 1,
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
    }
}
