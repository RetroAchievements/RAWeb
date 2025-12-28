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
        $publishedAchievement = Achievement::factory()->promoted()->create(['game_id' => $game->id]);
        $unofficialAchievement = Achievement::factory()->create(['game_id' => $game->id]);

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
            'BadgeName' => $publishedAchievement->image_name . '_lock',
            'BadgeURL' => media_asset('Badge/' . $publishedAchievement->image_name . '_lock.png'),
            'BadgeClassNames' => '',
            'DisplayOrder' => $publishedAchievement->order_column,
            'Unlocked' => false,
            'DateAwarded' => null,
            'HardcoreAchieved' => null,
        ], $gameAchievementsWithUserProgress[0]);
    }

    public function testItAttachesUserProgressToGameAchievements(): void
    {
        // Arrange
        Carbon::setTestNow(Carbon::now());

        $user = User::factory()->create();
        $game = $this->seedGame(withHash: false);
        $publishedAchievement = Achievement::factory()->promoted()->create(['game_id' => $game->id]);

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
            'BadgeName' => $publishedAchievement->image_name,
            'BadgeURL' => media_asset('Badge/' . $publishedAchievement->image_name . '.png'),
            'BadgeClassNames' => 'goldimage',
            'DisplayOrder' => $publishedAchievement->order_column,
            'Unlocked' => true,
            'DateAwarded' => Carbon::now()->format('Y-m-d H:i:s'),
            'HardcoreAchieved' => Carbon::now()->format('Y-m-d H:i:s'),
        ], $gameAchievementsWithUserProgress[0]);
    }
}
