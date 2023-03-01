<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use LegacyApp\Community\Enums\ActivityType;
use LegacyApp\Community\Models\UserActivity;
use LegacyApp\Platform\Models\Achievement;
use LegacyApp\Platform\Models\Game;
use LegacyApp\Platform\Models\PlayerAchievement;
use LegacyApp\Platform\Models\System;
use LegacyApp\Site\Models\User;
use Tests\TestCase;

class UserRecentlyPlayedGamesTest extends TestCase
{
    use RefreshDatabase;
    use BootstrapsApiV1;

    public function testGetUserRecentlyPlayedGamesUnknownUser(): void
    {
        $this->get($this->apiUrl('GetUserRecentlyPlayedGames', ['u' => 'nonExistant']))
            ->assertSuccessful()
            ->assertJson([]);
    }

    public function testGetUserRecentlyPlayedGames(): void
    {
        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create([
            'ConsoleID' => $system->ID,
            'ImageIcon' => '/Images/001234.png',
        ]);
        $publishedAchievements = Achievement::factory()->published()->count(3)->create(['GameID' => $game->ID]);
        /** @var Game $game2 */
        $game2 = Game::factory()->create(['ConsoleID' => $system->ID]);
        /** @var User $user */
        $user = User::factory()->create();
        /** @var UserActivity $activity */
        $activity = new UserActivity([
            'User' => $user->User,
            'timestamp' => Carbon::now()->subDays(1),
            'lastupdate' => Carbon::now()->subDays(1),
            'activitytype' => ActivityType::StartedPlaying,
            'data' => $game2->ID,
        ]);
        $activity->save();
        /** @var UserActivity $activity2 */
        $activity2 = new UserActivity([
            'User' => $user->User,
            'timestamp' => Carbon::now()->subHours(1),
            'lastupdate' => Carbon::now()->subMinutes(5), // active less than 5 minutes ago is Online
            'activitytype' => ActivityType::StartedPlaying,
            'data' => $game->ID,
        ]);
        $activity2->save();

        $hardcoreAchievement = $publishedAchievements->get(0);
        PlayerAchievement::factory()->hardcore()->create(['AchievementID' => $hardcoreAchievement->ID, 'User' => $user->User]);
        $softcoreAchievement = $publishedAchievements->get(0);
        PlayerAchievement::factory()->create(['AchievementID' => $softcoreAchievement->ID, 'User' => $user->User]);

        $this->get($this->apiUrl('GetUserRecentlyPlayedGames', ['u' => $user->User]))
            ->assertSuccessful()
            ->assertJson([
                [
                    'GameID' => $game->ID,
                    'Title' => $game->Title,
                    'ConsoleID' => $system->ID,
                    'ConsoleName' => $system->Name,
                    'ImageIcon' => $game->ImageIcon,
                    'LastPlayed' => $activity2->lastupdate->__toString(),
                    'NumPossibleAchievements' => 3,
                    'PossibleScore' => $publishedAchievements->get(0)->Points +
                                       $publishedAchievements->get(1)->Points +
                                       $publishedAchievements->get(2)->Points,
                    'NumAchieved' => 1,
                    'ScoreAchieved' => $softcoreAchievement->Points,
                    'NumAchievedHardcore' => 1,
                    'ScoreAchievedHardcore' => $hardcoreAchievement->Points,
                ],
                [
                    'GameID' => $game2->ID,
                    'Title' => $game2->Title,
                    'ConsoleID' => $system->ID,
                    'ConsoleName' => $system->Name,
                    'ImageIcon' => $game2->ImageIcon,
                    'LastPlayed' => $activity->lastupdate->__toString(),
                    'NumPossibleAchievements' => 0,
                    'PossibleScore' => 0,
                    'NumAchieved' => 0,
                    'ScoreAchieved' => 0,
                    'NumAchievedHardcore' => 0,
                    'ScoreAchievedHardcore' => 0,
                ],
            ]);
    }
}
