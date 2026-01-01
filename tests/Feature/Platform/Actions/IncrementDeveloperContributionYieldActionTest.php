<?php

declare(strict_types=1);

namespace Tests\Feature\Platform\Actions;

use App\Community\Enums\AwardType;
use App\Models\Achievement;
use App\Models\AchievementMaintainerUnlock;
use App\Models\PlayerAchievement;
use App\Models\PlayerBadge;
use App\Models\User;
use App\Platform\Actions\IncrementDeveloperContributionYieldAction;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Platform\Concerns\TestsPlayerBadges;
use Tests\TestCase;

class IncrementDeveloperContributionYieldActionTest extends TestCase
{
    use RefreshDatabase;
    use TestsPlayerBadges;

    private IncrementDeveloperContributionYieldAction $action;

    protected function setUp(): void
    {
        parent::setUp();

        $this->action = new IncrementDeveloperContributionYieldAction();
    }

    public function testItSuccessfullyIncrementsForUnlock(): void
    {
        // Arrange
        $developer = User::factory()->create(['yield_unlocks' => 5, 'yield_points' => 500]);
        $player = User::factory()->create();

        $game = $this->seedGame(withHash: false);
        $achievement = Achievement::factory()->promoted()->create([
            'game_id' => $game->id,
            'points' => 50,
            'user_id' => $developer->id,
        ]);

        $playerAchievement = PlayerAchievement::create([
            'user_id' => $player->id,
            'achievement_id' => $achievement->id,
            'unlocked_at' => Carbon::now(),
        ]);

        // Act
        $this->action->execute($developer, $achievement, $playerAchievement, true);
        $developer->refresh();

        // Assert
        $this->assertEquals(6, $developer->yield_unlocks);
        $this->assertEquals(550, $developer->yield_points);
    }

    public function testItSuccessfullyDecrementsForResets(): void
    {
        // Arrange
        $developer = User::factory()->create(['yield_unlocks' => 5, 'yield_points' => 500]);
        $player = User::factory()->create();

        $game = $this->seedGame(withHash: false);
        $achievement = Achievement::factory()->promoted()->create([
            'game_id' => $game->id,
            'points' => 50,
            'user_id' => $developer->id,
        ]);

        $playerAchievement = PlayerAchievement::create([
            'user_id' => $player->id,
            'achievement_id' => $achievement->id,
            'unlocked_at' => Carbon::now(),
        ]);

        // Act
        $this->action->execute($developer, $achievement, $playerAchievement, false);
        $developer->refresh();

        // Assert
        $this->assertEquals(4, $developer->yield_unlocks);
        $this->assertEquals(450, $developer->yield_points);
    }

    public function testItIgnoresUnofficialAchievements(): void
    {
        // Arrange
        $developer = User::factory()->create(['yield_unlocks' => 5, 'yield_points' => 500]);
        $player = User::factory()->create();

        $game = $this->seedGame(withHash: false);
        $achievement = Achievement::factory()->create([
            'game_id' => $game->id,
            'points' => 50,
            'user_id' => $developer->id,
            'is_promoted' => false,
        ]);

        $playerAchievement = PlayerAchievement::create([
            'user_id' => $player->id,
            'achievement_id' => $achievement->id,
            'unlocked_at' => Carbon::now(),
        ]);

        // Act
        $this->action->execute($developer, $achievement, $playerAchievement, true);
        $developer->refresh();

        // Assert
        $this->assertEquals(5, $developer->yield_unlocks); // !! unchanged
        $this->assertEquals(500, $developer->yield_points); // !! unchanged
    }

    public function testItIgnoresDeveloperOwnUnlocks(): void
    {
        // Arrange
        $developer = User::factory()->create(['yield_unlocks' => 5, 'yield_points' => 500]);

        $game = $this->seedGame(withHash: false);
        $achievement = Achievement::factory()->promoted()->create([
            'game_id' => $game->id,
            'points' => 50,
            'user_id' => $developer->id,
        ]);

        $playerAchievement = PlayerAchievement::create([
            'user_id' => $developer->id, // !! developer's own unlock
            'achievement_id' => $achievement->id,
            'unlocked_at' => Carbon::now(),
        ]);

        // Act
        $this->action->execute($developer, $achievement, $playerAchievement, true);
        $developer->refresh();

        // Assert
        $this->assertEquals(5, $developer->yield_unlocks); // !! unchanged
        $this->assertEquals(500, $developer->yield_points); // !! unchanged
    }

    public function testItAwardsBadgeOnThresholdCross(): void
    {
        // Arrange
        $developer = User::factory()->create(['yield_unlocks' => 0, 'yield_points' => 950]);
        $player = User::factory()->create();

        $game = $this->seedGame(withHash: false);
        $achievement = Achievement::factory()->promoted()->create([
            'game_id' => $game->id,
            'points' => 100, // this will bring the total to 1050, crossing the 1000 badge requirement threshold
            'user_id' => $developer->id,
        ]);

        $playerAchievement = PlayerAchievement::create([
            'user_id' => $player->id,
            'achievement_id' => $achievement->id,
            'unlocked_at' => Carbon::now(),
        ]);

        // ... they shouldn't already have a badge ...
        $this->assertNull(
            PlayerBadge::where('user_id', $developer->id)
                ->where('award_type', AwardType::AchievementPointsYield)
                ->first()
        );

        // Act
        $this->action->execute($developer, $achievement, $playerAchievement, true);
        $developer->refresh();

        // Assert
        $this->assertEquals(1050, $developer->yield_points);

        // ... verify the badge was awarded ...
        $badge = PlayerBadge::where('user_id', $developer->id)
            ->where('award_type', AwardType::AchievementPointsYield)
            ->first();

        $this->assertNotNull($badge);
        $this->assertEquals(0, $badge->award_key); // !! first tier (1000 points) is stored as 0
    }

    public function testItDoesNotDuplicateBadges(): void
    {
        // Arrange
        $developer = User::factory()->create(['yield_unlocks' => 10, 'yield_points' => 1100]);
        $player = User::factory()->create();

        // ... the developer already has the tier 0 badge (1000 points threshold) ...
        PlayerBadge::create([
            'user_id' => $developer->id,
            'award_type' => AwardType::AchievementPointsYield,
            'award_key' => 0,
            'order_column' => 1,
        ]);

        $game = $this->seedGame(withHash: false);
        $achievement = Achievement::factory()->promoted()->create([
            'game_id' => $game->id,
            'points' => 50,
            'user_id' => $developer->id,
        ]);

        $playerAchievement = PlayerAchievement::create([
            'user_id' => $player->id,
            'achievement_id' => $achievement->id,
            'unlocked_at' => Carbon::now(),
        ]);

        // Act
        $this->action->execute($developer, $achievement, $playerAchievement, true);

        // Assert
        // ... only one badge should exist ...
        $badgeCount = PlayerBadge::where('user_id', $developer->id)
            ->where('award_type', AwardType::AchievementPointsYield)
            ->where('award_key', 0)
            ->count();

        $this->assertEquals(1, $badgeCount);
    }

    public function testItCorrectlyHandlesMaintainerUnlocks(): void
    {
        // Arrange
        $author = User::factory()->create(['yield_unlocks' => 5, 'yield_points' => 500]);
        $maintainer = User::factory()->create(['yield_unlocks' => 10, 'yield_points' => 1000]);
        $player = User::factory()->create();

        $game = $this->seedGame(withHash: false);
        $achievement = Achievement::factory()->promoted()->create([
            'game_id' => $game->id,
            'points' => 50,
            'user_id' => $author->id,
        ]);

        $playerAchievement = PlayerAchievement::create([
            'user_id' => $player->id,
            'achievement_id' => $achievement->id,
            'unlocked_at' => Carbon::now(),
        ]);

        // ... this unlock is credited to the maintainer, not the author ...
        AchievementMaintainerUnlock::create([
            'player_achievement_id' => $playerAchievement->id,
            'maintainer_id' => $maintainer->id,
            'achievement_id' => $achievement->id,
        ]);

        // Act
        $this->action->execute($maintainer, $achievement, $playerAchievement, true);
        $maintainer->refresh();

        // Assert
        $this->assertEquals(11, $maintainer->yield_unlocks);
        $this->assertEquals(1050, $maintainer->yield_points);

        // ... author's stats should not change ...
        $author->refresh();
        $this->assertEquals(5, $author->yield_unlocks);
        $this->assertEquals(500, $author->yield_points);
    }

    public function testItIgnoresSoftcoreToHardcoreUpgrades(): void
    {
        // Arrange
        $developer = User::factory()->create(['yield_unlocks' => 5, 'yield_points' => 500]);
        $player = User::factory()->create();

        $game = $this->seedGame(withHash: false);
        $achievement = Achievement::factory()->promoted()->create([
            'game_id' => $game->id,
            'points' => 50,
            'user_id' => $developer->id,
        ]);

        // ... player already has a softcore unlock ...
        $playerAchievement = PlayerAchievement::create([
            'user_id' => $player->id,
            'achievement_id' => $achievement->id,
            'unlocked_at' => Carbon::now()->subHour(),
            'unlocked_hardcore_at' => Carbon::now(), // !! hardcore upgrade
        ]);

        // Act
        $this->action->execute($developer, $achievement, $playerAchievement, true, true);
        $developer->refresh();

        // Assert
        $this->assertEquals(5, $developer->yield_unlocks); // !! unchanged
        $this->assertEquals(500, $developer->yield_points); // !! unchanged
    }

    public function testItCountsDirectHardcoreUnlocks(): void
    {
        // Arrange
        $developer = User::factory()->create(['yield_unlocks' => 5, 'yield_points' => 500]);
        $player = User::factory()->create();

        $game = $this->seedGame(withHash: false);
        $achievement = Achievement::factory()->promoted()->create([
            'game_id' => $game->id,
            'points' => 50,
            'user_id' => $developer->id,
        ]);

        // ... player unlocked directly in hardcore (both timestamps are the same) ...
        $now = Carbon::now();
        $playerAchievement = PlayerAchievement::create([
            'user_id' => $player->id,
            'achievement_id' => $achievement->id,
            'unlocked_at' => $now,
            'unlocked_hardcore_at' => $now,
        ]);

        // Act
        $this->action->execute($developer, $achievement, $playerAchievement, true, true);
        $developer->refresh();

        // Assert
        $this->assertEquals(6, $developer->yield_unlocks); // !! incremented
        $this->assertEquals(550, $developer->yield_points); // !! incremented
    }

    public function testItDoesNotReAwardBadgeAfterDippingBelowThreshold(): void
    {
        // Arrange
        $developer = User::factory()->create(['yield_unlocks' => 10, 'yield_points' => 1050]);
        $player1 = User::factory()->create();
        $player2 = User::factory()->create();

        // ... developer already has the 1000 point yield badge ...
        PlayerBadge::create([
            'user_id' => $developer->id,
            'award_type' => AwardType::AchievementPointsYield,
            'award_key' => 0, // First tier (1000 points)
            'order_column' => 1,
        ]);

        $game = $this->seedGame(withHash: false);

        // ... an achievement worth 100 points which will soon be reset ...
        $achievement1 = Achievement::factory()->promoted()->create([
            'game_id' => $game->id,
            'points' => 100,
            'user_id' => $developer->id,
        ]);

        // ... an achievement worth 50 points that will be unlocked later ...
        $achievement2 = Achievement::factory()->promoted()->create([
            'game_id' => $game->id,
            'points' => 50,
            'user_id' => $developer->id,
        ]);

        $playerAchievement1 = PlayerAchievement::create([
            'user_id' => $player1->id,
            'achievement_id' => $achievement1->id,
            'unlocked_at' => Carbon::now(),
        ]);

        // Act
        // ... reset the 100 point achievement. this should drop yield to 950 ...
        $this->action->execute($developer, $achievement1, $playerAchievement1, false);
        $developer->refresh();

        $this->assertEquals(950, $developer->yield_points); // !! 950 yield

        // ... unlock the 50 point achievement. this should increase yield to 1000 ...
        $playerAchievement2 = PlayerAchievement::create([
            'user_id' => $player2->id,
            'achievement_id' => $achievement2->id,
            'unlocked_at' => Carbon::now(),
        ]);

        $this->action->execute($developer, $achievement2, $playerAchievement2, true);
        $developer->refresh();

        // Assert
        $this->assertEquals(1000, $developer->yield_points);

        // ... should still only have ONE badge, no duplicates ...
        $badgeCount = PlayerBadge::where('user_id', $developer->id)
            ->where('award_type', AwardType::AchievementPointsYield)
            ->where('award_key', 0)
            ->count();

        $this->assertEquals(1, $badgeCount);
    }

    public function testItPreventsUnderflowWhenDecrementingFromZero(): void
    {
        // Arrange
        $developer = User::factory()->create(['yield_unlocks' => 0, 'yield_points' => 0]);
        $player = User::factory()->create();

        $game = $this->seedGame(withHash: false);
        $achievement = Achievement::factory()->promoted()->create([
            'game_id' => $game->id,
            'points' => 50,
            'user_id' => $developer->id,
        ]);

        $playerAchievement = PlayerAchievement::create([
            'user_id' => $player->id,
            'achievement_id' => $achievement->id,
            'unlocked_at' => Carbon::now(),
        ]);

        // Act
        $this->action->execute($developer, $achievement, $playerAchievement, false);
        $developer->refresh();

        // Assert
        $this->assertEquals(0, $developer->yield_unlocks);
        $this->assertEquals(0, $developer->yield_points);
    }

    public function testItPreventsPartialUnderflowWhenDecrementingYield(): void
    {
        // Arrange
        $developer = User::factory()->create(['yield_unlocks' => 1, 'yield_points' => 25]);
        $player = User::factory()->create();

        $game = $this->seedGame(withHash: false);
        $achievement = Achievement::factory()->promoted()->create([
            'game_id' => $game->id,
            'points' => 50, // !! more points than current yield
            'user_id' => $developer->id,
        ]);

        $playerAchievement = PlayerAchievement::create([
            'user_id' => $player->id,
            'achievement_id' => $achievement->id,
            'unlocked_at' => Carbon::now(),
        ]);

        // Act
        $this->action->execute($developer, $achievement, $playerAchievement, false);
        $developer->refresh();

        // Assert
        $this->assertEquals(0, $developer->yield_unlocks);
        $this->assertEquals(0, $developer->yield_points); // Should be 0, not negative
    }
}
