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
use App\Platform\Enums\AchievementFlag;
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
        $developer = User::factory()->create(['ContribCount' => 5, 'ContribYield' => 500]);
        $player = User::factory()->create();

        $game = $this->seedGame(withHash: false);
        $achievement = Achievement::factory()->published()->create([
            'GameID' => $game->id,
            'Points' => 50,
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
        $this->assertEquals(6, $developer->ContribCount);
        $this->assertEquals(550, $developer->ContribYield);
    }

    public function testItSuccessfullyDecrementsForResets(): void
    {
        // Arrange
        $developer = User::factory()->create(['ContribCount' => 5, 'ContribYield' => 500]);
        $player = User::factory()->create();

        $game = $this->seedGame(withHash: false);
        $achievement = Achievement::factory()->published()->create([
            'GameID' => $game->id,
            'Points' => 50,
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
        $this->assertEquals(4, $developer->ContribCount);
        $this->assertEquals(450, $developer->ContribYield);
    }

    public function testItIgnoresUnofficialAchievements(): void
    {
        // Arrange
        $developer = User::factory()->create(['ContribCount' => 5, 'ContribYield' => 500]);
        $player = User::factory()->create();

        $game = $this->seedGame(withHash: false);
        $achievement = Achievement::factory()->create([
            'GameID' => $game->id,
            'Points' => 50,
            'user_id' => $developer->id,
            'Flags' => AchievementFlag::Unofficial->value,
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
        $this->assertEquals(5, $developer->ContribCount); // !! unchanged
        $this->assertEquals(500, $developer->ContribYield); // !! unchanged
    }

    public function testItIgnoresDeveloperOwnUnlocks(): void
    {
        // Arrange
        $developer = User::factory()->create(['ContribCount' => 5, 'ContribYield' => 500]);

        $game = $this->seedGame(withHash: false);
        $achievement = Achievement::factory()->published()->create([
            'GameID' => $game->id,
            'Points' => 50,
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
        $this->assertEquals(5, $developer->ContribCount); // !! unchanged
        $this->assertEquals(500, $developer->ContribYield); // !! unchanged
    }

    public function testItAwardsBadgeOnThresholdCross(): void
    {
        // Arrange
        $developer = User::factory()->create(['ContribCount' => 0, 'ContribYield' => 950]);
        $player = User::factory()->create();

        $game = $this->seedGame(withHash: false);
        $achievement = Achievement::factory()->published()->create([
            'GameID' => $game->id,
            'Points' => 100, // this will bring the total to 1050, crossing the 1000 badge requirement threshold
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
                ->where('AwardType', AwardType::AchievementPointsYield)
                ->first()
        );

        // Act
        $this->action->execute($developer, $achievement, $playerAchievement, true);
        $developer->refresh();

        // Assert
        $this->assertEquals(1050, $developer->ContribYield);

        // ... verify the badge was awarded ...
        $badge = PlayerBadge::where('user_id', $developer->id)
            ->where('AwardType', AwardType::AchievementPointsYield)
            ->first();

        $this->assertNotNull($badge);
        $this->assertEquals(0, $badge->AwardData); // !! first tier (1000 points) is stored as 0
    }

    public function testItDoesNotDuplicateBadges(): void
    {
        // Arrange
        $developer = User::factory()->create(['ContribCount' => 10, 'ContribYield' => 1100]);
        $player = User::factory()->create();

        // ... the developer already has the tier 0 badge (1000 points threshold) ...
        PlayerBadge::create([
            'user_id' => $developer->id,
            'AwardType' => AwardType::AchievementPointsYield,
            'AwardData' => 0,
            'DisplayOrder' => 1,
        ]);

        $game = $this->seedGame(withHash: false);
        $achievement = Achievement::factory()->published()->create([
            'GameID' => $game->id,
            'Points' => 50,
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
            ->where('AwardType', AwardType::AchievementPointsYield)
            ->where('AwardData', 0)
            ->count();

        $this->assertEquals(1, $badgeCount);
    }

    public function testItCorrectlyHandlesMaintainerUnlocks(): void
    {
        // Arrange
        $author = User::factory()->create(['ContribCount' => 5, 'ContribYield' => 500]);
        $maintainer = User::factory()->create(['ContribCount' => 10, 'ContribYield' => 1000]);
        $player = User::factory()->create();

        $game = $this->seedGame(withHash: false);
        $achievement = Achievement::factory()->published()->create([
            'GameID' => $game->id,
            'Points' => 50,
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
        $this->assertEquals(11, $maintainer->ContribCount);
        $this->assertEquals(1050, $maintainer->ContribYield);

        // ... author's stats should not change ...
        $author->refresh();
        $this->assertEquals(5, $author->ContribCount);
        $this->assertEquals(500, $author->ContribYield);
    }
}
