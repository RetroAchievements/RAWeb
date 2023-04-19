<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use Illuminate\Foundation\Testing\RefreshDatabase;
use LegacyApp\Platform\Models\Achievement;
use LegacyApp\Platform\Models\Game;
use LegacyApp\Platform\Models\System;
use LegacyApp\Site\Models\User;
use Tests\Feature\Platform\TestsPlayerAchievements;
use Tests\TestCase;

class GameRankAndScoreTest extends TestCase
{
    use RefreshDatabase;
    use BootstrapsApiV1;
    use TestsPlayerAchievements;

    public function testGetGameRankAndScoreUnknownGame(): void
    {
        $this->get($this->apiUrl('GetGameRankAndScore', ['g' => 999999]))
            ->assertSuccessful()
            ->assertJson([]);
    }

    public function testGetGameRankAndScore(): void
    {
        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create(['ConsoleID' => $system->ID]);
        $publishedAchievements =

        $ach1 = Achievement::factory()->published()->create(['GameID' => $game->ID, 'Points' => 3]);
        $ach2 = Achievement::factory()->published()->create(['GameID' => $game->ID, 'Points' => 5]);
        $ach3 = Achievement::factory()->published()->create(['GameID' => $game->ID, 'Points' => 10]);

        // $this->user has mastered the game
        $this->addHardcoreUnlock($this->user, $ach1);
        $this->addHardcoreUnlock($this->user, $ach2);
        $this->addHardcoreUnlock($this->user, $ach3);

        // $user2 has completed the game
        /** @var User $user2 */
        $user2 = User::factory()->create();
        $this->addHardcoreUnlock($user2, $ach1);
        $this->addHardcoreUnlock($user2, $ach2);
        $this->addSoftcoreUnlock($user2, $ach3);

        // $user3 has not completed the game
        /** @var User $user3 */
        $user3 = User::factory()->create();
        $this->addHardcoreUnlock($user3, $ach1);
        $this->addHardcoreUnlock($user3, $ach3);

        // ask for high scores first (t=0 [default])
        $this->get($this->apiUrl('GetGameRankAndScore', ['g' => $game->ID]))
            ->assertSuccessful()
            ->assertJsonCount(3)
            ->assertJson([
                [
                    'User' => $this->user->User,
                    'NumAchievements' => 3,
                    'TotalScore' => $ach1->Points + $ach2->Points + $ach3->Points,
                ],
                [
                    'User' => $user3->User,
                    'NumAchievements' => 2,
                    'TotalScore' => $ach1->Points + $ach3->Points,
                ],
                [
                    'User' => $user2->User,
                    'NumAchievements' => 2,
                    'TotalScore' => $ach1->Points + $ach2->Points,
                ],
            ]);

        // ask for masters (t=1)
        $this->get($this->apiUrl('GetGameRankAndScore', ['g' => $game->ID, 't' => 1]))
            ->assertSuccessful()
            ->assertJsonCount(1)
            ->assertJson([
                [
                    'User' => $this->user->User,
                    'NumAchievements' => 3,
                    'TotalScore' => $ach1->Points + $ach2->Points + $ach3->Points,
                ],
            ]);
    }
}
