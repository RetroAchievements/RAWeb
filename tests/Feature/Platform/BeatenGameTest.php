<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Community\Enums\AwardType;
use App\Platform\Enums\UnlockMode;
use App\Platform\Models\Achievement;
use App\Platform\Models\Game;
use App\Platform\Models\PlayerBadge;
use App\Platform\Models\System;
use App\Site\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class BeatenGameTest extends TestCase
{
    use RefreshDatabase;
    use TestsPlayerAchievements;

    public function testNoProgressionAchievementsAvailable(): void
    {
        // Arrange
        /** @var User $user */
        $user = User::factory()->create();
        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create(['ConsoleID' => $system->ID]);
        $publishedAchievements = Achievement::factory()->published()->count(6)->create(['GameID' => $game->ID]);

        $this->addHardcoreUnlock($user, $publishedAchievements->get(0), Carbon::now());

        // Act
        $beaten = testBeatenGame($game->ID, $user->User, true);

        // Assert
        $this->assertFalse($beaten['isBeatable']);
        $this->assertFalse($beaten['isBeatenSoftcore']);
        $this->assertFalse($beaten['isBeatenHardcore']);
    }

    public function testNoProgressionAchievementsUnlocked(): void
    {
        // Arrange
        /** @var User $user */
        $user = User::factory()->create();
        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create(['ConsoleID' => $system->ID]);

        /** @var Achievement $achievement */
        $achievement = Achievement::factory()->published()->create(['GameID' => $game->ID]);
        Achievement::factory()->published()->progression()->count(6)->create(['GameID' => $game->ID]);

        $this->addHardcoreUnlock($user, $achievement, Carbon::now());

        // Act
        $beaten = testBeatenGame($game->ID, $user->User, true);

        // Assert
        $this->assertTrue($beaten['isBeatable']);
        $this->assertFalse($beaten['isBeatenSoftcore']);
        $this->assertFalse($beaten['isBeatenHardcore']);
    }

    public function testSomeProgressionAchievementsUnlocked(): void
    {
        // Arrange
        /** @var User $user */
        $user = User::factory()->create();
        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create(['ConsoleID' => $system->ID]);

        $progressionAchievements = Achievement::factory()->published()->progression()->count(5)->create(['GameID' => $game->ID]);
        Achievement::factory()->published()->winCondition()->create(['GameID' => $game->ID]);

        $this->addHardcoreUnlock($user, $progressionAchievements->get(0), Carbon::now());
        $this->addHardcoreUnlock($user, $progressionAchievements->get(1), Carbon::now());
        $this->addHardcoreUnlock($user, $progressionAchievements->get(2), Carbon::now());

        // Act
        $beaten = testBeatenGame($game->ID, $user->User, true);

        // Assert
        $this->assertTrue($beaten['isBeatable']);
        $this->assertFalse($beaten['isBeatenSoftcore']);
        $this->assertFalse($beaten['isBeatenHardcore']);
    }

    public function testAllProgressionButNoWinConditionAchievementsUnlocked(): void
    {
        // Arrange
        /** @var User $user */
        $user = User::factory()->create();
        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create(['ConsoleID' => $system->ID]);

        $progressionAchievements = Achievement::factory()->published()->progression()->count(5)->create(['GameID' => $game->ID]);
        Achievement::factory()->published()->winCondition()->create(['GameID' => $game->ID]);

        $this->addHardcoreUnlock($user, $progressionAchievements->get(0), Carbon::now());
        $this->addHardcoreUnlock($user, $progressionAchievements->get(1), Carbon::now());
        $this->addHardcoreUnlock($user, $progressionAchievements->get(2), Carbon::now());
        $this->addHardcoreUnlock($user, $progressionAchievements->get(3), Carbon::now());
        $this->addHardcoreUnlock($user, $progressionAchievements->get(4), Carbon::now());

        // Act
        $beaten = testBeatenGame($game->ID, $user->User, true);

        // Assert
        $this->assertTrue($beaten['isBeatable']);
        $this->assertFalse($beaten['isBeatenSoftcore']);
        $this->assertFalse($beaten['isBeatenHardcore']);
    }

    public function testAllProgressionAchievementsUnlockedAndNoWinConditionExists(): void
    {
                // Arrange
        /** @var User $user */
        $user = User::factory()->create();
        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create(['ConsoleID' => $system->ID]);

        $progressionAchievements = Achievement::factory()->published()->progression()->count(5)->create(['GameID' => $game->ID]);

        $this->addHardcoreUnlock($user, $progressionAchievements->get(0), Carbon::now());
        $this->addHardcoreUnlock($user, $progressionAchievements->get(1), Carbon::now());
        $this->addHardcoreUnlock($user, $progressionAchievements->get(2), Carbon::now());
        $this->addSoftcoreUnlock($user, $progressionAchievements->get(3), Carbon::now());
        $this->addHardcoreUnlock($user, $progressionAchievements->get(4), Carbon::now());

        // Act
        $beaten = testBeatenGame($game->ID, $user->User, true);

        // Assert
        $this->assertTrue($beaten['isBeatable']);
        $this->assertTrue($beaten['isBeatenSoftcore']);
        $this->assertFalse($beaten['isBeatenHardcore']);
    }

    public function testAllProgressionAndOneWinConditionAchievementsUnlocked(): void
    {
        // Arrange
        /** @var User $user */
        $user = User::factory()->create();
        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create(['ConsoleID' => $system->ID]);

        $progressionAchievements = Achievement::factory()->published()->progression()->count(5)->create(['GameID' => $game->ID]);
        $winConditionAchievement = Achievement::factory()->published()->winCondition()->create(['GameID' => $game->ID]);

        $this->addHardcoreUnlock($user, $progressionAchievements->get(0), Carbon::now());
        $this->addHardcoreUnlock($user, $progressionAchievements->get(1), Carbon::now());
        $this->addHardcoreUnlock($user, $progressionAchievements->get(2), Carbon::now());
        $this->addHardcoreUnlock($user, $progressionAchievements->get(3), Carbon::now());
        $this->addHardcoreUnlock($user, $progressionAchievements->get(4), Carbon::now());
        $this->addHardcoreUnlock($user, $winConditionAchievement, Carbon::now());

        // Act
        $beaten = testBeatenGame($game->ID, $user->User, true);

        // Assert
        $this->assertTrue($beaten['isBeatable']);
        $this->assertTrue($beaten['isBeatenSoftcore']);
        $this->assertTrue($beaten['isBeatenHardcore']);
    }

    public function testNoProgressionAndOneWinConditionAchievementUnlocked(): void
    {
        // Arrange
        /** @var User $user */
        $user = User::factory()->create();
        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create(['ConsoleID' => $system->ID]);

        Achievement::factory()->published()->progression()->count(5)->create(['GameID' => $game->ID]);
        $winConditionAchievement = Achievement::factory()->published()->winCondition()->create(['GameID' => $game->ID]);

        $this->addHardcoreUnlock($user, $winConditionAchievement, Carbon::now());

        // Act
        $beaten = testBeatenGame($game->ID, $user->User, true);

        // Assert
        $this->assertTrue($beaten['isBeatable']);
        $this->assertFalse($beaten['isBeatenSoftcore']);
        $this->assertFalse($beaten['isBeatenHardcore']);
    }

    public function testSomeHardcoreAndSomeSoftcoreUnlocks(): void
    {
        // Arrange
        /** @var User $user */
        $user = User::factory()->create();
        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create(['ConsoleID' => $system->ID]);

        $progressionAchievements = Achievement::factory()->published()->progression()->count(5)->create(['GameID' => $game->ID]);
        $winConditionAchievement = Achievement::factory()->published()->winCondition()->create(['GameID' => $game->ID]);

        $this->addSoftcoreUnlock($user, $progressionAchievements->get(0), Carbon::now());
        $this->addHardcoreUnlock($user, $progressionAchievements->get(1), Carbon::now());
        $this->addSoftcoreUnlock($user, $progressionAchievements->get(2), Carbon::now());
        $this->addSoftcoreUnlock($user, $progressionAchievements->get(3), Carbon::now());
        $this->addSoftcoreUnlock($user, $progressionAchievements->get(4), Carbon::now());
        $this->addHardcoreUnlock($user, $winConditionAchievement, Carbon::now());

        // Act
        $beaten = testBeatenGame($game->ID, $user->User, true);

        // Assert
        $this->assertTrue($beaten['isBeatable']);
        $this->assertTrue($beaten['isBeatenSoftcore']);
        $this->assertFalse($beaten['isBeatenHardcore']);
    }

    public function testSoftcoreAwardAssignment(): void
    {
        // Arrange
        /** @var User $user */
        $user = User::factory()->create();
        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create(['ConsoleID' => $system->ID]);

        $progressionAchievements = Achievement::factory()->published()->progression()->count(5)->create(['GameID' => $game->ID]);
        $winConditionAchievement = Achievement::factory()->published()->winCondition()->create(['GameID' => $game->ID]);

        $this->addSoftcoreUnlock($user, $progressionAchievements->get(0), Carbon::now());
        $this->addSoftcoreUnlock($user, $progressionAchievements->get(1), Carbon::now());
        $this->addSoftcoreUnlock($user, $progressionAchievements->get(2), Carbon::now());
        $this->addHardcoreUnlock($user, $progressionAchievements->get(3), Carbon::now());
        $this->addSoftcoreUnlock($user, $progressionAchievements->get(4), Carbon::now());
        $this->addHardcoreUnlock($user, $winConditionAchievement, Carbon::now());

        // Act
        testBeatenGame($game->ID, $user->User, true);

        // Assert
        $this->assertEquals(PlayerBadge::where('User', $user->User)->count(), 1);
        $this->assertNotNull(PlayerBadge::where('User', $user->User)
            ->where('AwardType', AwardType::GameBeaten)
            ->where('AwardData', $game->ID)
            ->where('AwardDataExtra', UnlockMode::Softcore)
            ->where('AwardDate', Carbon::now())
            ->first()
        );
    }

    public function testHardcoreAwardAssignment(): void
    {
        // Arrange
        /** @var User $user */
        $user = User::factory()->create();
        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create(['ConsoleID' => $system->ID]);

        $progressionAchievements = Achievement::factory()->published()->progression()->count(5)->create(['GameID' => $game->ID]);
        $winConditionAchievement = Achievement::factory()->published()->winCondition()->create(['GameID' => $game->ID]);

        $this->addHardcoreUnlock($user, $progressionAchievements->get(0), Carbon::now());
        $this->addHardcoreUnlock($user, $progressionAchievements->get(1), Carbon::now());
        $this->addHardcoreUnlock($user, $progressionAchievements->get(2), Carbon::now());
        $this->addHardcoreUnlock($user, $progressionAchievements->get(3), Carbon::now());
        $this->addHardcoreUnlock($user, $progressionAchievements->get(4), Carbon::now());
        $this->addHardcoreUnlock($user, $winConditionAchievement, Carbon::now());

        // Act
        testBeatenGame($game->ID, $user->User, true);

        // Assert
        $this->assertEquals(PlayerBadge::where('User', $user->User)->count(), 1);
        $this->assertNotNull(PlayerBadge::where('User', $user->User)
            ->where('AwardType', AwardType::GameBeaten)
            ->where('AwardData', $game->ID)
            ->where('AwardDataExtra', UnlockMode::Hardcore)
            ->where('AwardDate', Carbon::now())
            ->first()
        );
    }

    public function testBeatenAwardRevocation(): void
    {
        // Arrange
        /** @var User $user */
        $user = User::factory()->create();
        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create(['ConsoleID' => $system->ID]);

        $progressionAchievements = Achievement::factory()->published()->progression()->count(5)->create(['GameID' => $game->ID]);
        $winConditionAchievement = Achievement::factory()->published()->winCondition()->create(['GameID' => $game->ID]);

        $this->addHardcoreUnlock($user, $progressionAchievements->get(0), Carbon::now());
        $this->addHardcoreUnlock($user, $progressionAchievements->get(1), Carbon::now());
        $this->addHardcoreUnlock($user, $progressionAchievements->get(2), Carbon::now());
        $this->addHardcoreUnlock($user, $progressionAchievements->get(3), Carbon::now());
        $this->addHardcoreUnlock($user, $progressionAchievements->get(4), Carbon::now());
        $this->addHardcoreUnlock($user, $winConditionAchievement, Carbon::now());

        testBeatenGame($game->ID, $user->User, true);

        // Act
        Achievement::factory()->published()->progression()->create(['GameID' => $game->ID]);
        testBeatenGame($game->ID, $user->User, true);

        // Assert
        $this->assertEquals(PlayerBadge::where('User', $user->User)->count(), 0);
    }

    public function testBeatenAwardRevocation2(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create(['ConsoleID' => $system->ID]);

        $progressionAchievements = Achievement::factory()->published()->progression()->count(5)->create(['GameID' => $game->ID]);
        $winConditionAchievement = Achievement::factory()->published()->winCondition()->create(['GameID' => $game->ID]);

        // First, the user will get a softcore beaten award.
        $this->addSoftcoreUnlock($user, $progressionAchievements->get(0), Carbon::now());
        $this->addSoftcoreUnlock($user, $progressionAchievements->get(1), Carbon::now());
        $this->addSoftcoreUnlock($user, $progressionAchievements->get(2), Carbon::now());
        $this->addHardcoreUnlock($user, $progressionAchievements->get(3), Carbon::now());
        $this->addHardcoreUnlock($user, $progressionAchievements->get(4), Carbon::now());
        $this->addHardcoreUnlock($user, $winConditionAchievement, Carbon::now());

        testBeatenGame($game->ID, $user->User, true);

        // Now they'll upgrade it to hardcore by unlocking the remaining achievements in hardcore.
        $this->addHardcoreUnlock($user, $progressionAchievements->get(0), Carbon::now()->addMinutes(5));
        $this->addHardcoreUnlock($user, $progressionAchievements->get(1), Carbon::now()->addMinutes(5));
        $this->addHardcoreUnlock($user, $progressionAchievements->get(2), Carbon::now()->addMinutes(5));

        testBeatenGame($game->ID, $user->User, true);

        // A new achievement gets added and marked as Progression.
        /** @var Achievement $newAchievement */
        $newAchievement = Achievement::factory()->published()->progression()->create(['GameID' => $game->ID]);
        testBeatenGame($game->ID, $user->User, true);
        $this->assertEquals(PlayerBadge::where('User', $user->User)->count(), 0);

        // The user unlocks it in softcore.
        $this->addSoftcoreUnlock($user, $newAchievement, Carbon::now());
        testBeatenGame($game->ID, $user->User, true);
        $this->assertEquals(PlayerBadge::where('User', $user->User)->count(), 1);

        // The user unlocks it in hardcore.
        $this->addHardcoreUnlock($user, $newAchievement, Carbon::now());
        testBeatenGame($game->ID, $user->User, true);
        $this->assertEquals(PlayerBadge::where('User', $user->User)->count(), 2);
    }
}
