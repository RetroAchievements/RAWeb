<?php

declare(strict_types=1);

namespace Tests\Feature\Platform\Actions;

use App\Models\Achievement;
use App\Models\AchievementSet;
use App\Models\Game;
use App\Models\GameHash;
use App\Models\System;
use App\Platform\Actions\AssociateAchievementSetToGameAction;
use App\Platform\Actions\ResolveAchievementSetGameHashesAction;
use App\Platform\Actions\UpsertGameCoreAchievementSetFromLegacyFlagsAction;
use App\Platform\Enums\AchievementSetType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResolveAchievementSetGameHashesActionTest extends TestCase
{
    use RefreshDatabase;

    private System $system;
    private UpsertGameCoreAchievementSetFromLegacyFlagsAction $upsertGameCoreSetAction;
    private AssociateAchievementSetToGameAction $associateAchievementSetToGameAction;

    protected function setUp(): void
    {
        parent::setUp();

        $this->system = System::factory()->create(['id' => 1, 'name' => 'NES/Famicom']);
        $this->upsertGameCoreSetAction = new UpsertGameCoreAchievementSetFromLegacyFlagsAction();
        $this->associateAchievementSetToGameAction = new AssociateAchievementSetToGameAction();
    }

    private function createGameWithAchievements(string $title, int $publishedCount): Game
    {
        $game = Game::factory()->create(['title' => $title, 'system_id' => $this->system->id]);
        Achievement::factory()->promoted()->count($publishedCount)->create(['game_id' => $game->id]);

        return $game;
    }

    public function testItReturnsEmptyForSetWithNoLinks(): void
    {
        // Arrange
        $achievementSet = AchievementSet::factory()->create();

        // Act
        $result = (new ResolveAchievementSetGameHashesAction())->execute($achievementSet);

        // Assert
        $this->assertTrue($result->isEmpty());
    }

    public function testItReturnsBaseGameHashesForCoreSet(): void
    {
        // Arrange
        $baseGame = $this->createGameWithAchievements('Dragon Quest III', 5);
        $this->upsertGameCoreSetAction->execute($baseGame);

        $hash1 = GameHash::factory()->create(['game_id' => $baseGame->id]);
        $hash2 = GameHash::factory()->create(['game_id' => $baseGame->id]);

        $achievementSet = $baseGame->gameAchievementSets()->core()->first()->achievementSet;

        // Act
        $result = (new ResolveAchievementSetGameHashesAction())->execute($achievementSet);

        // Assert
        $this->assertCount(2, $result);
        $this->assertTrue($result->contains('id', $hash1->id));
        $this->assertTrue($result->contains('id', $hash2->id));
    }

    public function testCoreSetReturnsBaseAndSubsetGameHashes(): void
    {
        // Arrange
        $baseGame = $this->createGameWithAchievements('Dragon Quest III', 5);
        $bonusGame = $this->createGameWithAchievements('Dragon Quest III [Subset - Bonus]', 3);
        $specialtyGame = $this->createGameWithAchievements('Dragon Quest III [Subset - Specialty]', 2);

        $this->upsertGameCoreSetAction->execute($baseGame);
        $this->upsertGameCoreSetAction->execute($bonusGame);
        $this->upsertGameCoreSetAction->execute($specialtyGame);

        $this->associateAchievementSetToGameAction->execute($baseGame, $bonusGame, AchievementSetType::Bonus, 'Bonus');
        $this->associateAchievementSetToGameAction->execute($baseGame, $specialtyGame, AchievementSetType::Specialty, 'Specialty');

        $baseHash = GameHash::factory()->create(['game_id' => $baseGame->id]);
        $bonusHash = GameHash::factory()->create(['game_id' => $bonusGame->id]);
        $specialtyHash = GameHash::factory()->create(['game_id' => $specialtyGame->id]);

        $achievementSet = $baseGame->gameAchievementSets()->core()->first()->achievementSet;

        // Act
        $result = (new ResolveAchievementSetGameHashesAction())->execute($achievementSet);

        // Assert
        $this->assertCount(3, $result);
        $this->assertTrue($result->contains('id', $baseHash->id));
        $this->assertTrue($result->contains('id', $bonusHash->id));
        $this->assertTrue($result->contains('id', $specialtyHash->id));
    }

    public function testBonusSetReturnsBaseAndSubsetGameHashes(): void
    {
        // Arrange
        $baseGame = $this->createGameWithAchievements('Dragon Quest III', 5);
        $bonusGame = $this->createGameWithAchievements('Dragon Quest III [Subset - Bonus]', 3);
        $specialtyGame = $this->createGameWithAchievements('Dragon Quest III [Subset - Specialty]', 2);

        $this->upsertGameCoreSetAction->execute($baseGame);
        $this->upsertGameCoreSetAction->execute($bonusGame);
        $this->upsertGameCoreSetAction->execute($specialtyGame);

        $this->associateAchievementSetToGameAction->execute($baseGame, $bonusGame, AchievementSetType::Bonus, 'Bonus');
        $this->associateAchievementSetToGameAction->execute($baseGame, $specialtyGame, AchievementSetType::Specialty, 'Specialty');

        $baseHash = GameHash::factory()->create(['game_id' => $baseGame->id]);
        $bonusHash = GameHash::factory()->create(['game_id' => $bonusGame->id]);
        $specialtyHash = GameHash::factory()->create(['game_id' => $specialtyGame->id]);

        $achievementSet = $bonusGame->gameAchievementSets()->core()->first()->achievementSet;

        // Act
        $result = (new ResolveAchievementSetGameHashesAction())->execute($achievementSet);

        // Assert
        $this->assertCount(3, $result);
        $this->assertTrue($result->contains('id', $baseHash->id));
        $this->assertTrue($result->contains('id', $bonusHash->id));
        $this->assertTrue($result->contains('id', $specialtyHash->id));
    }

    public function testSpecialtySetReturnsOnlySpecialtyGameHashes(): void
    {
        // Arrange
        $baseGame = $this->createGameWithAchievements('Dragon Quest III', 5);
        $bonusGame = $this->createGameWithAchievements('Dragon Quest III [Subset - Bonus]', 3);
        $specialtyGame = $this->createGameWithAchievements('Dragon Quest III [Subset - Specialty]', 2);

        $this->upsertGameCoreSetAction->execute($baseGame);
        $this->upsertGameCoreSetAction->execute($bonusGame);
        $this->upsertGameCoreSetAction->execute($specialtyGame);

        $this->associateAchievementSetToGameAction->execute($baseGame, $bonusGame, AchievementSetType::Bonus, 'Bonus');
        $this->associateAchievementSetToGameAction->execute($baseGame, $specialtyGame, AchievementSetType::Specialty, 'Specialty');

        $baseHash = GameHash::factory()->create(['game_id' => $baseGame->id]);
        $bonusHash = GameHash::factory()->create(['game_id' => $bonusGame->id]);
        $specialtyHash = GameHash::factory()->create(['game_id' => $specialtyGame->id]);

        $achievementSet = $specialtyGame->gameAchievementSets()->core()->first()->achievementSet;

        // Act
        $result = (new ResolveAchievementSetGameHashesAction())->execute($achievementSet);

        // Assert
        $this->assertCount(1, $result);
        $this->assertTrue($result->contains('id', $specialtyHash->id));
        $this->assertFalse($result->contains('id', $baseHash->id));
        $this->assertFalse($result->contains('id', $bonusHash->id));
    }

    public function testExclusiveSetReturnsOnlyExclusiveGameHashes(): void
    {
        // Arrange
        $baseGame = $this->createGameWithAchievements('Dragon Quest III', 5);
        $exclusiveGame = $this->createGameWithAchievements('Dragon Quest III [Subset - Exclusive]', 4);

        $this->upsertGameCoreSetAction->execute($baseGame);
        $this->upsertGameCoreSetAction->execute($exclusiveGame);

        $this->associateAchievementSetToGameAction->execute($baseGame, $exclusiveGame, AchievementSetType::Exclusive, 'Exclusive');

        $baseHash = GameHash::factory()->create(['game_id' => $baseGame->id]);
        $exclusiveHash = GameHash::factory()->create(['game_id' => $exclusiveGame->id]);

        $achievementSet = $exclusiveGame->gameAchievementSets()->core()->first()->achievementSet;

        // Act
        $result = (new ResolveAchievementSetGameHashesAction())->execute($achievementSet);

        // Assert
        $this->assertCount(1, $result);
        $this->assertTrue($result->contains('id', $exclusiveHash->id));
        $this->assertFalse($result->contains('id', $baseHash->id));
    }

    public function testItHandlesMultipleBonusSets(): void
    {
        // Arrange
        $baseGame = $this->createGameWithAchievements('Dragon Quest III', 5);
        $bonusGame1 = $this->createGameWithAchievements('Dragon Quest III [Subset - Bonus 1]', 3);
        $bonusGame2 = $this->createGameWithAchievements('Dragon Quest III [Subset - Bonus 2]', 2);

        $this->upsertGameCoreSetAction->execute($baseGame);
        $this->upsertGameCoreSetAction->execute($bonusGame1);
        $this->upsertGameCoreSetAction->execute($bonusGame2);

        $this->associateAchievementSetToGameAction->execute($baseGame, $bonusGame1, AchievementSetType::Bonus, 'Bonus 1');
        $this->associateAchievementSetToGameAction->execute($baseGame, $bonusGame2, AchievementSetType::Bonus, 'Bonus 2');

        $baseHash = GameHash::factory()->create(['game_id' => $baseGame->id]);
        $bonus1Hash = GameHash::factory()->create(['game_id' => $bonusGame1->id]);
        $bonus2Hash = GameHash::factory()->create(['game_id' => $bonusGame2->id]);

        $achievementSet = $bonusGame1->gameAchievementSets()->core()->first()->achievementSet;

        // Act
        $result = (new ResolveAchievementSetGameHashesAction())->execute($achievementSet);

        // Assert
        $this->assertCount(3, $result);
        $this->assertTrue($result->contains('id', $baseHash->id));
        $this->assertTrue($result->contains('id', $bonus1Hash->id));
        $this->assertTrue($result->contains('id', $bonus2Hash->id));
    }

    public function testItExcludesIncompatibleHashes(): void
    {
        // Arrange
        $baseGame = $this->createGameWithAchievements('Dragon Quest III', 5);
        $this->upsertGameCoreSetAction->execute($baseGame);

        $compatibleHash = GameHash::factory()->create(['game_id' => $baseGame->id]);
        $incompatibleHash = GameHash::factory()->create(['game_id' => $baseGame->id]);

        $achievementSet = $baseGame->gameAchievementSets()->core()->first()->achievementSet;

        // ... mark one hash as incompatible ...
        $achievementSet->incompatibleGameHashes()->attach($incompatibleHash->id);

        // Act
        $result = (new ResolveAchievementSetGameHashesAction())->execute($achievementSet);

        // Assert
        $this->assertCount(1, $result);
        $this->assertTrue($result->contains('id', $compatibleHash->id));
        $this->assertFalse($result->contains('id', $incompatibleHash->id));
    }
}
