<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\Achievement;
use App\Models\Game;
use App\Models\System;
use App\Platform\Actions\UpdateGameMetrics;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Platform\Concerns\TestsPlayerAchievements;
use Tests\TestCase;

class UserProgressTest extends TestCase
{
    use RefreshDatabase;
    use TestsPlayerAchievements;
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
        $this->addHardcoreUnlock($this->user, $publishedAchievements->get(0));
        $this->addSoftcoreUnlock($this->user, $publishedAchievements->get(1));
        /** @var Game $game2 */
        $game2 = Game::factory()->create(['ConsoleID' => $system->ID]);
        $publishedAchievements2 = Achievement::factory()->published()->count(5)->create(['GameID' => $game2->ID]);
        (new UpdateGameMetrics())->execute($game2);
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
                    'NumAchieved' => 2,
                    'ScoreAchieved' => $publishedAchievements->get(0)->Points +
                                       $publishedAchievements->get(1)->Points,
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
