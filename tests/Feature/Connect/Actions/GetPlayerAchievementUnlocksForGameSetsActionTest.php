<?php

declare(strict_types=1);

namespace Tests\Feature\Connect\Actions;

use App\Connect\Actions\GetPlayerAchievementUnlocksForGameSetsAction;
use App\Models\Achievement;
use App\Models\Game;
use App\Models\PlayerAchievement;
use App\Models\System;
use App\Models\User;
use App\Platform\Actions\AssociateAchievementSetToGameAction;
use App\Platform\Actions\UpsertGameCoreAchievementSetFromLegacyFlagsAction;
use App\Platform\Enums\AchievementSetType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetPlayerAchievementUnlocksForGameSetsActionTest extends TestCase
{
    use RefreshDatabase;

    private System $system;
    private UpsertGameCoreAchievementSetFromLegacyFlagsAction $upsertGameCoreSetAction;
    private AssociateAchievementSetToGameAction $associateAchievementSetToGameAction;

    protected function setUp(): void
    {
        parent::setUp();

        $this->system = System::factory()->create(['ID' => 1, 'Name' => 'NES/Famicom']);
        $this->upsertGameCoreSetAction = new UpsertGameCoreAchievementSetFromLegacyFlagsAction();
        $this->associateAchievementSetToGameAction = new AssociateAchievementSetToGameAction();
    }

    public function testItReturnsEmptyForGameWithNoAchievementSets(): void
    {
        // Arrange
        $user = User::factory()->create();
        $game = Game::factory()->create(['ConsoleID' => $this->system->id]);

        // Act
        $result = (new GetPlayerAchievementUnlocksForGameSetsAction())->execute($user, $game);

        // Assert
        $this->assertEmpty($result);
    }

    public function testItReturnsUnlocksForRegularGame(): void
    {
        // Arrange
        $user = User::factory()->create();
        $game = Game::factory()->create(['ConsoleID' => $this->system->id]);
        $achievement = Achievement::factory()->published()->create(['GameID' => $game->id]);
        $this->upsertGameCoreSetAction->execute($game);

        PlayerAchievement::factory()->create([
            'user_id' => $user->id,
            'achievement_id' => $achievement->id,
            'unlocked_at' => '2024-01-15 10:00:00',
            'unlocked_hardcore_at' => '2024-01-15 11:00:00',
        ]);

        // Act
        $result = (new GetPlayerAchievementUnlocksForGameSetsAction())->execute($user, $game);

        // Assert
        $this->assertCount(1, $result);
        $this->assertArrayHasKey($achievement->id, $result);
        $this->assertEquals('2024-01-15 10:00:00', $result[$achievement->id]['DateEarned']);
        $this->assertEquals('2024-01-15 11:00:00', $result[$achievement->id]['DateEarnedHardcore']);
    }

    public function testItReturnsUnlocksFromMultipleSiblingGames(): void
    {
        // Arrange
        $user = User::factory()->create();

        // ... create two parent games ...
        $parentGame1 = Game::factory()->create(['Title' => 'Parent 1', 'ConsoleID' => $this->system->id]);
        $parentGame2 = Game::factory()->create(['Title' => 'Parent 2', 'ConsoleID' => $this->system->id]);

        // ... create achievements for each parent ...
        $parentAch1 = Achievement::factory()->published()->create(['GameID' => $parentGame1->id]);
        $parentAch2 = Achievement::factory()->published()->create(['GameID' => $parentGame2->id]);

        $this->upsertGameCoreSetAction->execute($parentGame1);
        $this->upsertGameCoreSetAction->execute($parentGame2);

        // ... create the subset game ...
        $subsetGame = Game::factory()->create(['Title' => 'Multi-Parent Subset', 'ConsoleID' => $this->system->id]);
        $subsetAch = Achievement::factory()->published()->create(['GameID' => $subsetGame->id]);
        $this->upsertGameCoreSetAction->execute($subsetGame);

        // ... link the subset to both parents ...
        $this->associateAchievementSetToGameAction->execute($parentGame1, $subsetGame, AchievementSetType::Bonus, 'Bonus');
        $this->associateAchievementSetToGameAction->execute($parentGame2, $subsetGame, AchievementSetType::Bonus, 'Bonus');

        // ... the user has unlocks from both parents and the subset ...
        PlayerAchievement::factory()->create([
            'user_id' => $user->id,
            'achievement_id' => $parentAch1->id, // !! from parent 1
            'unlocked_hardcore_at' => '2024-01-01 10:00:00',
        ]);
        PlayerAchievement::factory()->create([
            'user_id' => $user->id,
            'achievement_id' => $parentAch2->id, // !! from parent 2
            'unlocked_hardcore_at' => '2024-01-02 10:00:00',
        ]);
        PlayerAchievement::factory()->create([
            'user_id' => $user->id,
            'achievement_id' => $subsetAch->id, // !! from the subset itself
            'unlocked_hardcore_at' => '2024-01-03 10:00:00',
        ]);

        // Act
        $result = (new GetPlayerAchievementUnlocksForGameSetsAction())->execute($user, $subsetGame);

        // Assert
        $this->assertCount(3, $result);
        $this->assertArrayHasKey($parentAch1->id, $result);
        $this->assertArrayHasKey($parentAch2->id, $result);
        $this->assertArrayHasKey($subsetAch->id, $result);
    }
}
