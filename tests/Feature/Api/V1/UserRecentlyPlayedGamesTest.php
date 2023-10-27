<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Community\Enums\ActivityType;
use App\Community\Models\UserActivityLegacy;
use App\Platform\Models\Achievement;
use App\Platform\Models\Game;
use App\Platform\Models\System;
use App\Site\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Feature\Platform\Concerns\TestsPlayerAchievements;
use Tests\TestCase;

class UserRecentlyPlayedGamesTest extends TestCase
{
    use RefreshDatabase;
    use BootstrapsApiV1;
    use TestsPlayerAchievements;

    public function testItValidates(): void
    {
        $this->get($this->apiUrl('GetUserRecentlyPlayedGames', [
            'c' => 'nope',
            'o' => -1,
        ]))
            ->assertJsonValidationErrors([
                'u',
                'c',
                'o',
            ]);
    }

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
        $activity = new UserActivityLegacy([
            'User' => $user->User,
            'timestamp' => Carbon::now()->subDays(1),
            'lastupdate' => Carbon::now()->subDays(1),
            'activitytype' => ActivityType::StartedPlaying,
            'data' => $game2->ID,
        ]);
        $activity->save();
        $activity2 = new UserActivityLegacy([
            'User' => $user->User,
            'timestamp' => Carbon::now()->subHours(1),
            'lastupdate' => Carbon::now()->subMinutes(5), // active less than 5 minutes ago is Online
            'activitytype' => ActivityType::StartedPlaying,
            'data' => $game->ID,
        ]);
        $activity2->save();

        $hardcoreAchievement = $publishedAchievements->get(0);
        $this->addHardcoreUnlock($user, $hardcoreAchievement);
        $softcoreAchievement = $publishedAchievements->get(0);
        $this->addSoftcoreUnlock($user, $softcoreAchievement);

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
