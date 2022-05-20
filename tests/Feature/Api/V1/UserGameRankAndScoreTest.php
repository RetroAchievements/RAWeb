<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Platform\Models\Achievement;
use App\Platform\Models\Game;
use App\Platform\Models\PlayerAchievementLegacy;
use App\Site\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserGameRankAndScoreTest extends TestCase
{
    use RefreshDatabase;
    use BootstrapsApiV1;

    public function testGetUserGameRankAndScoreUnknownUser(): void
    {
        $game = Game::factory()->create();

        $this->get($this->apiUrl('GetUserGameRankAndScore', ['u' => 'nonExistant', 'g' => $game->ID]))
            ->assertSuccessful()
            ->assertJson([]);
    }

    public function testGetUserGameRankAndScore(): void
    {
        /** @var Game $game */
        $game = Game::factory()->create();
        $publishedAchievements = Achievement::factory()->published()->count(3)->create(['GameID' => $game->ID]);
        $firstAchievement = $publishedAchievements->get(0);
        $secondAchievement = $publishedAchievements->get(1);
        $thirdAchievement = $publishedAchievements->get(2);
        /** @var User $user */
        $user = User::factory()->create();
        PlayerAchievementLegacy::factory()->hardcore()->create(['AchievementID' => $firstAchievement->ID, 'User' => $user->User]);
        $unlock2 = PlayerAchievementLegacy::factory()->hardcore()->create(['AchievementID' => $secondAchievement->ID, 'User' => $user->User]);

        /** @var User $user2 */
        $user2 = User::factory()->create();
        PlayerAchievementLegacy::factory()->hardcore()->create(['AchievementID' => $firstAchievement->ID, 'User' => $user2->User]);
        PlayerAchievementLegacy::factory()->hardcore()->create(['AchievementID' => $secondAchievement->ID, 'User' => $user2->User]);
        PlayerAchievementLegacy::factory()->hardcore()->create(['AchievementID' => $thirdAchievement->ID, 'User' => $user2->User]);

        $this->get($this->apiUrl('GetUserGameRankAndScore', ['u' => $user->User, 'g' => $game->ID]))
            ->assertSuccessful()
            ->assertJson([[
                'User' => $user->User,
                'TotalScore' => $firstAchievement->Points + $secondAchievement->Points,
                'LastAward' => $unlock2->Date->__toString(),
                'UserRank' => 2,
            ]]);
    }

    public function testGetUserGameRankAndScoreUntracked(): void
    {
        /** @var Game $game */
        $game = Game::factory()->create();
        $publishedAchievements = Achievement::factory()->published()->count(3)->create(['GameID' => $game->ID]);
        $firstAchievement = $publishedAchievements->get(0);
        $secondAchievement = $publishedAchievements->get(1);
        $thirdAchievement = $publishedAchievements->get(2);
        /** @var User $user */
        $user = User::factory()->create(['Untracked' => true]);
        PlayerAchievementLegacy::factory()->hardcore()->create(['AchievementID' => $firstAchievement->ID, 'User' => $user->User]);
        $unlock2 = PlayerAchievementLegacy::factory()->hardcore()->create(['AchievementID' => $secondAchievement->ID, 'User' => $user->User]);

        /** @var User $user2 */
        $user2 = User::factory()->create();
        PlayerAchievementLegacy::factory()->hardcore()->create(['AchievementID' => $firstAchievement->ID, 'User' => $user2->User]);
        PlayerAchievementLegacy::factory()->hardcore()->create(['AchievementID' => $secondAchievement->ID, 'User' => $user2->User]);
        PlayerAchievementLegacy::factory()->hardcore()->create(['AchievementID' => $thirdAchievement->ID, 'User' => $user2->User]);

        $this->get($this->apiUrl('GetUserGameRankAndScore', ['u' => $user->User, 'g' => $game->ID]))
            ->assertSuccessful()
            ->assertJson([[
                'User' => $user->User,
                'TotalScore' => $firstAchievement->Points + $secondAchievement->Points,
                'LastAward' => $unlock2->Date->__toString(),
                'UserRank' => null,
            ]]);
    }
}
