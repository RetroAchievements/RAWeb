<?php

declare(strict_types=1);

namespace Tests\Feature\Platform\Action;

use App\Models\Achievement;
use App\Models\AchievementSet;
use App\Models\AchievementSetAchievement;
use App\Models\Game;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Platform\Concerns\TestsPlayerAchievements;
use Tests\Feature\Platform\Concerns\TestsPlayerBadges;
use Tests\TestCase;

class UpdateGameAchievementsMetricsTest extends TestCase
{
    use RefreshDatabase;

    use TestsPlayerAchievements;
    use TestsPlayerBadges;

    public function testMetrics(): void
    {
        // Arrange
        User::factory()->count(10)->create();
        $game = $this->seedGame(withHash: false);
        Achievement::factory()->published()->count(10)->create(['GameID' => $game->id, 'Points' => 3]);

        $achievementSet = AchievementSet::factory()->create();
        $achievements = Achievement::all();
        foreach ($achievements as $achievement) {
            AchievementSetAchievement::factory()->create([
                'achievement_set_id' => $achievementSet->id,
                'achievement_id' => $achievement->id,
            ]);
        }

        // Act
        foreach (User::all() as $index => $user) {
            for ($i = 0; $i <= $index; $i++) {
                $this->addHardcoreUnlock($user, Achievement::find($i + 1));
            }
        }

        // Assert
        $updatedAchievements = Achievement::all();
        $updatedAchievementSet = AchievementSet::find($achievementSet->id);
        $updatedGame = Game::find($game->id);

        // Verify the denormalized data on the achievement entities.
        $this->assertEquals(
            [10, 9, 8, 7, 6, 5, 4, 3, 2, 1],
            $updatedAchievements->pluck('unlocks_total')->toArray()
        );
        $this->assertEquals(
            [1, 0.9, 0.8, 0.7, 0.6, 0.5, 0.4, 0.3, 0.2, 0.1],
            $updatedAchievements->pluck('unlock_percentage')->toArray()
        );
        $this->assertEquals(
            [3, 3, 3, 3, 3, 4, 4, 5, 7, 13],
            $updatedAchievements->pluck('points_weighted')->toArray()
        );

        // Verify the denormalized data on the achievement set.
        $this->assertEquals($updatedGame->players_total, $updatedAchievementSet->players_total);
        $this->assertEquals($updatedGame->players_hardcore, $updatedAchievementSet->players_hardcore);
        $this->assertEquals($updatedAchievements->sum('points'), $updatedAchievementSet->points_total);
        $this->assertEquals($updatedAchievements->sum('TrueRatio'), $updatedAchievementSet->points_weighted);
    }
}
