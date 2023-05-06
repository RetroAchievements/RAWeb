<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Platform\Models\Achievement;
use App\Platform\Models\Game;
use App\Platform\Models\PlayerAchievementLegacy;
use App\Platform\Models\System;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserProgressTest extends TestCase
{
    use RefreshDatabase;
    use BootstrapsApiV1;

    public function testGetUserProgressUnknownUser(): void
    {
        $this->get($this->apiUrl('GetUserProgress', ['u' => 'nonExistant']))
            ->assertSuccessful()
            ->assertJson([]);
    }

    public function testGetUserProgress(): void
    {
        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create(['ConsoleID' => $system->ID]);
        $publishedAchievements = Achievement::factory()->published()->count(3)->create(['GameID' => $game->ID]);
        PlayerAchievementLegacy::factory()->hardcore()->create(['AchievementID' => $publishedAchievements->get(0)->ID, 'User' => $this->user->User]);
        PlayerAchievementLegacy::factory()->create(['AchievementID' => $publishedAchievements->get(1)->ID, 'User' => $this->user->User]);
        /** @var Game $game2 */
        $game2 = Game::factory()->create(['ConsoleID' => $system->ID]);
        $publishedAchievements2 = Achievement::factory()->published()->count(5)->create(['GameID' => $game2->ID]);
        /** @var Game $game3 */
        $game3 = Game::factory()->create(['ConsoleID' => $system->ID]);

        $csv = $game->ID . ',' . $game2->ID . ',' . $game3->ID . ',15934';

        $this->get($this->apiUrl('GetUserProgress', ['u' => $this->user->User, 'i' => $csv]))
            ->assertSuccessful()
            ->assertJson([
                $game->ID => [
                    'NumPossibleAchievements' => 3,
                    'PossibleScore' => $publishedAchievements->get(0)->Points +
                                       $publishedAchievements->get(1)->Points +
                                       $publishedAchievements->get(2)->Points,
                    'NumAchievedHardcore' => 1,
                    'ScoreAchievedHardcore' => $publishedAchievements->get(0)->Points,
                    'NumAchieved' => 1,
                    'ScoreAchieved' => $publishedAchievements->get(1)->Points,
                ],
                $game2->ID => [
                    'NumPossibleAchievements' => 5,
                    'PossibleScore' => $publishedAchievements2->get(0)->Points +
                                       $publishedAchievements2->get(1)->Points +
                                       $publishedAchievements2->get(2)->Points +
                                       $publishedAchievements2->get(3)->Points +
                                       $publishedAchievements2->get(4)->Points,
                    'NumAchievedHardcore' => 0,
                    'ScoreAchievedHardcore' => 0,
                    'NumAchieved' => 0,
                    'ScoreAchieved' => 0,
                ],
                $game3->ID => [
                    'NumPossibleAchievements' => 0,
                    'PossibleScore' => 0,
                    'NumAchievedHardcore' => 0,
                    'ScoreAchievedHardcore' => 0,
                    'NumAchieved' => 0,
                    'ScoreAchieved' => 0,
                ],
                '15934' => [
                    'NumPossibleAchievements' => 0,
                    'PossibleScore' => 0,
                    'NumAchievedHardcore' => 0,
                    'ScoreAchievedHardcore' => 0,
                    'NumAchieved' => 0,
                    'ScoreAchieved' => 0,
                ],
            ]);
    }
}
