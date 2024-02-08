<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\Achievement;
use App\Models\Game;
use App\Models\System;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Platform\Concerns\TestsPlayerAchievements;
use Tests\TestCase;

class UserGameRankAndScoreTest extends TestCase
{
    use RefreshDatabase;
    use BootstrapsApiV1;
    use TestsPlayerAchievements;

    public function testGetUserGameRankAndScoreUnknownUser(): void
    {
        $game = Game::factory()->create();

        $this->get($this->apiUrl('GetUserGameRankAndScore', ['u' => 'nonExistant', 'g' => $game->ID]))
            ->assertSuccessful()
            ->assertJson([]);
    }

    public function testGetUserGameRankAndScore(): void
    {
        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create(['ConsoleID' => $system->ID]);
        $publishedAchievements = Achievement::factory()->published()->count(3)->create(['GameID' => $game->ID]);
        $firstAchievement = $publishedAchievements->get(0);
        $secondAchievement = $publishedAchievements->get(1);
        $thirdAchievement = $publishedAchievements->get(2);
        /** @var User $user */
        $user = User::factory()->create();
        $this->addHardcoreUnlock($user, $firstAchievement);
        $unlock2Time = Carbon::now();
        $this->addHardcoreUnlock($user, $secondAchievement, $unlock2Time);

        /** @var User $user2 */
        $user2 = User::factory()->create();
        $this->addHardcoreUnlock($user2, $firstAchievement);
        $this->addHardcoreUnlock($user2, $secondAchievement);
        $this->addHardcoreUnlock($user2, $thirdAchievement);

        $this->get($this->apiUrl('GetUserGameRankAndScore', ['u' => $user->User, 'g' => $game->ID]))
            ->assertSuccessful()
            ->assertJson([[
                'User' => $user->User,
                'TotalScore' => $firstAchievement->Points + $secondAchievement->Points,
                'LastAward' => $unlock2Time->__toString(),
                'UserRank' => 2,
            ]]);
    }

    public function testGetUserGameRankAndScoreUntracked(): void
    {
        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create(['ConsoleID' => $system->ID]);
        $publishedAchievements = Achievement::factory()->published()->count(3)->create(['GameID' => $game->ID]);
        $firstAchievement = $publishedAchievements->get(0);
        $secondAchievement = $publishedAchievements->get(1);
        $thirdAchievement = $publishedAchievements->get(2);
        /** @var User $user */
        $user = User::factory()->create(['Untracked' => true]);
        $this->addHardcoreUnlock($user, $firstAchievement);
        $unlock2Time = Carbon::now();
        $this->addHardcoreUnlock($user, $secondAchievement, $unlock2Time);

        /** @var User $user2 */
        $user2 = User::factory()->create();
        $this->addHardcoreUnlock($user2, $firstAchievement);
        $this->addHardcoreUnlock($user2, $secondAchievement);
        $this->addHardcoreUnlock($user2, $thirdAchievement);

        $this->get($this->apiUrl('GetUserGameRankAndScore', ['u' => $user->User, 'g' => $game->ID]))
            ->assertSuccessful()
            ->assertJson([[
                'User' => $user->User,
                'TotalScore' => $firstAchievement->Points + $secondAchievement->Points,
                'LastAward' => $unlock2Time->__toString(),
                'UserRank' => null,
            ]]);
    }
}
