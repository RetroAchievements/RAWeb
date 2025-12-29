<?php

declare(strict_types=1);

namespace Tests\Feature\Platform\Actions;

use App\Models\Game;
use App\Models\PlayerStat;
use App\Models\PlayerStatRanking;
use App\Models\System;
use App\Models\User;
use App\Platform\Actions\UpdateBeatenGamesLeaderboardAction;
use App\Platform\Enums\PlayerStatRankingKind;
use App\Platform\Enums\PlayerStatType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateBeatenGamesLeaderboardActionTest extends TestCase
{
    use RefreshDatabase;

    private System $system;
    private UpdateBeatenGamesLeaderboardAction $action;

    protected function setUp(): void
    {
        parent::setUp();

        $this->system = System::factory()->create(['ID' => 1]);
        $this->action = new UpdateBeatenGamesLeaderboardAction();
    }

    public function testItCorrectlyRanksUsersByTotalBeatenGames(): void
    {
        // Arrange
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        PlayerStat::factory()->create([
            'user_id' => $user1->id,
            'system_id' => $this->system->id,
            'type' => PlayerStatType::GamesBeatenHardcoreRetail,
            'value' => 10,
        ]);
        PlayerStat::factory()->create([
            'user_id' => $user2->id,
            'system_id' => $this->system->id,
            'type' => PlayerStatType::GamesBeatenHardcoreRetail,
            'value' => 5,
        ]);
        PlayerStat::factory()->create([
            'user_id' => $user3->id,
            'system_id' => $this->system->id,
            'type' => PlayerStatType::GamesBeatenHardcoreRetail,
            'value' => 15,
        ]);

        // Act
        $this->action->execute($this->system->id, PlayerStatRankingKind::RetailBeaten);

        // Assert
        $rankings = PlayerStatRanking::orderBy('rank_number')->get();

        $this->assertCount(3, $rankings);
        $this->assertEquals($user3->id, $rankings[0]->user_id); // 15 games = rank 1
        $this->assertEquals(1, $rankings[0]->rank_number);
        $this->assertEquals($user1->id, $rankings[1]->user_id); // 10 games = rank 2
        $this->assertEquals(2, $rankings[1]->rank_number);
        $this->assertEquals($user2->id, $rankings[2]->user_id); // 5 games = rank 3
        $this->assertEquals(3, $rankings[2]->rank_number);
    }

    public function testItAssignsSameRankNumberForTies(): void
    {
        // Arrange
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        PlayerStat::factory()->create([
            'user_id' => $user1->id,
            'system_id' => $this->system->id,
            'type' => PlayerStatType::GamesBeatenHardcoreRetail,
            'value' => 10, // !!
            'stat_updated_at' => now()->subDays(2),
        ]);
        PlayerStat::factory()->create([
            'user_id' => $user2->id,
            'system_id' => $this->system->id,
            'type' => PlayerStatType::GamesBeatenHardcoreRetail,
            'value' => 10, // !!
            'stat_updated_at' => now()->subDays(1),
        ]);

        PlayerStat::factory()->create([
            'user_id' => $user3->id,
            'system_id' => $this->system->id,
            'type' => PlayerStatType::GamesBeatenHardcoreRetail,
            'value' => 5,
        ]);

        // Act
        $this->action->execute($this->system->id, PlayerStatRankingKind::RetailBeaten);

        // Assert
        $rankings = PlayerStatRanking::orderBy('row_number')->get();

        $this->assertCount(3, $rankings);

        // ... both tied users should have rank 1 ...
        $this->assertEquals(1, $rankings[0]->rank_number); // rank 1
        $this->assertEquals(1, $rankings[1]->rank_number); // rank 1 (tie)
        $this->assertEquals(3, $rankings[2]->rank_number); // rank 3 (skips 2)
    }

    public function testItUsesRowNumberForTiebreakerOrdering(): void
    {
        // Arrange
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $earlierDate = now()->subDays(5);
        $laterDate = now()->subDays(1);

        PlayerStat::factory()->create([
            'user_id' => $user1->id,
            'system_id' => $this->system->id,
            'type' => PlayerStatType::GamesBeatenHardcoreRetail,
            'value' => 10,
            'stat_updated_at' => $laterDate,
        ]);
        PlayerStat::factory()->create([
            'user_id' => $user2->id,
            'system_id' => $this->system->id,
            'type' => PlayerStatType::GamesBeatenHardcoreRetail,
            'value' => 10,
            'stat_updated_at' => $earlierDate,
        ]);

        // Act
        $this->action->execute($this->system->id, PlayerStatRankingKind::RetailBeaten);

        // Assert
        $rankings = PlayerStatRanking::orderBy('row_number')->get();

        $this->assertCount(2, $rankings);

        // ... $user2 has the earlier date, so they should have a lower row_number ...
        $this->assertEquals($user2->id, $rankings[0]->user_id);
        $this->assertEquals(1, $rankings[0]->row_number);
        $this->assertEquals($user1->id, $rankings[1]->user_id);
        $this->assertEquals(2, $rankings[1]->row_number);
    }

    public function testRetailBeatenIncludesCorrectStatTypes(): void
    {
        // Arrange
        $user = User::factory()->create();

        PlayerStat::factory()->create([
            'user_id' => $user->id,
            'system_id' => $this->system->id,
            'type' => PlayerStatType::GamesBeatenHardcoreRetail,
            'value' => 5, // !!
        ]);
        PlayerStat::factory()->create([
            'user_id' => $user->id,
            'system_id' => $this->system->id,
            'type' => PlayerStatType::GamesBeatenHardcoreUnlicensed,
            'value' => 3, // !!
        ]);
        PlayerStat::factory()->create([
            'user_id' => $user->id,
            'system_id' => $this->system->id,
            'type' => PlayerStatType::GamesBeatenHardcoreHomebrew, // excluded
            'value' => 10,
        ]);
        PlayerStat::factory()->create([
            'user_id' => $user->id,
            'system_id' => $this->system->id,
            'type' => PlayerStatType::GamesBeatenHardcoreHacks, // excluded
            'value' => 20,
        ]);

        // Act
        $this->action->execute($this->system->id, PlayerStatRankingKind::RetailBeaten);

        // Assert
        $ranking = PlayerStatRanking::first();

        $this->assertNotNull($ranking);
        $this->assertEquals(8, $ranking->total); // 5 + 3 = 8
    }

    public function testHomebrewBeatenIncludesOnlyHomebrewStats(): void
    {
        // Arrange
        $user = User::factory()->create();

        PlayerStat::factory()->create([
            'user_id' => $user->id,
            'system_id' => $this->system->id,
            'type' => PlayerStatType::GamesBeatenHardcoreHomebrew,
            'value' => 7,
        ]);
        PlayerStat::factory()->create([
            'user_id' => $user->id,
            'system_id' => $this->system->id,
            'type' => PlayerStatType::GamesBeatenHardcoreRetail, // excluded
            'value' => 100,
        ]);

        // Act
        $this->action->execute($this->system->id, PlayerStatRankingKind::HomebrewBeaten);

        // Assert
        $ranking = PlayerStatRanking::first();

        $this->assertNotNull($ranking);
        $this->assertEquals(7, $ranking->total);
    }

    public function testHacksBeatenIncludesOnlyHacksStats(): void
    {
        // Arrange
        $user = User::factory()->create();

        PlayerStat::factory()->create([
            'user_id' => $user->id,
            'system_id' => $this->system->id,
            'type' => PlayerStatType::GamesBeatenHardcoreHacks,
            'value' => 12,
        ]);
        PlayerStat::factory()->create([
            'user_id' => $user->id,
            'system_id' => $this->system->id,
            'type' => PlayerStatType::GamesBeatenHardcoreRetail, // excluded
            'value' => 50,
        ]);

        // Act
        $this->action->execute($this->system->id, PlayerStatRankingKind::HacksBeaten);

        // Assert
        $ranking = PlayerStatRanking::first();

        $this->assertNotNull($ranking);
        $this->assertEquals(12, $ranking->total);
    }

    public function testAllBeatenIncludesAllStatTypes(): void
    {
        // Arrange
        $user = User::factory()->create();

        PlayerStat::factory()->create([
            'user_id' => $user->id,
            'system_id' => $this->system->id,
            'type' => PlayerStatType::GamesBeatenHardcoreRetail,
            'value' => 1,
        ]);
        PlayerStat::factory()->create([
            'user_id' => $user->id,
            'system_id' => $this->system->id,
            'type' => PlayerStatType::GamesBeatenHardcoreUnlicensed,
            'value' => 2,
        ]);
        PlayerStat::factory()->create([
            'user_id' => $user->id,
            'system_id' => $this->system->id,
            'type' => PlayerStatType::GamesBeatenHardcoreHomebrew,
            'value' => 3,
        ]);
        PlayerStat::factory()->create([
            'user_id' => $user->id,
            'system_id' => $this->system->id,
            'type' => PlayerStatType::GamesBeatenHardcoreHacks,
            'value' => 4,
        ]);
        PlayerStat::factory()->create([
            'user_id' => $user->id,
            'system_id' => $this->system->id,
            'type' => PlayerStatType::GamesBeatenHardcorePrototypes,
            'value' => 5,
        ]);
        PlayerStat::factory()->create([
            'user_id' => $user->id,
            'system_id' => $this->system->id,
            'type' => PlayerStatType::GamesBeatenHardcoreDemos,
            'value' => 6,
        ]);

        // Act
        $this->action->execute($this->system->id, PlayerStatRankingKind::AllBeaten);

        // Assert
        $ranking = PlayerStatRanking::first();

        $this->assertNotNull($ranking);
        $this->assertEquals(21, $ranking->total); // !! 1+2+3+4+5+6 = 21
    }

    public function testItCalculatesRankingsForSpecificSystem(): void
    {
        // Arrange
        $system2 = System::factory()->create(['ID' => 2]);
        $user = User::factory()->create();

        PlayerStat::factory()->create([
            'user_id' => $user->id,
            'system_id' => $this->system->id,
            'type' => PlayerStatType::GamesBeatenHardcoreRetail,
            'value' => 10, // !!
        ]);
        PlayerStat::factory()->create([
            'user_id' => $user->id,
            'system_id' => $system2->id,
            'type' => PlayerStatType::GamesBeatenHardcoreRetail,
            'value' => 5,
        ]);

        // Act
        $this->action->execute($this->system->id, PlayerStatRankingKind::RetailBeaten); // !! only system 1

        // Assert
        $rankings = PlayerStatRanking::all();

        $this->assertCount(1, $rankings);
        $this->assertEquals($this->system->id, $rankings->first()->system_id);
        $this->assertEquals(10, $rankings->first()->total); // only system 1 stats
    }

    public function testItCalculatesOverallRankingsWhenSystemIdIsNull(): void
    {
        // Arrange
        $user = User::factory()->create();

        PlayerStat::factory()->create([
            'user_id' => $user->id,
            'system_id' => null,
            'type' => PlayerStatType::GamesBeatenHardcoreRetail,
            'value' => 25,
        ]);

        // Act
        $this->action->execute(systemId: null, kind: PlayerStatRankingKind::RetailBeaten);

        // Assert
        $ranking = PlayerStatRanking::first();

        $this->assertNotNull($ranking);
        $this->assertNull($ranking->system_id);
        $this->assertEquals(25, $ranking->total);
    }

    public function testHomebrewSystemExcludesRetailFromRetailBeatenKind(): void
    {
        // Arrange
        $homebrewSystem = System::factory()->create(['ID' => System::Arduboy]);
        $user = User::factory()->create();

        PlayerStat::factory()->create([
            'user_id' => $user->id,
            'system_id' => $homebrewSystem->id,
            'type' => PlayerStatType::GamesBeatenHardcoreRetail,
            'value' => 10,
        ]);

        // Act
        $this->action->execute($homebrewSystem->id, PlayerStatRankingKind::RetailBeaten);

        // Assert
        // ... no rankings should be created because homebrew systems have no retail games ...
        $this->assertCount(0, PlayerStatRanking::all());
    }

    public function testHomebrewSystemExcludesRetailFromAllBeatenKind(): void
    {
        // Arrange
        $homebrewSystem = System::factory()->create(['ID' => System::Arduboy]);
        $user = User::factory()->create();

        PlayerStat::factory()->create([
            'user_id' => $user->id,
            'system_id' => $homebrewSystem->id,
            'type' => PlayerStatType::GamesBeatenHardcoreRetail,
            'value' => 100,
        ]);
        PlayerStat::factory()->create([
            'user_id' => $user->id,
            'system_id' => $homebrewSystem->id,
            'type' => PlayerStatType::GamesBeatenHardcoreUnlicensed,
            'value' => 5,
        ]);
        PlayerStat::factory()->create([
            'user_id' => $user->id,
            'system_id' => $homebrewSystem->id,
            'type' => PlayerStatType::GamesBeatenHardcoreHomebrew,
            'value' => 3,
        ]);

        // Act
        $this->action->execute($homebrewSystem->id, PlayerStatRankingKind::AllBeaten);

        // Assert
        $ranking = PlayerStatRanking::first();

        $this->assertNotNull($ranking);
        $this->assertEquals(8, $ranking->total);
    }

    public function testItAggregatesMultipleStatTypesForSameUser(): void
    {
        // Arrange
        $user = User::factory()->create();

        PlayerStat::factory()->create([
            'user_id' => $user->id,
            'system_id' => $this->system->id,
            'type' => PlayerStatType::GamesBeatenHardcoreRetail,
            'value' => 5,
        ]);
        PlayerStat::factory()->create([
            'user_id' => $user->id,
            'system_id' => $this->system->id,
            'type' => PlayerStatType::GamesBeatenHardcoreUnlicensed,
            'value' => 3,
        ]);

        // Act
        $this->action->execute($this->system->id, PlayerStatRankingKind::RetailBeaten);

        // Assert
        $ranking = PlayerStatRanking::first();

        $this->assertNotNull($ranking);
        $this->assertEquals(8, $ranking->total);
    }

    public function testItSetsLastGameIdFromMostRecentStat(): void
    {
        // Arrange
        $user = User::factory()->create();
        $olderGame = Game::factory()->create(['system_id' => $this->system->id]);
        $newerGame = Game::factory()->create(['system_id' => $this->system->id]);

        PlayerStat::factory()->create([
            'user_id' => $user->id,
            'system_id' => $this->system->id,
            'type' => PlayerStatType::GamesBeatenHardcoreRetail,
            'value' => 5,
            'last_game_id' => $olderGame->id,
            'stat_updated_at' => now()->subDays(5), // older
        ]);
        PlayerStat::factory()->create([
            'user_id' => $user->id,
            'system_id' => $this->system->id,
            'type' => PlayerStatType::GamesBeatenHardcoreUnlicensed,
            'value' => 3,
            'last_game_id' => $newerGame->id,
            'stat_updated_at' => now()->subDays(1), // newer
        ]);

        // Act
        $this->action->execute($this->system->id, PlayerStatRankingKind::RetailBeaten);

        // Assert
        $ranking = PlayerStatRanking::first();

        $this->assertNotNull($ranking);
        $this->assertEquals($newerGame->id, $ranking->last_game_id); // from most recent stat
    }

    public function testItSetsLastAffectedAtFromMostRecentStat(): void
    {
        // Arrange
        $user = User::factory()->create();
        $olderDate = now()->subDays(10);
        $newerDate = now()->subDays(2);

        PlayerStat::factory()->create([
            'user_id' => $user->id,
            'system_id' => $this->system->id,
            'type' => PlayerStatType::GamesBeatenHardcoreRetail,
            'value' => 5,
            'stat_updated_at' => $olderDate,
        ]);
        PlayerStat::factory()->create([
            'user_id' => $user->id,
            'system_id' => $this->system->id,
            'type' => PlayerStatType::GamesBeatenHardcoreUnlicensed,
            'value' => 3,
            'stat_updated_at' => $newerDate, // !!
        ]);

        // Act
        $this->action->execute($this->system->id, PlayerStatRankingKind::RetailBeaten);

        // Assert
        $ranking = PlayerStatRanking::first();

        $this->assertNotNull($ranking);
        $this->assertEquals($newerDate->toDateTimeString(), $ranking->last_affected_at->toDateTimeString()); // matches newer date
    }

    public function testItReplacesExistingRankingsOnRerun(): void
    {
        // Arrange
        $user = User::factory()->create();

        $stat = PlayerStat::factory()->create([
            'user_id' => $user->id,
            'system_id' => $this->system->id,
            'type' => PlayerStatType::GamesBeatenHardcoreRetail,
            'value' => 10,
        ]);

        // Act
        // ... first run ...
        $this->action->execute($this->system->id, PlayerStatRankingKind::RetailBeaten);

        $ranking = PlayerStatRanking::first();
        $this->assertEquals(10, $ranking->total);

        // ... modify the stat ...
        $stat->value = 25;
        $stat->save();

        // ... second run ...
        $this->action->execute($this->system->id, PlayerStatRankingKind::RetailBeaten);

        // Assert
        $rankings = PlayerStatRanking::all();
        $this->assertCount(1, $rankings); // still only one ranking
        $this->assertEquals(25, $rankings->first()->total); // but the value was updated
    }

    public function testItHandlesEmptyPlayerStats(): void
    {
        // ... no players exist ...

        // Act
        $this->action->execute($this->system->id, PlayerStatRankingKind::RetailBeaten);

        // Assert
        $this->assertCount(0, PlayerStatRanking::all());
    }

    public function testItExcludesUsersWithZeroTotalFromLeaderboard(): void
    {
        // Arrange
        $user = User::factory()->create();

        PlayerStat::factory()->create([
            'user_id' => $user->id,
            'system_id' => $this->system->id,
            'type' => PlayerStatType::GamesBeatenHardcoreRetail,
            'value' => 0, // !! zero value
        ]);

        // Act
        $this->action->execute($this->system->id, PlayerStatRankingKind::RetailBeaten);

        // Assert
        $this->assertCount(0, PlayerStatRanking::all());
    }
}
