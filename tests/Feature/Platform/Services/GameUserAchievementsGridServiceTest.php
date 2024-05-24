<?php

declare(strict_types=1);

namespace Tests\Feature\Platform\Services;

use App\Models\Achievement;
use App\Models\User;
use App\Platform\Services\GameUserAchievementsGridService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Feature\Platform\Concerns\TestsPlayerAchievements;
use Tests\TestCase;

class GameUserAchievementsGridServiceTest extends TestCase
{
    use RefreshDatabase;
    use TestsPlayerAchievements;

    public function testItReturnsAllGameAchievements(): void
    {
        // Arrange
        $user = User::factory()->create();
        $game = $this->seedGame(withHash: false);
        $publishedAchievement = Achievement::factory()->published()->create(['GameID' => $game->id]);
        $unofficialAchievement = Achievement::factory()->create(['GameID' => $game->id]);

        $service = new GameUserAchievementsGridService();

        // Act
        $gameAchievementsWithUserProgress = $service->getGameAchievementsWithUserProgress($game, $user);

        // Assert
        $publishedAchievement->refresh();

        $this->assertEquals(1, count($gameAchievementsWithUserProgress));
        $this->assertEquals([
            'ID' => $publishedAchievement->id,
            'Title' => $publishedAchievement->title,
            'Description' => $publishedAchievement->description,
            'Points' => $publishedAchievement->points,
            'TrueRatio' => $publishedAchievement->points_weighted,
            'Type' => $publishedAchievement->type,
            'BadgeName' => $publishedAchievement->BadgeName . '_lock',
            'BadgeURL' => media_asset('Badge/' . $publishedAchievement->BadgeName . '_lock.png'),
            'BadgeClassNames' => '',
            'DisplayOrder' => $publishedAchievement->DisplayOrder,
            'Unlocked' => false,
            'DateAwarded' => null,
        ], $gameAchievementsWithUserProgress[0]);
    }

    public function testItAttachesUserProgressToGameAchievements(): void
    {
        // Arrange
        Carbon::setTestNow(Carbon::now());

        $user = User::factory()->create();
        $game = $this->seedGame(withHash: false);
        $publishedAchievement = Achievement::factory()->published()->create(['GameID' => $game->id]);

        $this->addHardcoreUnlock($user, $publishedAchievement);

        $service = new GameUserAchievementsGridService();

        // Act
        $gameAchievementsWithUserProgress = $service->getGameAchievementsWithUserProgress($game, $user);

        // Assert
        $publishedAchievement->refresh();

        $this->assertEquals(1, count($gameAchievementsWithUserProgress));
        $this->assertEquals([
            'ID' => $publishedAchievement->id,
            'Title' => $publishedAchievement->title,
            'Description' => $publishedAchievement->description,
            'Points' => $publishedAchievement->points,
            'TrueRatio' => $publishedAchievement->points_weighted,
            'Type' => $publishedAchievement->type,
            'BadgeName' => $publishedAchievement->BadgeName,
            'BadgeURL' => media_asset('Badge/' . $publishedAchievement->BadgeName . '.png'),
            'BadgeClassNames' => 'goldimage',
            'DisplayOrder' => $publishedAchievement->DisplayOrder,
            'Unlocked' => true,
            'DateAwarded' => Carbon::now()->format('Y-m-d H:i:s'),
            'HardcoreAchieved' => Carbon::now()->format('Y-m-d H:i:s'),
        ], $gameAchievementsWithUserProgress[0]);
    }
}
