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
            'LastAchievementEarnedID' => $achievement->ID,
            'LastAchievementEarnedByUser' => 1,
            'LastRegisteredUser' => $user->User,
            'LastUpdatedGameID' => $game->ID,
            'LastUpdatedAchievementID' => $achievement->ID,
            'LastCreatedGameID' => $game->ID,
            'LastCreatedAchievementID' => $achievement->ID,
            'last_game_hardcore_mastered_user_id' => $user->ID,
            'last_game_hardcore_beaten_user_id' => $user->ID,
            'NextGameToScan' => $game->ID,
            'Event_AOTW_AchievementID' => $achievement->ID,
            'Event_AOTW_ForumID' => $forumTopic->ID,
        ]);
    }
}
