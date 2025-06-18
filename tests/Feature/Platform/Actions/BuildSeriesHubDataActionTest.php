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
        $this->assertCount(3, $result->topGames); // only 3 games with achievements (main game has 0)
        $this->assertEquals(1, $result->additionalGameCount); // 4 total - 3 shown = 1

        // ... verify games are ordered by release date ASC (excluding the main game which has 0 achievements) ...
        $this->assertEquals($game1->id, $result->topGames[0]->id); // 1988-10-09
        $this->assertEquals($game2->id, $result->topGames[1]->id); // 1988-10-23
        $this->assertEquals($game3->id, $result->topGames[2]->id); // 1990-11-21
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

    public function testItCalculatesAdditionalGameCountCorrectly(): void
    {
        // Arrange
        $game = Game::factory()->create([
            'Title' => 'Final Fantasy VII',
            'released_at' => '1997-01-31',
            'achievements_published' => 55,
        ]);
        $seriesHub = GameSet::factory()->create([
            'title' => 'Series - Final Fantasy',
            'type' => GameSetType::Hub,
        ]);

        // ... create games before and after FF7 ...
        $gamesBefore = Game::factory()->count(4)->sequence(
            ['Title' => 'Final Fantasy', 'released_at' => '1987-12-18', 'achievements_published' => 20],
            ['Title' => 'Final Fantasy IV', 'released_at' => '1991-07-19', 'achievements_published' => 25],
            ['Title' => 'Final Fantasy V', 'released_at' => '1992-12-06', 'achievements_published' => 30],
            ['Title' => 'Final Fantasy VI', 'released_at' => '1994-04-02', 'achievements_published' => 35],
        )->create();

        $gamesAfter = Game::factory()->count(3)->sequence(
            ['Title' => 'Final Fantasy VIII', 'released_at' => '1999-02-11', 'achievements_published' => 40],
            ['Title' => 'Final Fantasy IX', 'released_at' => '2000-07-07', 'achievements_published' => 45],
            ['Title' => 'Final Fantasy X', 'released_at' => '2001-07-19', 'achievements_published' => 50],
        )->create();

        $gameIds = [
            ...$gamesBefore->pluck('id')->toArray(),
            $game->id,
            ...$gamesAfter->pluck('id')->toArray(),
        ];

        $seriesHub->games()->attach($gameIds);

        // Act
        $result = (new BuildSeriesHubDataAction())->execute($game);

        // Assert
        $this->assertNotNull($result);
        $this->assertEquals(8, $result->totalGameCount);
        $this->assertCount(5, $result->topGames); // !! shows 5 games centered around FF7
        $this->assertEquals(3, $result->additionalGameCount); // !! 8 - 5 = 3

        // ... verify FF7 is in the middle of the 5 games shown (index 2) ...
        $this->assertEquals($game->id, $result->topGames[2]->id);
    }

    public function testItShowsGamesChronologicallyWhenCurrentGameIsFirstInSeries(): void
    {
        // Arrange
        $seriesHub = GameSet::factory()->create([
            'title' => 'Series - The Legend of Zelda',
            'type' => GameSetType::Hub,
        ]);

        $game1 = Game::factory()->create(['Title' => 'Zelda 1', 'released_at' => '1986-02-21', 'achievements_published' => 10]);
        $game2 = Game::factory()->create(['Title' => 'Zelda 2', 'released_at' => '1987-01-14', 'achievements_published' => 15]);
        $game3 = Game::factory()->create(['Title' => 'Zelda 3', 'released_at' => '1991-11-21', 'achievements_published' => 20]);

        $seriesHub->games()->attach([$game1->id, $game2->id, $game3->id]);

        // Act
        $result = (new BuildSeriesHubDataAction())->execute($game1);

        // Assert
        $this->assertCount(3, $result->topGames);
        $this->assertEquals(0, $result->additionalGameCount);

        $this->assertEquals('Zelda 1', $result->topGames[0]->title);
        $this->assertEquals('Zelda 2', $result->topGames[1]->title);
        $this->assertEquals('Zelda 3', $result->topGames[2]->title);
    }

    public function testItExcludesGamesWithoutAchievementsFromDisplayButCountsThem(): void
    {
        // Arrange
        $seriesHub = GameSet::factory()->create([
            'title' => 'Series - Metroid',
            'type' => GameSetType::Hub,
        ]);

        // ... create 8 games: 5 with achievements, 3 without ...
        $game1 = Game::factory()->create([
            'Title' => 'Metroid',
            'released_at' => '1986-08-06',
            'achievements_published' => 10,
        ]);
        $game2 = Game::factory()->create([
            'Title' => 'Metroid II',
            'released_at' => '1991-11-01',
            'achievements_published' => 0, // !! no achievements
        ]);
        $game3 = Game::factory()->create([
            'Title' => 'Super Metroid',
            'released_at' => '1994-03-19',
            'achievements_published' => 20,
        ]);
        $game4 = Game::factory()->create([
            'Title' => 'Metroid Fusion',
            'released_at' => '2002-11-18',
            'achievements_published' => 0, // !! no achievements
        ]);
        $game5 = Game::factory()->create([
            'Title' => 'Metroid Zero Mission',
            'released_at' => '2004-02-09',
            'achievements_published' => 15,
        ]);
        $game6 = Game::factory()->create([
            'Title' => 'Metroid Prime',
            'released_at' => '2002-11-17',
            'achievements_published' => 25,
        ]);
        $game7 = Game::factory()->create([
            'Title' => 'Metroid Prime 2',
            'released_at' => '2004-11-15',
            'achievements_published' => 30,
        ]);
        $game8 = Game::factory()->create([
            'Title' => 'Metroid Prime 3',
            'released_at' => '2007-08-27',
            'achievements_published' => 0, // !! no achievements
        ]);

        $seriesHub->games()->attach([
            $game1->id, $game2->id, $game3->id, $game4->id,
            $game5->id, $game6->id, $game7->id, $game8->id,
        ]);

        // Act
        $result = (new BuildSeriesHubDataAction())->execute($game3); // !! execute with Super Metroid

        // Assert
        $this->assertEquals(8, $result->totalGameCount); // !! all 8 games counted
        $this->assertCount(5, $result->topGames); // !! shows max 5 games (that have achievements)
        $this->assertEquals(3, $result->additionalGameCount); // !! 8 total - 5 shown = 3

        // ... verify only games with achievements are shown ...
        $displayedTitles = array_map(fn ($g) => $g->title, $result->topGames);
        $this->assertNotContains('Metroid II', $displayedTitles);
        $this->assertNotContains('Metroid Fusion', $displayedTitles);
        $this->assertNotContains('Metroid Prime 3', $displayedTitles);

        // ... verify games are in chronological order ...
        $this->assertContains('Metroid', $displayedTitles);
        $this->assertContains('Super Metroid', $displayedTitles);
    }
}
