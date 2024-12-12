<?php

declare(strict_types=1);

namespace Tests\Feature\Platform\Action;

use App\Models\GameSet;
use App\Models\GameSetLink;
use App\Platform\Actions\BuildHubBreadcrumbsAction;
use App\Platform\Data\GameSetData;
use App\Platform\Enums\GameSetType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class BuildHubBreadcrumbsActionTest extends TestCase
{
    use RefreshDatabase;

    private BuildHubBreadcrumbsAction $action;

    protected function setUp(): void
    {
        parent::setUp();

        $this->action = new BuildHubBreadcrumbsAction();
    }

    private function createHub(string $title): GameSet
    {
        return GameSet::factory()->create([
            'title' => $title,
            'type' => GameSetType::Hub,
            'image_asset_path' => '/Images/000001.png',
            'updated_at' => Carbon::parse('2024-01-01'),
        ]);
    }

    public function assertBreadcrumb(GameSetData $breadcrumb, int $id, string $title): void
    {
        $this->assertEquals($id, $breadcrumb->id);
        $this->assertEquals($title, $breadcrumb->title);
    }

    public function testItReturnsSingleItemForCentralHub(): void
    {
        // Arrange
        $centralHub = $this->createHub('[Central]');
        $centralHub->id = GameSet::CentralHubId;
        $centralHub->save();

        // Act
        $breadcrumbs = $this->action->execute($centralHub);

        // Assert
        $this->assertCount(1, $breadcrumbs);
        $this->assertBreadcrumb($breadcrumbs[0], GameSet::CentralHubId, '[Central]');
    }

    public function testItHandlesThemeHubHierarchy(): void
    {
        // Arrange
        $centralHub = $this->createHub('[Central]');
        $centralHub->id = GameSet::CentralHubId;
        $centralHub->save();

        $centralThemeHub = $this->createHub('[Central - Theme]');
        $themeHub = $this->createHub('[Theme - Horror]');

        GameSetLink::factory()->create([
            'parent_game_set_id' => $centralThemeHub->id,
            'child_game_set_id' => $themeHub->id,
        ]);

        // Act
        $breadcrumbs = $this->action->execute($themeHub);

        // Assert
        $this->assertCount(3, $breadcrumbs);
        $this->assertBreadcrumb($breadcrumbs[0], GameSet::CentralHubId, '[Central]');
        $this->assertBreadcrumb($breadcrumbs[1], $centralThemeHub->id, '[Central - Theme]');
        $this->assertBreadcrumb($breadcrumbs[2], $themeHub->id, '[Theme - Horror]');
    }

    public function testItHandlesDifficultyHubHierarchy(): void
    {
        // Arrange
        $centralHub = $this->createHub('[Central]');
        $centralHub->id = GameSet::CentralHubId;
        $centralHub->save();

        $centralDifficultyHub = $this->createHub('[Central - Difficulty]');
        $difficultyHub = $this->createHub('[Difficulty - Hard]');

        GameSetLink::factory()->create([
            'parent_game_set_id' => $centralDifficultyHub->id,
            'child_game_set_id' => $difficultyHub->id,
        ]);

        // Act
        $breadcrumbs = $this->action->execute($difficultyHub);

        // Assert
        $this->assertCount(3, $breadcrumbs);
        $this->assertBreadcrumb($breadcrumbs[0], GameSet::CentralHubId, '[Central]');
        $this->assertBreadcrumb($breadcrumbs[1], $centralDifficultyHub->id, '[Central - Difficulty]');
        $this->assertBreadcrumb($breadcrumbs[2], $difficultyHub->id, '[Difficulty - Hard]');
    }

    public function testItHandlesStandardHierarchy(): void
    {
        // Arrange
        $centralHub = $this->createHub('[Central]');
        $centralHub->id = GameSet::CentralHubId;
        $centralHub->save();

        $metaHub = $this->createHub('[Meta - Subcategory]');
        $devCompHub = $this->createHub('[Meta|DevComp - Testing]');

        GameSetLink::factory()->create([
            'parent_game_set_id' => $metaHub->id,
            'child_game_set_id' => $devCompHub->id,
        ]);

        GameSetLink::factory()->create([
            'parent_game_set_id' => $centralHub->id,
            'child_game_set_id' => $metaHub->id,
        ]);

        // Act
        $breadcrumbs = $this->action->execute($devCompHub);

        // Assert
        $this->assertCount(3, $breadcrumbs);
        $this->assertBreadcrumb($breadcrumbs[0], GameSet::CentralHubId, '[Central]');
        $this->assertBreadcrumb($breadcrumbs[1], $metaHub->id, '[Meta - Subcategory]');
        $this->assertBreadcrumb($breadcrumbs[2], $devCompHub->id, '[Meta|DevComp - Testing]');
    }

    public function testItHandlesCustomParentChain(): void
    {
        // Arrange
        $centralHub = $this->createHub('[Central]');
        $centralHub->id = GameSet::CentralHubId;
        $centralHub->save();

        $genreHub = $this->createHub('[Genre - Action]');
        $subgenreHub = $this->createHub('[Genre - Action Shooter]');

        GameSetLink::factory()->create([
            'parent_game_set_id' => $genreHub->id,
            'child_game_set_id' => $subgenreHub->id,
        ]);

        GameSetLink::factory()->create([
            'parent_game_set_id' => $centralHub->id,
            'child_game_set_id' => $genreHub->id,
        ]);

        // Act
        $breadcrumbs = $this->action->execute($subgenreHub);

        // Assert
        $this->assertCount(3, $breadcrumbs);
        $this->assertBreadcrumb($breadcrumbs[0], GameSet::CentralHubId, '[Central]');
        $this->assertBreadcrumb($breadcrumbs[1], $genreHub->id, '[Genre - Action]');
        $this->assertBreadcrumb($breadcrumbs[2], $subgenreHub->id, '[Genre - Action Shooter]');
    }

    public function testItHandlesTypeMappings(): void
    {
        // Arrange
        $centralHub = $this->createHub('[Central]');
        $centralHub->id = GameSet::CentralHubId;
        $centralHub->save();

        $miscHub = $this->createHub('[Misc. - Random]');
        $centralMiscHub = $this->createHub('[Central - Miscellaneous]');

        GameSetLink::factory()->create([
            'parent_game_set_id' => $centralMiscHub->id,
            'child_game_set_id' => $miscHub->id,
        ]);

        GameSetLink::factory()->create([
            'parent_game_set_id' => $centralHub->id,
            'child_game_set_id' => $centralMiscHub->id,
        ]);

        // Act
        $breadcrumbs = $this->action->execute($miscHub);

        // Assert
        $this->assertCount(3, $breadcrumbs);
        $this->assertBreadcrumb($breadcrumbs[0], GameSet::CentralHubId, '[Central]');
        $this->assertBreadcrumb($breadcrumbs[1], $centralMiscHub->id, '[Central - Miscellaneous]');
        $this->assertBreadcrumb($breadcrumbs[2], $miscHub->id, '[Misc. - Random]');
    }

    public function testItHandlesInvalidHubTitleFormat(): void
    {
        // Arrange
        $centralHub = $this->createHub('[Central]');
        $centralHub->id = GameSet::CentralHubId;
        $centralHub->save();

        $invalidHub = $this->createHub('Invalid Title Format');

        // Act
        $breadcrumbs = $this->action->execute($invalidHub);

        // Assert
        $this->assertCount(2, $breadcrumbs);
        $this->assertBreadcrumb($breadcrumbs[0], GameSet::CentralHubId, '[Central]');
        $this->assertBreadcrumb($breadcrumbs[1], $invalidHub->id, 'Invalid Title Format');
    }

    public function testItSkipsDeletedHubs(): void
    {
        // Arrange
        $centralHub = $this->createHub('[Central]');
        $centralHub->id = GameSet::CentralHubId;
        $centralHub->save();

        $parentHub = $this->createHub('[Genre - RPG]');
        $childHub = $this->createHub('[Genre - Action RPG]');

        GameSetLink::factory()->create([
            'parent_game_set_id' => $parentHub->id,
            'child_game_set_id' => $childHub->id,
        ]);

        // ... soft delete the parent ...
        $parentHub->delete();

        // Act
        $breadcrumbs = $this->action->execute($childHub);

        // Assert - Should still include Central hub
        $this->assertCount(2, $breadcrumbs);
        $this->assertBreadcrumb($breadcrumbs[0], GameSet::CentralHubId, '[Central]');
        $this->assertBreadcrumb($breadcrumbs[1], $childHub->id, '[Genre - Action RPG]');
    }

    public function testItHandlesMultipleParentLinks(): void
    {
        // Arrange
        $centralHub = $this->createHub('[Central]');
        $centralHub->id = GameSet::CentralHubId;
        $centralHub->save();

        $parent1 = $this->createHub('[Genre - RPG]');
        $parent2 = $this->createHub('[Genre - Action]');
        $childHub = $this->createHub('[Genre - Action RPG]');

        GameSetLink::factory()->create([
            'parent_game_set_id' => $parent1->id,
            'child_game_set_id' => $childHub->id,
        ]);
        GameSetLink::factory()->create([
            'parent_game_set_id' => $parent2->id,
            'child_game_set_id' => $childHub->id,
        ]);

        // Act
        $breadcrumbs = $this->action->execute($childHub);

        // Assert
        $this->assertCount(3, $breadcrumbs);
        $this->assertBreadcrumb($breadcrumbs[0], GameSet::CentralHubId, '[Central]');
        $this->assertBreadcrumb($breadcrumbs[1], $parent1->id, '[Genre - RPG]');
        $this->assertBreadcrumb($breadcrumbs[2], $childHub->id, '[Genre - Action RPG]');
    }

    public function testItHandlesNestedMiscHubHierarchy(): void
    {
        // Arrange
        $centralHub = $this->createHub('[Central]');
        $centralHub->id = GameSet::CentralHubId;
        $centralHub->save();

        $centralMiscHub = $this->createHub('[Central - Miscellaneous]');
        $parentHub = $this->createHub('[Misc. - Console Variants]');
        $childHub = $this->createHub('[Misc. - Console Variants - Greatest Hits]');

        GameSetLink::factory()->create([
            'parent_game_set_id' => $centralMiscHub->id,
            'child_game_set_id' => $parentHub->id,
        ]);

        GameSetLink::factory()->create([
            'parent_game_set_id' => $parentHub->id,
            'child_game_set_id' => $childHub->id,
        ]);

        // Act
        $breadcrumbs = $this->action->execute($childHub);

        // Assert
        $this->assertCount(4, $breadcrumbs);
        $this->assertBreadcrumb($breadcrumbs[0], GameSet::CentralHubId, '[Central]');
        $this->assertBreadcrumb($breadcrumbs[1], $centralMiscHub->id, '[Central - Miscellaneous]');
        $this->assertBreadcrumb($breadcrumbs[2], $parentHub->id, '[Misc. - Console Variants]');
        $this->assertBreadcrumb($breadcrumbs[3], $childHub->id, '[Misc. - Console Variants - Greatest Hits]');
    }

    public function testItIgnoresUnrelatedSubgenreLinks(): void
    {
        // Arrange
        $centralHub = $this->createHub('[Central]');
        $centralHub->id = GameSet::CentralHubId;
        $centralHub->save();

        $centralGenreHub = $this->createHub('[Central - Genre & Subgenre]');
        $sudokuHub = $this->createHub('[Subgenre - Sudoku]');
        $sokobanHub = $this->createHub('[Subgenre - Sokoban]');
        $logicHub = $this->createHub('[Subgenre - Logic Puzzle]');

        GameSetLink::factory()->create([
            'parent_game_set_id' => $logicHub->id,
            'child_game_set_id' => $sudokuHub->id,
        ]);
        GameSetLink::factory()->create([
            'parent_game_set_id' => $sokobanHub->id,
            'child_game_set_id' => $sudokuHub->id,
        ]);

        GameSetLink::factory()->create([
            'parent_game_set_id' => $centralGenreHub->id,
            'child_game_set_id' => $sudokuHub->id,
        ]);

        // Act
        $breadcrumbs = $this->action->execute($sudokuHub);

        // Assert
        $this->assertCount(3, $breadcrumbs);
        $this->assertBreadcrumb($breadcrumbs[0], GameSet::CentralHubId, '[Central]');
        $this->assertBreadcrumb($breadcrumbs[1], $centralGenreHub->id, '[Central - Genre & Subgenre]');
        $this->assertBreadcrumb($breadcrumbs[2], $sudokuHub->id, '[Subgenre - Sudoku]');
    }

    public function testItHandlesSimpleMiscHubDirectly(): void
    {
        // Arrange
        $centralHub = $this->createHub('[Central]');
        $centralHub->id = GameSet::CentralHubId;
        $centralHub->save();

        $centralMiscHub = $this->createHub('[Central - Miscellaneous]');
        $simpleHub = $this->createHub('[Misc. - Greatest Hits]');
        $unrelatedHub = $this->createHub('[Misc. - Arcade]');

        GameSetLink::factory()->create([
            'parent_game_set_id' => $unrelatedHub->id,
            'child_game_set_id' => $simpleHub->id,
        ]);

        GameSetLink::factory()->create([
            'parent_game_set_id' => $centralMiscHub->id,
            'child_game_set_id' => $simpleHub->id,
        ]);

        // Act
        $breadcrumbs = $this->action->execute($simpleHub);

        // Assert
        $this->assertCount(3, $breadcrumbs);
        $this->assertBreadcrumb($breadcrumbs[0], GameSet::CentralHubId, '[Central]');
        $this->assertBreadcrumb($breadcrumbs[1], $centralMiscHub->id, '[Central - Miscellaneous]');
        $this->assertBreadcrumb($breadcrumbs[2], $simpleHub->id, '[Misc. - Greatest Hits]');
    }

    public function testItHandlesSimilarTitledMiscHubsIndependently(): void
    {
        // Arrange
        $centralHub = $this->createHub('[Central]');
        $centralHub->id = GameSet::CentralHubId;
        $centralHub->save();

        $centralMiscHub = $this->createHub('[Central - Miscellaneous]');

        $ps2Hub = $this->createHub('[Misc. - PlayStation 2 - Greatest Hits]');
        $psHub = $this->createHub('[Misc. - PlayStation - Greatest Hits]');

        GameSetLink::factory()->create([
            'parent_game_set_id' => $psHub->id,
            'child_game_set_id' => $ps2Hub->id,
        ]);

        GameSetLink::factory()->create([
            'parent_game_set_id' => $centralMiscHub->id,
            'child_game_set_id' => $ps2Hub->id,
        ]);
        GameSetLink::factory()->create([
            'parent_game_set_id' => $centralMiscHub->id,
            'child_game_set_id' => $psHub->id,
        ]);

        // Act
        $ps2Breadcrumbs = $this->action->execute($ps2Hub);

        // Assert
        $this->assertCount(3, $ps2Breadcrumbs);
        $this->assertBreadcrumb($ps2Breadcrumbs[0], GameSet::CentralHubId, '[Central]');
        $this->assertBreadcrumb($ps2Breadcrumbs[1], $centralMiscHub->id, '[Central - Miscellaneous]');
        $this->assertBreadcrumb($ps2Breadcrumbs[2], $ps2Hub->id, '[Misc. - PlayStation 2 - Greatest Hits]');
    }

    public function testItMaintainsEventHierarchyForSubHubs(): void
    {
        // Arrange
        $centralHub = $this->createHub('[Central]');
        $centralHub->id = GameSet::CentralHubId;
        $centralHub->save();

        $centralEventsHub = $this->createHub('[Central - Community Events]');
        $eventHub = $this->createHub('[Events - The Unwanted]');
        $pastHub = $this->createHub('[The Unwanted - Past Games]');

        GameSetLink::factory()->create([
            'parent_game_set_id' => $centralHub->id,
            'child_game_set_id' => $centralEventsHub->id,
        ]);
        GameSetLink::factory()->create([
            'parent_game_set_id' => $centralEventsHub->id,
            'child_game_set_id' => $eventHub->id,
        ]);
        GameSetLink::factory()->create([
            'parent_game_set_id' => $eventHub->id,
            'child_game_set_id' => $pastHub->id,
        ]);

        // Act
        $breadcrumbs = $this->action->execute($pastHub);

        // Assert
        $this->assertCount(4, $breadcrumbs);
        $this->assertBreadcrumb($breadcrumbs[0], GameSet::CentralHubId, '[Central]');
        $this->assertBreadcrumb($breadcrumbs[1], $centralEventsHub->id, '[Central - Community Events]');
        $this->assertBreadcrumb($breadcrumbs[2], $eventHub->id, '[Events - The Unwanted]');
        $this->assertBreadcrumb($breadcrumbs[3], $pastHub->id, '[The Unwanted - Past Games]');

        // ... also test navigation directly to the event hub ...
        $eventBreadcrumbs = $this->action->execute($eventHub);

        $this->assertCount(3, $eventBreadcrumbs);
        $this->assertBreadcrumb($eventBreadcrumbs[0], GameSet::CentralHubId, '[Central]');
        $this->assertBreadcrumb($eventBreadcrumbs[1], $centralEventsHub->id, '[Central - Community Events]');
        $this->assertBreadcrumb($eventBreadcrumbs[2], $eventHub->id, '[Events - The Unwanted]');
    }
}
