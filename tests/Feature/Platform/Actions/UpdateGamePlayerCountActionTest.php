<?php

declare(strict_types=1);

namespace Tests\Feature\Connect;

use App\Models\AchievementSet;
use App\Models\Game;
use App\Models\GameAchievementSet;
use App\Models\PlayerGame;
use App\Models\UnrankedUser;
use App\Models\User;
use App\Platform\Actions\UpdateGamePlayerCountAction;
use App\Platform\Enums\AchievementSetType;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

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
        UnrankedUser::create(['user_id' => PlayerGame::where('achievements_unlocked_hardcore', 0)->first()->user_id]);
        UnrankedUser::create(['user_id' => PlayerGame::where('achievements_unlocked_hardcore', '!=', 0)->first()->user_id]);

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
