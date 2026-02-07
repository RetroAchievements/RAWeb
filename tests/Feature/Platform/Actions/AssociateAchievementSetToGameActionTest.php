<?php

declare(strict_types=1);

namespace Tests\Feature\Platform\Actions;

use App\Models\Achievement;
use App\Models\Game;
use App\Models\System;
use App\Platform\Actions\AssociateAchievementSetToGameAction;
use App\Platform\Actions\UpsertGameCoreAchievementSetFromLegacyFlagsAction;
use App\Platform\Enums\AchievementSetType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class AssociateAchievementSetToGameActionTest extends TestCase
{
    use RefreshDatabase;

    private System $system;
    private UpsertGameCoreAchievementSetFromLegacyFlagsAction $upsertGameCoreSetAction;
    private AssociateAchievementSetToGameAction $associateAction;

    protected function setUp(): void
    {
        parent::setUp();

        $this->system = System::factory()->create(['id' => 1, 'name' => 'NES/Famicom']);
        $this->upsertGameCoreSetAction = new UpsertGameCoreAchievementSetFromLegacyFlagsAction();
        $this->associateAction = new AssociateAchievementSetToGameAction();
    }

    private function createGameWithAchievements(string $title, int $publishedCount): Game
    {
        $game = Game::factory()->create(['title' => $title, 'system_id' => $this->system->id]);
        Achievement::factory()->promoted()->count($publishedCount)->create(['game_id' => $game->id]);

        return $game;
    }

    public function testItAssociatesAchievementSetAsBonus(): void
    {
        // Arrange
        $baseGame = $this->createGameWithAchievements('Sonic the Hedgehog', 10);
        $subsetGame = $this->createGameWithAchievements('Sonic the Hedgehog [Subset - Bonus]', 5);

        $this->upsertGameCoreSetAction->execute($baseGame);
        $this->upsertGameCoreSetAction->execute($subsetGame);

        // Act
        $this->associateAction->execute($baseGame, $subsetGame, AchievementSetType::Bonus, 'Bonus');

        // Assert
        $this->assertDatabaseHas('game_achievement_sets', [
            'game_id' => $baseGame->id,
            'type' => AchievementSetType::Bonus->value,
            'title' => 'Bonus',
        ]);
    }

    public function testItAssociatesAchievementSetAsSpecialty(): void
    {
        // Arrange
        $baseGame = $this->createGameWithAchievements('Sonic the Hedgehog', 10);
        $subsetGame = $this->createGameWithAchievements('Sonic the Hedgehog [Subset - No Rings]', 5);

        $this->upsertGameCoreSetAction->execute($baseGame);
        $this->upsertGameCoreSetAction->execute($subsetGame);

        // Act
        $this->associateAction->execute($baseGame, $subsetGame, AchievementSetType::Specialty, 'No Rings');

        // Assert
        $this->assertDatabaseHas('game_achievement_sets', [
            'game_id' => $baseGame->id,
            'type' => AchievementSetType::Specialty->value,
            'title' => 'No Rings',
        ]);
    }

    public function testItThrowsWhenAssociatingSpecialtySetToMultipleParents(): void
    {
        // Arrange
        $parentGameA = $this->createGameWithAchievements('Pokemon Red', 10);
        $parentGameB = $this->createGameWithAchievements('Pokemon Blue', 10);
        $subsetGame = $this->createGameWithAchievements('Pokemon Red | Pokemon Blue [Subset - Specialty]', 5);

        $this->upsertGameCoreSetAction->execute($parentGameA);
        $this->upsertGameCoreSetAction->execute($parentGameB);
        $this->upsertGameCoreSetAction->execute($subsetGame);

        // ... the first association should succeed ...
        $this->associateAction->execute($parentGameA, $subsetGame, AchievementSetType::Specialty, 'Specialty');

        // Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Specialty sets cannot be linked to multiple parent games.');

        $this->associateAction->execute($parentGameB, $subsetGame, AchievementSetType::Specialty, 'Specialty'); // act
    }

    public function testItThrowsWhenAssociatingWillBeSpecialtySetToMultipleParents(): void
    {
        // Arrange
        $parentGameA = $this->createGameWithAchievements('Pokemon Red', 10);
        $parentGameB = $this->createGameWithAchievements('Pokemon Blue', 10);
        $subsetGame = $this->createGameWithAchievements('Pokemon Red | Pokemon Blue [Subset - Specialty]', 5);

        $this->upsertGameCoreSetAction->execute($parentGameA);
        $this->upsertGameCoreSetAction->execute($parentGameB);
        $this->upsertGameCoreSetAction->execute($subsetGame);

        // ... the first association should succeed ...
        $this->associateAction->execute($parentGameA, $subsetGame, AchievementSetType::WillBeSpecialty, 'Specialty');

        // // Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Specialty sets cannot be linked to multiple parent games.');

        $this->associateAction->execute($parentGameB, $subsetGame, AchievementSetType::WillBeSpecialty, 'Specialty'); // act
    }

    public function testItAllowsBonusSetToBeLinkedToMultipleParents(): void
    {
        // Arrange
        $parentGameA = $this->createGameWithAchievements('Pokemon Red', 10);
        $parentGameB = $this->createGameWithAchievements('Pokemon Blue', 10);
        $subsetGame = $this->createGameWithAchievements('Pokemon Red | Pokemon Blue [Subset - Bonus]', 5);

        $this->upsertGameCoreSetAction->execute($parentGameA);
        $this->upsertGameCoreSetAction->execute($parentGameB);
        $this->upsertGameCoreSetAction->execute($subsetGame);

        // Act
        $this->associateAction->execute($parentGameA, $subsetGame, AchievementSetType::Bonus, 'Bonus');
        $this->associateAction->execute($parentGameB, $subsetGame, AchievementSetType::Bonus, 'Bonus');

        // Assert
        $this->assertDatabaseHas('game_achievement_sets', [
            'game_id' => $parentGameA->id,
            'type' => AchievementSetType::Bonus->value,
        ]);
        $this->assertDatabaseHas('game_achievement_sets', [
            'game_id' => $parentGameB->id,
            'type' => AchievementSetType::Bonus->value,
        ]);
    }

    public function testItThrowsWhenAddingBonusToSetAlreadyLinkedAsSpecialty(): void
    {
        // Arrange
        $parentGameA = $this->createGameWithAchievements('Pokemon Red', 10);
        $parentGameB = $this->createGameWithAchievements('Pokemon Blue', 10);
        $subsetGame = $this->createGameWithAchievements('Pokemon [Subset - Challenge]', 5);

        $this->upsertGameCoreSetAction->execute($parentGameA);
        $this->upsertGameCoreSetAction->execute($parentGameB);
        $this->upsertGameCoreSetAction->execute($subsetGame);

        // ... first, link  it as specialty to Game A ...
        $this->associateAction->execute($parentGameA, $subsetGame, AchievementSetType::Specialty, 'Challenge');

        // Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('This set is already linked as a Specialty set to another game');

        // ... trying to link it as bonus to Game B should fail ...
        $this->associateAction->execute($parentGameB, $subsetGame, AchievementSetType::Bonus, 'Challenge'); // act
    }
}
