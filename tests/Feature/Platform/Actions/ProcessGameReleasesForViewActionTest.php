<?php

declare(strict_types=1);

namespace Tests\Feature\Platform\Actions;

use App\Models\Game;
use App\Models\GameRelease;
use App\Models\System;
use App\Platform\Actions\ProcessGameReleasesForViewAction;
use App\Platform\Enums\GameReleaseRegion;
use App\Platform\Enums\ReleasedAtGranularity;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProcessGameReleasesForViewActionTest extends TestCase
{
    use RefreshDatabase;

    private System $system;
    private ProcessGameReleasesForViewAction $action;

    protected function setUp(): void
    {
        parent::setUp();

        $this->system = System::factory()->create(['ID' => 1, 'Name' => 'NES/Famicom']);
        $this->action = new ProcessGameReleasesForViewAction();
    }

    private function createGameWithReleases(string $title, array $releases): Game
    {
        $game = Game::factory()->create(['Title' => $title, 'ConsoleID' => $this->system->id]);

        // Delete releases created by the factory.
        GameRelease::where('game_id', $game->id)->delete();

        foreach ($releases as $release) {
            GameRelease::factory()->create(array_merge(['game_id' => $game->id], $release));
        }

        return $game->fresh(['releases']);
    }

    public function testItReturnsEmptyArrayWhenNoReleases(): void
    {
        // Arrange
        $game = Game::factory()->create(['Title' => 'Super Mario Bros.', 'ConsoleID' => $this->system->id]);

        // Delete releases created by the factory.
        GameRelease::where('game_id', $game->id)->delete();
        $game = $game->fresh(['releases']);

        // Act
        $result = $this->action->execute($game);

        // Assert
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testItReturnsSingleReleaseWhenOnlyOneExists(): void
    {
        // Arrange
        $game = $this->createGameWithReleases('Super Mario Bros.', [
            [
                'region' => GameReleaseRegion::NorthAmerica,
                'released_at' => Carbon::parse('1985-09-13'),
                'released_at_granularity' => ReleasedAtGranularity::Day,
            ],
        ]);

        // Act
        $result = $this->action->execute($game);

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals(GameReleaseRegion::NorthAmerica, $result[0]->region);
        $this->assertEquals('1985-09-13', $result[0]->released_at->format('Y-m-d'));
    }

    public function testItDeduplicatesByRegionKeepingEarliest(): void
    {
        // Arrange
        $game = $this->createGameWithReleases('Super Mario Bros.', [
            [
                'region' => GameReleaseRegion::Japan,
                'released_at' => Carbon::parse('1985-09-13'),
                'released_at_granularity' => ReleasedAtGranularity::Day,
            ],
            [
                'region' => GameReleaseRegion::Japan,
                'released_at' => Carbon::parse('1986-02-21'), // !! Later release in the same region
                'released_at_granularity' => ReleasedAtGranularity::Day,
            ],
        ]);

        // Act
        $result = $this->action->execute($game);

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals('1985-09-13', $result[0]->released_at->format('Y-m-d'));
    }

    public function testItPrioritizesMoreSpecificDatesWhenDeduplicating(): void
    {
        // Arrange
        $game = $this->createGameWithReleases('Dragon Quest', [
            [
                'region' => GameReleaseRegion::Japan,
                'released_at' => Carbon::parse('1986-01-01'), // !! Year granularity, so becomes 1986-12-31
                'released_at_granularity' => ReleasedAtGranularity::Year,
            ],
            [
                'region' => GameReleaseRegion::Japan,
                'released_at' => Carbon::parse('1986-05-27'), // !! Day granularity
                'released_at_granularity' => ReleasedAtGranularity::Day,
            ],
        ]);

        // Act
        $result = $this->action->execute($game);

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals('1986-05-27', $result[0]->released_at->format('Y-m-d'));
        $this->assertEquals(ReleasedAtGranularity::Day, $result[0]->released_at_granularity);
    }

    public function testItNormalizesWorldwideAndOtherRegions(): void
    {
        // Arrange
        $game = $this->createGameWithReleases('Tetris', [
            [
                'region' => GameReleaseRegion::Worldwide,
                'released_at' => Carbon::parse('1984-06-06'),
                'released_at_granularity' => ReleasedAtGranularity::Day,
            ],
            [
                'region' => GameReleaseRegion::Other,
                'released_at' => Carbon::parse('1985-01-01'), // !! Later date
                'released_at_granularity' => ReleasedAtGranularity::Year,
            ],
        ]);

        // Act
        $result = $this->action->execute($game);

        // Assert
        $this->assertCount(1, $result); // !! Both normalized to 'worldwide'
        $this->assertEquals('1984-06-06', $result[0]->released_at->format('Y-m-d'));
    }

    public function testItSortsReleasesByDateAscending(): void
    {
        // Arrange
        $game = $this->createGameWithReleases('Final Fantasy', [
            [
                'region' => GameReleaseRegion::NorthAmerica,
                'released_at' => Carbon::parse('1990-07-12'),
                'released_at_granularity' => ReleasedAtGranularity::Day,
            ],
            [
                'region' => GameReleaseRegion::Japan,
                'released_at' => Carbon::parse('1987-12-18'),
                'released_at_granularity' => ReleasedAtGranularity::Day,
            ],
            [
                'region' => GameReleaseRegion::Europe,
                'released_at' => Carbon::parse('1991-01-01'),
                'released_at_granularity' => ReleasedAtGranularity::Year,
            ],
        ]);

        // Act
        $result = $this->action->execute($game);

        // Assert
        $this->assertCount(3, $result);
        $this->assertEquals(GameReleaseRegion::Japan, $result[0]->region); // !! 1987-12-18
        $this->assertEquals(GameReleaseRegion::NorthAmerica, $result[1]->region); // !! 1990-07-12
        $this->assertEquals(GameReleaseRegion::Europe, $result[2]->region); // !! 1991-01-01 (end of year)
    }

    public function testItSortsMoreSpecificDatesFirstWhenDatesAreEqual(): void
    {
        // Arrange
        $game = $this->createGameWithReleases('Mega Man', [
            [
                'region' => GameReleaseRegion::Japan,
                'released_at' => Carbon::parse('1987-12-01'),
                'released_at_granularity' => ReleasedAtGranularity::Month, // !! Becomes 1987-12-31
            ],
            [
                'region' => GameReleaseRegion::NorthAmerica,
                'released_at' => Carbon::parse('1987-12-17'),
                'released_at_granularity' => ReleasedAtGranularity::Day, // !! More specific
            ],
        ]);

        // Act
        $result = $this->action->execute($game);

        // Assert
        $this->assertCount(2, $result);
        $this->assertEquals(GameReleaseRegion::NorthAmerica, $result[0]->region); // !! Day granularity comes first
        $this->assertEquals(GameReleaseRegion::Japan, $result[1]->region);
    }

    public function testItHandlesReleasesWithNullDates(): void
    {
        // Arrange
        $game = $this->createGameWithReleases('Mystery Game', [
            [
                'region' => GameReleaseRegion::Japan,
                'released_at' => null,
                'released_at_granularity' => null,
            ],
            [
                'region' => GameReleaseRegion::NorthAmerica,
                'released_at' => Carbon::parse('1990-01-01'),
                'released_at_granularity' => ReleasedAtGranularity::Year,
            ],
            [
                'region' => GameReleaseRegion::Europe,
                'released_at' => null,
                'released_at_granularity' => null,
            ],
        ]);

        // Act
        $result = $this->action->execute($game);

        // Assert
        $this->assertCount(3, $result);
        $this->assertEquals(GameReleaseRegion::NorthAmerica, $result[0]->region); // !! Has date
        $this->assertEquals(GameReleaseRegion::Japan, $result[1]->region); // !! Null dates come after
        $this->assertEquals(GameReleaseRegion::Europe, $result[2]->region);
    }

    public function testItHandlesMultipleGranularitiesCorrectly(): void
    {
        // Arrange
        $game = $this->createGameWithReleases('Zelda', [
            [
                'region' => GameReleaseRegion::Japan,
                'released_at' => Carbon::parse('1986-02-21'),
                'released_at_granularity' => ReleasedAtGranularity::Day,
            ],
            [
                'region' => GameReleaseRegion::NorthAmerica,
                'released_at' => Carbon::parse('1987-08-01'),
                'released_at_granularity' => ReleasedAtGranularity::Month, // !! Becomes 1987-08-31
            ],
            [
                'region' => GameReleaseRegion::Europe,
                'released_at' => Carbon::parse('1987-01-01'),
                'released_at_granularity' => ReleasedAtGranularity::Year, // !! Becomes 1987-12-31
            ],
        ]);

        // Act
        $result = $this->action->execute($game);

        // Assert
        $this->assertCount(3, $result);
        $this->assertEquals(GameReleaseRegion::Japan, $result[0]->region); // !! 1986-02-21
        $this->assertEquals(GameReleaseRegion::NorthAmerica, $result[1]->region); // !! 1987-08-31
        $this->assertEquals(GameReleaseRegion::Europe, $result[2]->region); // !! 1987-12-31
    }

    public function testItHandlesNullRegionsAsWorldwide(): void
    {
        // Arrange
        $game = Game::factory()->create(['Title' => 'Null Region Game', 'ConsoleID' => $this->system->id]);

        GameRelease::where('game_id', $game->id)->delete();
        GameRelease::factory()->create([
            'game_id' => $game->id,
            'region' => null,
            'released_at' => Carbon::parse('1990-01-01'),
            'released_at_granularity' => ReleasedAtGranularity::Year,
        ]);
        GameRelease::factory()->create([
            'game_id' => $game->id,
            'region' => GameReleaseRegion::Worldwide,
            'released_at' => Carbon::parse('1989-06-01'),
            'released_at_granularity' => ReleasedAtGranularity::Month,
        ]);
        $game = $game->fresh(['releases']);

        // Act
        $result = $this->action->execute($game);

        // Assert
        $this->assertCount(1, $result); // !! Both normalized to 'worldwide'
        $this->assertEquals('1989-06-01', $result[0]->released_at->format('Y-m-d'));
    }
}
