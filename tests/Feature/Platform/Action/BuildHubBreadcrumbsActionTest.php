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
}
