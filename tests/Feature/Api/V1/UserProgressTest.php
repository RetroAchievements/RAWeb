<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\Achievement;
use App\Models\Game;
use App\Models\System;
use App\Platform\Actions\UpdateGameMetricsAction;
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

    public function testGetUserProgressByName(): void
    {
        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create(['system_id' => $system->id]);
        $publishedAchievements = Achievement::factory()->promoted()->count(3)->create(['game_id' => $game->id]);
        $this->addHardcoreUnlock($this->user, $publishedAchievements->get(0));
        $this->addSoftcoreUnlock($this->user, $publishedAchievements->get(1));
        /** @var Game $game2 */
        $game2 = Game::factory()->create(['system_id' => $system->id]);
        $publishedAchievements2 = Achievement::factory()->promoted()->count(5)->create(['game_id' => $game2->id]);
        (new UpdateGameMetricsAction())->execute($game2);
        /** @var Game $game3 */
        $game3 = Game::factory()->create(['system_id' => $system->id]);

        $csv = $game->id . ',' . $game2->id . ',' . $game3->id . ',15934';

        $this->get($this->apiUrl('GetUserProgress', ['u' => $this->user->username, 'i' => $csv]))
            ->assertSuccessful()
            ->assertJson([
                $game->id => [
                    'NumPossibleAchievements' => 3,
                    'PossibleScore' => $publishedAchievements->get(0)->points +
                                       $publishedAchievements->get(1)->points +
                                       $publishedAchievements->get(2)->points,
                    'NumAchievedHardcore' => 1,
                    'ScoreAchievedHardcore' => $publishedAchievements->get(0)->points,
                    'NumAchieved' => 2,
                    'ScoreAchieved' => $publishedAchievements->get(0)->points +
                                       $publishedAchievements->get(1)->points,
                ],
                $game2->id => [
                    'NumPossibleAchievements' => 5,
                    'PossibleScore' => $publishedAchievements2->get(0)->points +
                                       $publishedAchievements2->get(1)->points +
                                       $publishedAchievements2->get(2)->points +
                                       $publishedAchievements2->get(3)->points +
                                       $publishedAchievements2->get(4)->points,
                    'NumAchievedHardcore' => 0,
                    'ScoreAchievedHardcore' => 0,
                    'NumAchieved' => 0,
                    'ScoreAchieved' => 0,
                ],
                $game3->id => [
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

    public function testGetUserProgressByUlid(): void
    {
        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create(['system_id' => $system->id]);
        $publishedAchievements = Achievement::factory()->promoted()->count(3)->create(['game_id' => $game->id]);
        $this->addHardcoreUnlock($this->user, $publishedAchievements->get(0));
        $this->addSoftcoreUnlock($this->user, $publishedAchievements->get(1));
        /** @var Game $game2 */
        $game2 = Game::factory()->create(['system_id' => $system->id]);
        $publishedAchievements2 = Achievement::factory()->promoted()->count(5)->create(['game_id' => $game2->id]);
        (new UpdateGameMetricsAction())->execute($game2);
        /** @var Game $game3 */
        $game3 = Game::factory()->create(['system_id' => $system->id]);

        $csv = $game->id . ',' . $game2->id . ',' . $game3->id . ',15934';

        $this->get($this->apiUrl('GetUserProgress', ['u' => $this->user->ulid, 'i' => $csv]))
            ->assertSuccessful()
            ->assertJson([
                $game->id => [
                    'NumPossibleAchievements' => 3,
                    'PossibleScore' => $publishedAchievements->get(0)->points +
                                       $publishedAchievements->get(1)->points +
                                       $publishedAchievements->get(2)->points,
                    'NumAchievedHardcore' => 1,
                    'ScoreAchievedHardcore' => $publishedAchievements->get(0)->points,
                    'NumAchieved' => 2,
                    'ScoreAchieved' => $publishedAchievements->get(0)->points +
                                       $publishedAchievements->get(1)->points,
                ],
                $game2->id => [
                    'NumPossibleAchievements' => 5,
                    'PossibleScore' => $publishedAchievements2->get(0)->points +
                                       $publishedAchievements2->get(1)->points +
                                       $publishedAchievements2->get(2)->points +
                                       $publishedAchievements2->get(3)->points +
                                       $publishedAchievements2->get(4)->points,
                    'NumAchievedHardcore' => 0,
                    'ScoreAchievedHardcore' => 0,
                    'NumAchieved' => 0,
                    'ScoreAchieved' => 0,
                ],
                $game3->id => [
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
