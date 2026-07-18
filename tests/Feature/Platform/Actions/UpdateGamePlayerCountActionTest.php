<?php

declare(strict_types=1);

namespace Tests\Feature\Connect;

use App\Models\Achievement;
use App\Models\AchievementSet;
use App\Models\Game;
use App\Models\GameAchievementSet;
use App\Models\PlayerAchievement;
use App\Models\PlayerGame;
use App\Models\UnrankedUser;
use App\Models\User;
use App\Platform\Actions\CalculateAchievementWeightedPointsAction;
use App\Platform\Actions\UpdateAchievementMetricsAction;
use App\Platform\Actions\UpdateGamePlayerCountAction;
use App\Platform\Enums\AchievementSetType;
use App\Platform\Jobs\UpdatePlayerGameMetricsJob;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(LazilyRefreshDatabase::class);

class UpdateGamePlayerCountActionTestHelpers
{
    public static function createGame(): Game
    {
        $game = Game::factory()->create(['players_total' => 0, 'players_hardcore' => 0]);
        $achievementSet = AchievementSet::factory()->create(['players_total' => 0, 'players_hardcore' => 0]);
        GameAchievementSet::create([
            'game_id' => $game->id,
            'achievement_set_id' => $achievementSet->id,
            'type' => AchievementSetType::Core,
        ]);

        return $game;
    }

    public static function createSubset(Game $game, AchievementSetType $type): Game
    {
        $subset = UpdateGamePlayerCountActionTestHelpers::createGame();
        GameAchievementSet::create([
            'game_id' => $game->id,
            'achievement_set_id' => $subset->gameAchievementSets()->core()->first()->achievement_set_id,
            'type' => $type,
        ]);

        return $subset;
    }

    public static function addPlayers(Game $game, int $count, bool $hardcore): void
    {
        for ($i = 0; $i < $count; $i++) {
            $user = User::factory()->create();
            PlayerGame::create([
                'game_id' => $game->id,
                'user_id' => $user->id,
                'achievements_unlocked' => 5,
                'achievements_unlocked_hardcore' => $hardcore ? rand(1, 5) : 0,
            ]);
        }
    }
}

describe('core set', function () {
    test('constructs player count from player games records', function () {
        $game = UpdateGamePlayerCountActionTestHelpers::createGame();
        UpdateGamePlayerCountActionTestHelpers::addPlayers($game, 3, false);
        UpdateGamePlayerCountActionTestHelpers::addPlayers($game, 2, true);

        (new UpdateGamePlayerCountAction())->execute($game);

        $game->refresh();
        $this->assertEquals(5, $game->players_total);
        $this->assertEquals(2, $game->players_hardcore);

        $achievementSet = $game->gameAchievementSets()->core()->first()->achievementSet;
        $this->assertEquals(5, $achievementSet->players_total);
        $this->assertEquals(2, $achievementSet->players_hardcore);
    });

    test('ignores unranked users', function () {
        $game = UpdateGamePlayerCountActionTestHelpers::createGame();
        UpdateGamePlayerCountActionTestHelpers::addPlayers($game, 3, false);
        UpdateGamePlayerCountActionTestHelpers::addPlayers($game, 2, true);

        // unrank one hardcore and one softcore player
        $casualUserId = PlayerGame::where('achievements_unlocked_hardcore', 0)->first()->user_id;
        $hardcoreUserId = PlayerGame::where('achievements_unlocked_hardcore', '!=', 0)->first()->user_id;
        UnrankedUser::create(['user_id' => $casualUserId]);
        UnrankedUser::create(['user_id' => $hardcoreUserId]);

        (new UpdateGamePlayerCountAction())->execute($game);

        $game->refresh();
        $this->assertEquals(3, $game->players_total);
        $this->assertEquals(1, $game->players_hardcore);

        $achievementSet = $game->gameAchievementSets()->core()->first()->achievementSet;
        $this->assertEquals(3, $achievementSet->players_total);
        $this->assertEquals(1, $achievementSet->players_hardcore);
    });

    test('ignores bonus subset players', function () {
        $game = UpdateGamePlayerCountActionTestHelpers::createGame();
        UpdateGamePlayerCountActionTestHelpers::addPlayers($game, 3, false);
        UpdateGamePlayerCountActionTestHelpers::addPlayers($game, 2, true);

        $subset = UpdateGamePlayerCountActionTestHelpers::createSubset($game, AchievementSetType::Bonus);
        UpdateGamePlayerCountActionTestHelpers::addPlayers($subset, 2, false);
        UpdateGamePlayerCountActionTestHelpers::addPlayers($subset, 1, false);

        (new UpdateGamePlayerCountAction())->execute($game);

        $game->refresh();
        $this->assertEquals(5, $game->players_total);
        $this->assertEquals(2, $game->players_hardcore);

        $achievementSet = $game->gameAchievementSets()->core()->first()->achievementSet;
        $this->assertEquals(5, $achievementSet->players_total);
        $this->assertEquals(2, $achievementSet->players_hardcore);
    });

    test('can refresh achievement percentages from stored unlock counts without recounting player achievements', function () {
        $game = UpdateGamePlayerCountActionTestHelpers::createGame();
        $achievement = Achievement::factory()->promoted()->create([
            'game_id' => $game->id,
            'points' => 5,
            'unlocks_total' => 1,
            'unlocks_hardcore' => 1,
            'unlock_percentage' => 1.0,
            'unlock_hardcore_percentage' => 1.0,
        ]);

        UpdateGamePlayerCountActionTestHelpers::addPlayers($game, 2, true);

        $queries = [];
        DB::listen(function (QueryExecuted $query) use (&$queries): void {
            $queries[] = $query->sql;
        });

        (new UpdateGamePlayerCountAction())->execute($game, shouldRecalculateAchievementUnlockCounts: false);

        $achievement->refresh();
        $this->assertEquals(1, $achievement->unlocks_total);
        $this->assertEquals(1, $achievement->unlocks_hardcore);
        $this->assertEquals(0.5, $achievement->unlock_percentage);
        $this->assertEquals(0.5, $achievement->unlock_hardcore_percentage);

        $wasPlayerAchievementsQueried = collect($queries)
            ->contains(fn (string $sql): bool => str_contains($sql, 'player_achievements'));

        $this->assertFalse($wasPlayerAchievementsQueried);
    });

    test('does not overwrite fresher achievement metrics from a stale stored-count snapshot', function () {
        $game = UpdateGamePlayerCountActionTestHelpers::createGame();
        $game->players_total = 1;
        $game->players_hardcore = 1;
        $game->save();

        $stalePointsWeighted = (new CalculateAchievementWeightedPointsAction())->execute(
            points: 5,
            unlocks: 1,
            gamePlayers: 1,
            allPlayers: 0,
        );
        $freshPointsWeighted = (new CalculateAchievementWeightedPointsAction())->execute(
            points: 5,
            unlocks: 2,
            gamePlayers: 2,
            allPlayers: 0,
        );

        $achievement = Achievement::factory()->promoted()->create([
            'game_id' => $game->id,
            'points' => 5,
            'unlocks_total' => 1,
            'unlocks_hardcore' => 1,
            'unlock_percentage' => 0.5,
            'unlock_hardcore_percentage' => 0.5,
            'points_weighted' => $stalePointsWeighted,
        ]);

        $staleAchievementSnapshot = Achievement::find($achievement->id);
        $game->players_total = 2;
        $game->players_hardcore = 2;
        $game->save();

        $achievement->forceFill([
            'unlocks_total' => 2,
            'unlocks_hardcore' => 2,
            'unlock_percentage' => 1.0,
            'unlock_hardcore_percentage' => 1.0,
            'points_weighted' => $freshPointsWeighted,
        ])->save();

        app(UpdateAchievementMetricsAction::class)
            ->updateFromStoredUnlockCounts($game, collect([$staleAchievementSnapshot]));

        $achievement->refresh();

        $this->assertEquals(2, $achievement->unlocks_total);
        $this->assertEquals(2, $achievement->unlocks_hardcore);
        $this->assertEquals(1.0, $achievement->unlock_percentage);
        $this->assertEquals(1.0, $achievement->unlock_hardcore_percentage);
        $this->assertEquals($freshPointsWeighted, $achievement->points_weighted);
    });

    test('recounts achievements with null stored unlock counts', function () {
        $game = UpdateGamePlayerCountActionTestHelpers::createGame();
        $game->players_total = 2;
        $game->players_hardcore = 2;
        $game->save();

        $expectedPointsWeighted = (new CalculateAchievementWeightedPointsAction())->execute(
            points: 5,
            unlocks: 0,
            gamePlayers: 2,
            allPlayers: 0,
        );

        $achievement = Achievement::factory()->promoted()->create([
            'game_id' => $game->id,
            'points' => 5,
            'unlocks_total' => null,
            'unlocks_hardcore' => null,
            'unlock_percentage' => 1.0,
            'unlock_hardcore_percentage' => 1.0,
            'points_weighted' => 999,
        ]);

        app(UpdateAchievementMetricsAction::class)
            ->updateFromStoredUnlockCounts($game, collect([$achievement]));

        $achievement->refresh();

        $this->assertEquals(0, $achievement->unlocks_total);
        $this->assertEquals(0, $achievement->unlocks_hardcore);
        $this->assertEquals(0.0, $achievement->unlock_percentage);
        $this->assertEquals(0.0, $achievement->unlock_hardcore_percentage);
        $this->assertEquals($expectedPointsWeighted, $achievement->points_weighted);
    });

    test('retries stored-count rows when a recount changes counts during the guarded update', function () {
        $game = UpdateGamePlayerCountActionTestHelpers::createGame();
        $game->players_total = 1;
        $game->players_hardcore = 1;
        $game->save();

        $stalePointsWeighted = (new CalculateAchievementWeightedPointsAction())->execute(
            points: 5,
            unlocks: 1,
            gamePlayers: 1,
            allPlayers: 0,
        );
        $freshPointsWeighted = (new CalculateAchievementWeightedPointsAction())->execute(
            points: 5,
            unlocks: 2,
            gamePlayers: 2,
            allPlayers: 0,
        );

        $achievement = Achievement::factory()->promoted()->create([
            'game_id' => $game->id,
            'points' => 5,
            'unlocks_total' => 1,
            'unlocks_hardcore' => 1,
            'unlock_percentage' => 1.0,
            'unlock_hardcore_percentage' => 1.0,
            'points_weighted' => $stalePointsWeighted,
        ]);

        $game->players_total = 2;
        $game->players_hardcore = 2;
        $game->save();

        $users = User::factory()->count(2)->create();
        foreach ($users as $user) {
            PlayerAchievement::create([
                'user_id' => $user->id,
                'achievement_id' => $achievement->id,
                'unlocked_at' => now(),
                'unlocked_hardcore_at' => now(),
            ]);
        }

        $didSimulateRace = false;
        DB::listen(function (QueryExecuted $query) use (&$didSimulateRace, $achievement): void {
            if (
                !$didSimulateRace
                && str_starts_with($query->sql, 'select')
                && str_contains($query->sql, 'unlocks_total')
                && str_contains($query->sql, 'achievements')
            ) {
                $didSimulateRace = true;

                DB::table('achievements')
                    ->where('id', $achievement->id)
                    ->update([
                        'unlocks_total' => 2,
                        'unlocks_hardcore' => 2,
                        'unlock_percentage' => 2.0,
                        'unlock_hardcore_percentage' => 2.0,
                        'points_weighted' => 999,
                    ]);
            }
        });

        app(UpdateAchievementMetricsAction::class)
            ->updateFromStoredUnlockCounts($game, collect([$achievement]));

        $achievement->refresh();

        $this->assertTrue($didSimulateRace);
        $this->assertEquals(2, $achievement->unlocks_total);
        $this->assertEquals(2, $achievement->unlocks_hardcore);
        $this->assertEquals(1.0, $achievement->unlock_percentage);
        $this->assertEquals(1.0, $achievement->unlock_hardcore_percentage);
        $this->assertEquals($freshPointsWeighted, $achievement->points_weighted);
    });

    test('stored-count player game metrics jobs do not suppress full recount jobs', function () {
        $originalQueue = config('queue.default');

        config(['queue.default' => 'redis']);

        try {
            $fullRecountJob = new UpdatePlayerGameMetricsJob(
                userId: 1,
                gameId: 2,
                shouldRecalculateAchievementUnlockCounts: true
            );
            $storedCountsJob = new UpdatePlayerGameMetricsJob(
                userId: 1,
                gameId: 2,
                shouldRecalculateAchievementUnlockCounts: false
            );

            $this->assertNotEquals($fullRecountJob->uniqueId(), $storedCountsJob->uniqueId());
        } finally {
            config(['queue.default' => $originalQueue]);
        }
    });

    test('recounts achievement unlock counts by default', function () {
        $game = UpdateGamePlayerCountActionTestHelpers::createGame();
        $user = User::factory()->create();
        $achievement = Achievement::factory()->promoted()->create([
            'game_id' => $game->id,
            'points' => 5,
            'unlocks_total' => 0,
            'unlocks_hardcore' => 0,
        ]);

        PlayerGame::create([
            'game_id' => $game->id,
            'user_id' => $user->id,
            'achievements_unlocked' => 1,
            'achievements_unlocked_hardcore' => 1,
        ]);

        PlayerAchievement::create([
            'user_id' => $user->id,
            'achievement_id' => $achievement->id,
            'unlocked_at' => now(),
            'unlocked_hardcore_at' => now(),
        ]);

        (new UpdateGamePlayerCountAction())->execute($game);

        $achievement->refresh();
        $this->assertEquals(1, $achievement->unlocks_total);
        $this->assertEquals(1, $achievement->unlocks_hardcore);
        $this->assertEquals(1.0, $achievement->unlock_percentage);
        $this->assertEquals(1.0, $achievement->unlock_hardcore_percentage);
    });

    test('recounts achievement unlock counts without counting unranked users', function () {
        $game = UpdateGamePlayerCountActionTestHelpers::createGame();
        $game->players_total = 2;
        $game->players_hardcore = 1;
        $game->save();

        $rankedSoftcoreUser = User::factory()->create();
        $rankedHardcoreUser = User::factory()->create();
        $unrankedUser = User::factory()->create();

        $partiallyRankedAchievement = Achievement::factory()->promoted()->create([
            'game_id' => $game->id,
            'points' => 5,
            'unlocks_total' => 0,
            'unlocks_hardcore' => 0,
        ]);

        $onlyUnrankedAchievement = Achievement::factory()->promoted()->create([
            'game_id' => $game->id,
            'points' => 5,
            'unlocks_total' => 1,
            'unlocks_hardcore' => 1,
        ]);

        PlayerAchievement::create([
            'user_id' => $rankedSoftcoreUser->id,
            'achievement_id' => $partiallyRankedAchievement->id,
            'unlocked_at' => now(),
        ]);

        PlayerAchievement::create([
            'user_id' => $rankedHardcoreUser->id,
            'achievement_id' => $partiallyRankedAchievement->id,
            'unlocked_at' => now(),
            'unlocked_hardcore_at' => now(),
        ]);

        PlayerAchievement::create([
            'user_id' => $unrankedUser->id,
            'achievement_id' => $partiallyRankedAchievement->id,
            'unlocked_at' => now(),
            'unlocked_hardcore_at' => now(),
        ]);

        PlayerAchievement::create([
            'user_id' => $unrankedUser->id,
            'achievement_id' => $onlyUnrankedAchievement->id,
            'unlocked_at' => now(),
            'unlocked_hardcore_at' => now(),
        ]);

        UnrankedUser::create(['user_id' => $unrankedUser->id]);
        UnrankedUser::create(['user_id' => $unrankedUser->id]);

        app(UpdateAchievementMetricsAction::class)
            ->update($game, collect([$partiallyRankedAchievement, $onlyUnrankedAchievement]));

        $partiallyRankedAchievement->refresh();
        $onlyUnrankedAchievement->refresh();

        $this->assertEquals(2, $partiallyRankedAchievement->unlocks_total);
        $this->assertEquals(1, $partiallyRankedAchievement->unlocks_hardcore);
        $this->assertEquals(0, $onlyUnrankedAchievement->unlocks_total);
        $this->assertEquals(0, $onlyUnrankedAchievement->unlocks_hardcore);
    });
});

describe('subset', function () {
    test('constructs player count from game and bonus subset records', function () {
        $game = UpdateGamePlayerCountActionTestHelpers::createGame();
        UpdateGamePlayerCountActionTestHelpers::addPlayers($game, 3, false);
        UpdateGamePlayerCountActionTestHelpers::addPlayers($game, 2, true);

        $subset = UpdateGamePlayerCountActionTestHelpers::createSubset($game, AchievementSetType::Bonus);
        UpdateGamePlayerCountActionTestHelpers::addPlayers($subset, 2, false);
        UpdateGamePlayerCountActionTestHelpers::addPlayers($subset, 1, true);

        (new UpdateGamePlayerCountAction())->execute($subset);

        // base game should not be updated
        $game->refresh();
        $this->assertEquals(0, $game->players_total);
        $this->assertEquals(0, $game->players_hardcore);

        $achievementSet = $game->gameAchievementSets()->core()->first()->achievementSet;
        $this->assertEquals(0, $achievementSet->players_total);
        $this->assertEquals(0, $achievementSet->players_hardcore);

        // bonus subset game should only include players of subset
        $subset->refresh();
        $this->assertEquals(3, $subset->players_total);
        $this->assertEquals(1, $subset->players_hardcore);

        $achievementSet = $subset->gameAchievementSets()->core()->first()->achievementSet;
        $this->assertEquals(3, $achievementSet->players_total);
        $this->assertEquals(1, $achievementSet->players_hardcore);
    });

    test('constructs player count from game and challenge subset records', function () {
        $game = UpdateGamePlayerCountActionTestHelpers::createGame();
        UpdateGamePlayerCountActionTestHelpers::addPlayers($game, 3, false);
        UpdateGamePlayerCountActionTestHelpers::addPlayers($game, 2, true);

        $subset = UpdateGamePlayerCountActionTestHelpers::createSubset($game, AchievementSetType::Challenge);
        UpdateGamePlayerCountActionTestHelpers::addPlayers($subset, 2, false);
        UpdateGamePlayerCountActionTestHelpers::addPlayers($subset, 1, true);

        (new UpdateGamePlayerCountAction())->execute($subset);

        // base game should not be updated
        $game->refresh();
        $this->assertEquals(0, $game->players_total);
        $this->assertEquals(0, $game->players_hardcore);

        $achievementSet = $game->gameAchievementSets()->core()->first()->achievementSet;
        $this->assertEquals(0, $achievementSet->players_total);
        $this->assertEquals(0, $achievementSet->players_hardcore);

        // challenge subset game should only include players of subset
        $subset->refresh();
        $this->assertEquals(3, $subset->players_total);
        $this->assertEquals(1, $subset->players_hardcore);

        $achievementSet = $subset->gameAchievementSets()->core()->first()->achievementSet;
        $this->assertEquals(3, $achievementSet->players_total);
        $this->assertEquals(1, $achievementSet->players_hardcore);
    });

    test('constructs player count from game and specialty subset records', function () {
        $game = UpdateGamePlayerCountActionTestHelpers::createGame();
        UpdateGamePlayerCountActionTestHelpers::addPlayers($game, 3, false);
        UpdateGamePlayerCountActionTestHelpers::addPlayers($game, 2, true);

        $subset = UpdateGamePlayerCountActionTestHelpers::createSubset($game, AchievementSetType::Specialty);
        UpdateGamePlayerCountActionTestHelpers::addPlayers($subset, 2, false);
        UpdateGamePlayerCountActionTestHelpers::addPlayers($subset, 1, true);

        (new UpdateGamePlayerCountAction())->execute($subset);

        // base game should not be updated
        $game->refresh();
        $this->assertEquals(0, $game->players_total);
        $this->assertEquals(0, $game->players_hardcore);

        $achievementSet = $game->gameAchievementSets()->core()->first()->achievementSet;
        $this->assertEquals(0, $achievementSet->players_total);
        $this->assertEquals(0, $achievementSet->players_hardcore);

        // specialty subset game should only include players of subset
        $subset->refresh();
        $this->assertEquals(3, $subset->players_total);
        $this->assertEquals(1, $subset->players_hardcore);

        $achievementSet = $subset->gameAchievementSets()->core()->first()->achievementSet;
        $this->assertEquals(3, $achievementSet->players_total);
        $this->assertEquals(1, $achievementSet->players_hardcore);
    });

    test('constructs player count from game and exclusive subset records', function () {
        $game = UpdateGamePlayerCountActionTestHelpers::createGame();
        UpdateGamePlayerCountActionTestHelpers::addPlayers($game, 3, false);
        UpdateGamePlayerCountActionTestHelpers::addPlayers($game, 2, true);

        $subset = UpdateGamePlayerCountActionTestHelpers::createSubset($game, AchievementSetType::Exclusive);
        UpdateGamePlayerCountActionTestHelpers::addPlayers($subset, 2, false);
        UpdateGamePlayerCountActionTestHelpers::addPlayers($subset, 1, true);

        (new UpdateGamePlayerCountAction())->execute($subset);

        // base game should not be updated
        $game->refresh();
        $this->assertEquals(0, $game->players_total);
        $this->assertEquals(0, $game->players_hardcore);

        $achievementSet = $game->gameAchievementSets()->core()->first()->achievementSet;
        $this->assertEquals(0, $achievementSet->players_total);
        $this->assertEquals(0, $achievementSet->players_hardcore);

        // exclusive subset game should only include players of subset
        $subset->refresh();
        $this->assertEquals(3, $subset->players_total);
        $this->assertEquals(1, $subset->players_hardcore);

        $achievementSet = $subset->gameAchievementSets()->core()->first()->achievementSet;
        $this->assertEquals(3, $achievementSet->players_total);
        $this->assertEquals(1, $achievementSet->players_hardcore);
    });
});
