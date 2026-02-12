<?php

declare(strict_types=1);

namespace Tests\Feature\Community\Actions;

use App\Community\Actions\FetchGameActivityDataAction;
use App\Community\Data\GameActivitySnapshotData;
use App\Community\Enums\GameActivitySnapshotType;
use App\Community\Enums\TrendingReason;
use App\Models\Event;
use App\Models\Game;
use App\Models\GameActivitySnapshot;
use App\Models\System;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FetchGameActivityDataActionTest extends TestCase
{
    use RefreshDatabase;

    public function testItReturnsEmptyDataWhenNoSnapshots(): void
    {
        // Act
        $result = (new FetchGameActivityDataAction())->execute(GameActivitySnapshotType::Trending);

        // Assert
        $this->assertCount(0, $result);
    }

    public function testItReturnsTrendingGamesFromSnapshots(): void
    {
        // Arrange
        $system = System::factory()->create();

        // ... deliberately inserted out-of-order to verify sorting by score ...
        $game3 = Game::factory()->create(['system_id' => $system->id, 'title' => 'third_trending']);
        $game1 = Game::factory()->create(['system_id' => $system->id, 'title' => 'most_trending']);
        $game4 = Game::factory()->create(['system_id' => $system->id, 'title' => 'fourth_trending']);
        $game2 = Game::factory()->create(['system_id' => $system->id, 'title' => 'second_trending']);
        $game5 = Game::factory()->create(['system_id' => $system->id, 'title' => 'fifth_trending']);

        $snapshotTime = now();

        GameActivitySnapshot::factory()->create([
            'game_id' => $game1->id,
            'type' => GameActivitySnapshotType::Trending,
            'score' => 100.0,
            'trend_multiplier' => 5.0,
            'trending_reason' => TrendingReason::NewSet->value,
            'created_at' => $snapshotTime,
        ]);
        GameActivitySnapshot::factory()->create([
            'game_id' => $game2->id,
            'type' => GameActivitySnapshotType::Trending,
            'score' => 80.0,
            'trend_multiplier' => 4.0,
            'trending_reason' => TrendingReason::GainingTraction->value,
            'created_at' => $snapshotTime,
        ]);
        GameActivitySnapshot::factory()->create([
            'game_id' => $game3->id,
            'type' => GameActivitySnapshotType::Trending,
            'score' => 60.0,
            'trend_multiplier' => 3.0,
            'trending_reason' => TrendingReason::MorePlayers->value,
            'created_at' => $snapshotTime,
        ]);
        GameActivitySnapshot::factory()->create([
            'game_id' => $game4->id,
            'type' => GameActivitySnapshotType::Trending,
            'score' => 40.0,
            'trend_multiplier' => 2.0,
            'trending_reason' => TrendingReason::RenewedInterest->value,
            'created_at' => $snapshotTime,
        ]);
        GameActivitySnapshot::factory()->create([
            'game_id' => $game5->id,
            'type' => GameActivitySnapshotType::Trending,
            'score' => 20.0, // lower, so should not appear in results (we only take the top 4)
            'trend_multiplier' => 1.5,
            'trending_reason' => TrendingReason::MorePlayers->value,
            'created_at' => $snapshotTime,
        ]);

        // Act
        $result = (new FetchGameActivityDataAction())->execute(GameActivitySnapshotType::Trending);

        // Assert
        $this->assertCount(4, $result);
        $this->assertContainsOnlyInstancesOf(GameActivitySnapshotData::class, $result);

        $this->assertEquals('most_trending', $result[0]->game->title);
        $this->assertEquals('second_trending', $result[1]->game->title);
        $this->assertEquals('third_trending', $result[2]->game->title);
        $this->assertEquals('fourth_trending', $result[3]->game->title);

        $this->assertEquals(TrendingReason::NewSet, $result[0]->trendingReason);
        $this->assertEquals(TrendingReason::GainingTraction, $result[1]->trendingReason);
        $this->assertEquals(TrendingReason::MorePlayers, $result[2]->trendingReason);
        $this->assertEquals(TrendingReason::RenewedInterest, $result[3]->trendingReason);
    }

    public function testItReturnsPopularGamesFromSnapshots(): void
    {
        // Arrange
        $system = System::factory()->create();
        $game1 = Game::factory()->create(['system_id' => $system->id, 'title' => 'most_popular']);
        $game2 = Game::factory()->create(['system_id' => $system->id, 'title' => 'second_popular']);

        $snapshotTime = now();

        GameActivitySnapshot::factory()->create([
            'game_id' => $game1->id,
            'type' => GameActivitySnapshotType::Popular,
            'score' => 150,
            'player_count' => 150,
            'created_at' => $snapshotTime,
        ]);
        GameActivitySnapshot::factory()->create([
            'game_id' => $game2->id,
            'type' => GameActivitySnapshotType::Popular,
            'score' => 75,
            'player_count' => 75,
            'created_at' => $snapshotTime,
        ]);

        // Act
        $result = (new FetchGameActivityDataAction())->execute(GameActivitySnapshotType::Popular);

        // Assert
        $this->assertCount(2, $result);
        $this->assertEquals('most_popular', $result[0]->game->title);
        $this->assertEquals(150, $result[0]->playerCount);
        $this->assertNull($result[0]->trendingReason);

        $this->assertEquals('second_popular', $result[1]->game->title);
        $this->assertEquals(75, $result[1]->playerCount);
    }

    public function testItIncludesSnapshotsFromSameBatchSpanningSecondBoundary(): void
    {
        // Arrange
        $system = System::factory()->create();
        $game1 = Game::factory()->create(['system_id' => $system->id, 'title' => 'high_score']);
        $game2 = Game::factory()->create(['system_id' => $system->id, 'title' => 'low_score']);

        // ... simulate a batch insert that spans a second boundary ...
        $batchStart = now();
        GameActivitySnapshot::factory()->create([
            'game_id' => $game1->id,
            'type' => GameActivitySnapshotType::Trending,
            'score' => 196.0,
            'trending_reason' => TrendingReason::MorePlayers->value,
            'created_at' => $batchStart,
        ]);
        GameActivitySnapshot::factory()->create([
            'game_id' => $game2->id,
            'type' => GameActivitySnapshotType::Trending,
            'score' => 16.0,
            'trending_reason' => TrendingReason::NewSet->value,
            'created_at' => $batchStart->copy()->addSecond(),
        ]);

        // Act
        $result = (new FetchGameActivityDataAction())->execute(GameActivitySnapshotType::Trending);

        // Assert
        // ... both snapshots should be included despite different created_at values ...
        $this->assertCount(2, $result);
        $this->assertEquals('high_score', $result[0]->game->title);
        $this->assertEquals('low_score', $result[1]->game->title);
    }

    public function testItUsesOnlyMostRecentSnapshots(): void
    {
        // Arrange
        $system = System::factory()->create();
        $game1 = Game::factory()->create(['system_id' => $system->id, 'title' => 'game_one']);
        $game2 = Game::factory()->create(['system_id' => $system->id, 'title' => 'game_two']);

        GameActivitySnapshot::factory()->create([
            'game_id' => $game1->id,
            'type' => GameActivitySnapshotType::Trending,
            'score' => 100.0,
            'trending_reason' => TrendingReason::NewSet->value,
            'created_at' => now()->subHour(), // older, should be ignored
        ]);
        GameActivitySnapshot::factory()->create([
            'game_id' => $game2->id,
            'type' => GameActivitySnapshotType::Trending,
            'score' => 50.0,
            'trending_reason' => TrendingReason::MorePlayers->value,
            'created_at' => now(),
        ]);

        // Act
        $result = (new FetchGameActivityDataAction())->execute(GameActivitySnapshotType::Trending);

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals('game_two', $result[0]->game->title);
    }

    public function testItIncludesEventDataWhenSnapshotHasEventMeta(): void
    {
        // Arrange
        $system = System::factory()->create();

        $eventGame = Game::factory()->create([
            'system_id' => System::Events,
            'title' => 'Achievement of the Week 2024',
        ]);
        $event = Event::factory()->create(['legacy_game_id' => $eventGame->id]);

        $trendingGame = Game::factory()->create(['system_id' => $system->id, 'title' => 'Dark Cloud']);

        GameActivitySnapshot::factory()->create([
            'game_id' => $trendingGame->id,
            'type' => GameActivitySnapshotType::Trending,
            'score' => 100.0,
            'trend_multiplier' => 5.0,
            'trending_reason' => TrendingReason::MorePlayers->value,
            'meta' => ['event_id' => $event->id],
            'created_at' => now(),
        ]);

        // Act
        $result = (new FetchGameActivityDataAction())->execute(GameActivitySnapshotType::Trending);

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals('Dark Cloud', $result[0]->game->title);
        $this->assertEquals(TrendingReason::MorePlayers, $result[0]->trendingReason);
        $this->assertNotNull($result[0]->event);
        $this->assertEquals($event->id, $result[0]->event->id);
    }

    public function testItReturnsNullEventWhenSnapshotHasNoEventMeta(): void
    {
        // Arrange
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id, 'title' => 'Super Mario Bros']);

        GameActivitySnapshot::factory()->create([
            'game_id' => $game->id,
            'type' => GameActivitySnapshotType::Trending,
            'score' => 50.0,
            'trend_multiplier' => 2.0,
            'trending_reason' => TrendingReason::NewSet->value,
            'created_at' => now(),
        ]);

        // Act
        $result = (new FetchGameActivityDataAction())->execute(GameActivitySnapshotType::Trending);

        // Assert
        $this->assertCount(1, $result);
        $this->assertNull($result[0]->event);
    }
}
