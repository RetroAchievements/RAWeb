<?php

declare(strict_types=1);

namespace Tests\Feature\Connect\Actions;

use App\Connect\Actions\ResolveRootGameIdFromGameIdAction;
use App\Models\Achievement;
use App\Models\Game;
use App\Models\System;
use App\Platform\Actions\AssociateAchievementSetToGameAction;
use App\Platform\Actions\UpsertGameCoreAchievementSetFromLegacyFlagsAction;
use App\Platform\Enums\AchievementSetType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResolveRootGameIdFromGameIdActionTest extends TestCase
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

    public function testItReturnsGameIdWhenNoAchievementSets(): void
    {
        // Arrange
        $game = Game::factory()->create(['ConsoleID' => $this->system->id]);

        // Act
        $result = (new ResolveRootGameIdFromGameIdAction())->execute($game->id);

        // Assert
        $this->assertEquals($game->id, $result);
    }

    public function testItReturnsGameIdWhenOnlyCoreSet(): void
    {
        // Arrange
        $game = Game::factory()->create(['ConsoleID' => $this->system->id]);
        Achievement::factory()->count(5)->create(['GameID' => $game->id]);
        $this->upsertGameCoreSetAction->execute($game);

        // Act
        $result = (new ResolveRootGameIdFromGameIdAction())->execute($game->id);

        // Assert
        $this->assertEquals($game->id, $result);
    }

    public function testItIdentifiesParentWhenGameIsSubset(): void
    {
        // Arrange
        // ... create the parent game with a core set ...
        $parentGame = Game::factory()->create(['Title' => 'Main Game', 'ConsoleID' => $this->system->id]);
        Achievement::factory()->count(10)->create(['GameID' => $parentGame->id]);
        $this->upsertGameCoreSetAction->execute($parentGame);

        // ... create the subset game with its own achievements (and core set associated to its game ID) ...
        $subsetGame = Game::factory()->create(['Title' => 'Main Game [Subset - Bonus]', 'ConsoleID' => $this->system->id]);
        Achievement::factory()->count(5)->create(['GameID' => $subsetGame->id]);
        $this->upsertGameCoreSetAction->execute($subsetGame);

        // ... associate the subset's core set to the parent as a bonus type achievement set ...
        $this->associateAchievementSetToGameAction->execute($parentGame, $subsetGame, AchievementSetType::Bonus, 'Bonus');

        // Act
        $result = (new ResolveRootGameIdFromGameIdAction())->execute($subsetGame->id);

        // Assert
        // ... the subset game should return the parent's ID because the parent uses the subset's core set as bonus ...
        $this->assertEquals($parentGame->id, $result);
    }

    public function testItDoesNotIdentifyParentWhenSiblingsShareBonus(): void
    {
        // Arrange
        // ... create two sibling games, each with their own core sets ...
        $sibling1 = Game::factory()->create(['Title' => 'Game A', 'ConsoleID' => $this->system->id]);
        Achievement::factory()->count(10)->create(['GameID' => $sibling1->id]);
        $this->upsertGameCoreSetAction->execute($sibling1);

        $sibling2 = Game::factory()->create(['Title' => 'Game B', 'ConsoleID' => $this->system->id]);
        Achievement::factory()->count(10)->create(['GameID' => $sibling2->id]);
        $this->upsertGameCoreSetAction->execute($sibling2);

        // ... create a shared bonus game ...
        $bonusGame = Game::factory()->create(['Title' => 'Game A | Game B [Subset - Bonus]', 'ConsoleID' => $this->system->id]);
        Achievement::factory()->count(5)->create(['GameID' => $bonusGame->id]);
        $this->upsertGameCoreSetAction->execute($bonusGame);

        // ... both siblings reference the bonus game's core set as a bonus type ...
        $this->associateAchievementSetToGameAction->execute($sibling1, $bonusGame, AchievementSetType::Bonus, 'Bonus');
        $this->associateAchievementSetToGameAction->execute($sibling2, $bonusGame, AchievementSetType::Bonus, 'Bonus');

        // Act
        $result1 = (new ResolveRootGameIdFromGameIdAction())->execute($sibling1->id);
        $result2 = (new ResolveRootGameIdFromGameIdAction())->execute($sibling2->id);

        // Assert
        // ... each sibling should return its own ID, NOT the other sibling's ID ...
        $this->assertEquals($sibling1->id, $result1);
        $this->assertEquals($sibling2->id, $result2);
    }

    public function testItHandlesPokemonBlueRedScenario(): void
    {
        // Arrange
        // ... create Blue and Red as independent games with specific IDs to match production ...
        $blue = Game::factory()->create([
            'ID' => 586,
            'Title' => 'Pokemon Blue Version',
            'ConsoleID' => $this->system->id,
        ]);
        Achievement::factory()->count(90)->create(['GameID' => $blue->id]);
        $this->upsertGameCoreSetAction->execute($blue);

        $red = Game::factory()->create([
            'ID' => 724,
            'Title' => 'Pokemon Red Version',
            'ConsoleID' => $this->system->id,
        ]);
        Achievement::factory()->count(92)->create(['GameID' => $red->id]);
        $this->upsertGameCoreSetAction->execute($red);

        // ... create a shared bonus game ...
        $bonusGame = Game::factory()->create([
            'Title' => 'Pokemon Red Version | Pokemon Blue Version [Subset - Bonus]',
            'ConsoleID' => $this->system->id,
        ]);
        Achievement::factory()->count(10)->create(['GameID' => $bonusGame->id]);
        $this->upsertGameCoreSetAction->execute($bonusGame);

        // ... both Blue and Red reference the bonus set ...
        $this->associateAchievementSetToGameAction->execute($blue, $bonusGame, AchievementSetType::Bonus, 'Bonus');
        $this->associateAchievementSetToGameAction->execute($red, $bonusGame, AchievementSetType::Bonus, 'Bonus');

        // Act
        $blueResult = (new ResolveRootGameIdFromGameIdAction())->execute($blue->id);
        $redResult = (new ResolveRootGameIdFromGameIdAction())->execute($red->id);

        // Assert
        $this->assertEquals(586, $blueResult);
        $this->assertEquals(724, $redResult);
    }
}
