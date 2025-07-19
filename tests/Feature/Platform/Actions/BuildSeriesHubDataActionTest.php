<?php

declare(strict_types=1);

namespace Tests\Feature\Platform\Actions;

use App\Models\Game;
use App\Models\GameSet;
use App\Platform\Actions\BuildSeriesHubDataAction;
use App\Platform\Enums\GameSetType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BuildSeriesHubDataActionTest extends TestCase
{
    use RefreshDatabase;

    public function testItReturnsNullWhenGameHasNoHubs(): void
    {
        // Arrange
        $game = Game::factory()->create(['Title' => 'Test Game']);

        // Act
        $result = (new BuildSeriesHubDataAction())->execute($game);

        // Assert
        $this->assertNull($result);
    }

    public function testItReturnsSeriesHubDataWhenGameHasSeriesHub(): void
    {
        // Arrange
        $game = Game::factory()->create([
            'Title' => 'Super Mario Bros.',
            'released_at' => '1985-09-13',
        ]);
        $seriesHub = GameSet::factory()->create([
            'title' => 'Series - Super Mario',
            'type' => GameSetType::Hub,
        ]);

        // ... create some games in the series with release dates ...
        $game1 = Game::factory()->create([
            'Title' => 'Super Mario Bros. 2',
            'released_at' => '1988-10-09',
            'achievements_published' => 10,
            'points_total' => 100,
        ]);
        $game2 = Game::factory()->create([
            'Title' => 'Super Mario Bros. 3',
            'released_at' => '1988-10-23',
            'achievements_published' => 20,
            'points_total' => 200,
        ]);
        $game3 = Game::factory()->create([
            'Title' => 'Super Mario World',
            'released_at' => '1990-11-21',
            'achievements_published' => 30,
            'points_total' => 300,
        ]);
        $subset = Game::factory()->create([
            'Title' => 'Super Mario Bros. [Subset - Hard Mode]',
            'released_at' => '1985-09-13',
            'achievements_published' => 5,
            'points_total' => 50,
        ]);

        // ... attach games to the hub ...
        $seriesHub->games()->attach([$game->id, $game1->id, $game2->id, $game3->id, $subset->id]);

        // Act
        $result = (new BuildSeriesHubDataAction())->execute($game);

        // Assert
        $this->assertNotNull($result);
        $this->assertEquals($seriesHub->id, $result->hub->id);
        $this->assertEquals(4, $result->totalGameCount); // excludes subset
        $this->assertEquals(60, $result->achievementsPublished); // 10+20+30
        $this->assertEquals(600, $result->pointsTotal); // 100+200+300
    }

    public function testItPrefersSubseriesHubOverSeriesHub(): void
    {
        // Arrange
        $game = Game::factory()->create(['Title' => 'Zelda II: The Adventure of Link']);

        $seriesHub = GameSet::factory()->create([
            'title' => 'Series - The Legend of Zelda',
            'type' => GameSetType::Hub,
        ]);

        $subseriesHub = GameSet::factory()->create([
            'title' => 'Subseries - Zelda Classic',
            'type' => GameSetType::Hub,
        ]);

        // ... attach game to both hubs ...
        $seriesHub->games()->attach($game->id);
        $subseriesHub->games()->attach($game->id);

        // Act
        $result = (new BuildSeriesHubDataAction())->execute($game);

        // Assert
        $this->assertNotNull($result);
        $this->assertEquals($subseriesHub->id, $result->hub->id);
    }
}
