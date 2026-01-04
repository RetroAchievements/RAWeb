<?php

declare(strict_types=1);

namespace Tests\Feature\Platform\Actions;

use App\Models\AchievementSet;
use App\Models\Game;
use App\Models\GameAchievementSet;
use App\Models\PlayerAchievementSet;
use App\Models\PlayerGame;
use App\Models\System;
use App\Models\UnrankedUser;
use App\Models\User;
use App\Platform\Actions\UpdateGameBeatenMetricsAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateGameBeatenMetricsActionTest extends TestCase
{
    use RefreshDatabase;

    public function testItExcludesUnrankedUsersFromTimeToBeatMedianCalculation(): void
    {
        // Arrange
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);

        $rankedUser1 = User::factory()->create();
        $rankedUser2 = User::factory()->create();
        $rankedUser3 = User::factory()->create();

        $unrankedUser = User::factory()->create();
        UnrankedUser::create(['user_id' => $unrankedUser->id]);

        PlayerGame::factory()->create([
            'user_id' => $rankedUser1->id,
            'game_id' => $game->id,
            'time_to_beat_hardcore' => 100,
        ]);
        PlayerGame::factory()->create([
            'user_id' => $rankedUser2->id,
            'game_id' => $game->id,
            'time_to_beat_hardcore' => 200,
        ]);
        PlayerGame::factory()->create([
            'user_id' => $rankedUser3->id,
            'game_id' => $game->id,
            'time_to_beat_hardcore' => 300,
        ]);
        PlayerGame::factory()->create([
            'user_id' => $unrankedUser->id,
            'game_id' => $game->id,
            'time_to_beat_hardcore' => 30, // this should be excluded
        ]);

        // Act
        (new UpdateGameBeatenMetricsAction())->execute($game);

        // Assert
        $game->refresh();
        $this->assertEquals(3, $game->times_beaten_hardcore);
        $this->assertEquals(200, $game->median_time_to_beat_hardcore); // median of [100, 200, 300] = 200
    }

    public function testItExcludesUnrankedUsersFromTimeToCompleteMedianCalculation(): void
    {
        // Arrange
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);

        $achievementSet = AchievementSet::factory()->create([
            'achievements_published' => 10,
            'players_total' => 0,
            'players_hardcore' => 0,
        ]);
        GameAchievementSet::factory()->create([
            'game_id' => $game->id,
            'achievement_set_id' => $achievementSet->id,
        ]);

        $rankedUser1 = User::factory()->create();
        $rankedUser2 = User::factory()->create();
        $rankedUser3 = User::factory()->create();

        $unrankedUser = User::factory()->create();
        UnrankedUser::create(['user_id' => $unrankedUser->id]);

        PlayerAchievementSet::create([
            'user_id' => $rankedUser1->id,
            'achievement_set_id' => $achievementSet->id,
            'achievements_unlocked' => 10,
            'achievements_unlocked_hardcore' => 10,
            'time_taken' => 1000,
            'time_taken_hardcore' => 1000,
        ]);
        PlayerAchievementSet::create([
            'user_id' => $rankedUser2->id,
            'achievement_set_id' => $achievementSet->id,
            'achievements_unlocked' => 10,
            'achievements_unlocked_hardcore' => 10,
            'time_taken' => 2000,
            'time_taken_hardcore' => 2000,
        ]);
        PlayerAchievementSet::create([
            'user_id' => $rankedUser3->id,
            'achievement_set_id' => $achievementSet->id,
            'achievements_unlocked' => 10,
            'achievements_unlocked_hardcore' => 10,
            'time_taken' => 3000,
            'time_taken_hardcore' => 3000,
        ]);
        PlayerAchievementSet::create([
            'user_id' => $unrankedUser->id,
            'achievement_set_id' => $achievementSet->id,
            'achievements_unlocked' => 10,
            'achievements_unlocked_hardcore' => 10,
            'time_taken' => 30,
            'time_taken_hardcore' => 30, // this should be excluded
        ]);

        // Act
        (new UpdateGameBeatenMetricsAction())->execute($game);

        // Assert
        $achievementSet->refresh();
        $this->assertEquals(3, $achievementSet->times_completed_hardcore); // only 3 ranked users
        $this->assertEquals(2000, $achievementSet->median_time_to_complete_hardcore); // median of [1000, 2000, 3000] = 2000
    }

    public function testItHandlesSoftcoreTimesCorrectly(): void
    {
        // Arrange
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);

        $rankedUser1 = User::factory()->create();
        $rankedUser2 = User::factory()->create();

        $unrankedUser = User::factory()->create();
        UnrankedUser::create(['user_id' => $unrankedUser->id]);

        PlayerGame::factory()->create([
            'user_id' => $rankedUser1->id,
            'game_id' => $game->id,
            'time_to_beat' => 500,
            'time_to_beat_hardcore' => null, // !!
        ]);
        PlayerGame::factory()->create([
            'user_id' => $rankedUser2->id,
            'game_id' => $game->id,
            'time_to_beat' => 700,
            'time_to_beat_hardcore' => null, // !!
        ]);
        PlayerGame::factory()->create([
            'user_id' => $unrankedUser->id,
            'game_id' => $game->id,
            'time_to_beat' => 30, // should be excluded
            'time_to_beat_hardcore' => null, // !!
        ]);

        // Act
        (new UpdateGameBeatenMetricsAction())->execute($game);

        // Assert
        $game->refresh();
        $this->assertEquals(2, $game->times_beaten); // only 2 ranked users
        $this->assertEquals(600, $game->median_time_to_beat); // median of [500, 700] = 600
    }

    public function testItReturnsZeroWhenNoRankedUsersHaveTimes(): void
    {
        // Arrange
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);

        $unrankedUser = User::factory()->create();
        UnrankedUser::create(['user_id' => $unrankedUser->id]);

        PlayerGame::factory()->create([
            'user_id' => $unrankedUser->id,
            'game_id' => $game->id,
            'time_to_beat_hardcore' => 100,
        ]);

        // Act
        (new UpdateGameBeatenMetricsAction())->execute($game);

        // Assert
        $game->refresh();
        $this->assertEquals(0, $game->times_beaten_hardcore);
        $this->assertNull($game->median_time_to_beat_hardcore);
    }
}
