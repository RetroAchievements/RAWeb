<?php

declare(strict_types=1);

namespace Tests\Feature\Community\Actions;

use App\Community\Actions\BuildGameChecklistAction;
use App\Community\Data\GameGroupData;
use App\Community\Enums\AwardType;
use App\Models\Game;
use App\Models\GameAchievementSet;
use App\Models\PlayerBadge;
use App\Models\PlayerGame;
use App\Models\User;
use App\Platform\Enums\AchievementSetType;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;

uses(LazilyRefreshDatabase::class);

function createSubset(Game $game): GameAchievementSet
{
    // create an achievement set and associate it to the game
    GameAchievementSet::factory()->create(['game_id' => $game->id]);

    // create a subset game, and an achievement set for it, and associate them together
    $subsetGame = Game::factory()->create(['system_id' => $game->system_id]);
    $subsetGameSet = GameAchievementSet::factory()->create(['game_id' => $subsetGame->id]);

    // associate the subset to the base game
    GameAchievementSet::factory()->create([
        'game_id' => $game->id,
        'type' => AchievementSetType::Bonus,
        'achievement_set_id' => $subsetGameSet->achievement_set_id,
    ]);

    return $subsetGameSet;
}

function assertGameGroupData(
    GameGroupData $group,
    string $header,
    int $masteredCount,
    int $completedCount,
    int $beatenCount,
    int $beatenSoftcoreCount,
    array $gameIds,
): void {
    expect($group->header)->toBe($header);
    expect($group->masteredCount)->toBe($masteredCount);
    expect($group->completedCount)->toBe($completedCount);
    expect($group->beatenCount)->toBe($beatenCount);
    expect($group->beatenSoftcoreCount)->toBe($beatenSoftcoreCount);

    $groupGameIds = [];
    foreach ($group->games as $game) {
        $groupGameIds[] = $game->game->id;
    }
    expect($groupGameIds)->toEqual($gameIds);
}

describe('Parsing', function () {
    test('empty list', function () {
        $games = Game::factory()->create();
        $user = User::factory()->create();

        $result = (new BuildGameChecklistAction())->execute("", $user);

        $this->assertSame([], $result);
    });

    test('single game id, unheadered', function () {
        $game = Game::factory()->create();
        $user = User::factory()->create();

        $gameId = $game->id;
        $result = (new BuildGameChecklistAction())->execute("$gameId", $user);

        $this->assertEquals(1, count($result));

        assertGameGroupData($result[0], '', 0, 0, 0, 0, [$gameId]);
    });

    test('single game id, headered', function () {
        $game = Game::factory()->create();
        $user = User::factory()->create();

        $gameId = $game->id;
        $result = (new BuildGameChecklistAction())->execute("Header:$gameId", $user);

        $this->assertEquals(1, count($result));

        assertGameGroupData($result[0], 'Header', 0, 0, 0, 0, [$gameId]);
    });

    test('multiple game ids, unheadered', function () {
        $game1 = Game::factory()->create();
        $game2 = Game::factory()->create();
        $user = User::factory()->create();

        $game1Id = $game1->id;
        $game2Id = $game2->id;
        $result = (new BuildGameChecklistAction())->execute("$game1Id,$game2Id", $user);

        $this->assertEquals(1, count($result));

        assertGameGroupData($result[0], '', 0, 0, 0, 0, [$game1Id, $game2Id]);
    });

    test('multiple game ids, headered', function () {
        $game1 = Game::factory()->create();
        $game2 = Game::factory()->create();
        $user = User::factory()->create();

        $game1Id = $game1->id;
        $game2Id = $game2->id;
        $result = (new BuildGameChecklistAction())->execute("Header:$game1Id,$game2Id", $user);

        $this->assertEquals(1, count($result));

        assertGameGroupData($result[0], 'Header', 0, 0, 0, 0, [$game1Id, $game2Id]);
    });

    test('multiple groups, headered and unheadered (with repeated games)', function () {
        $game1 = Game::factory()->create();
        $game2 = Game::factory()->create();
        $game3 = Game::factory()->create();
        $game4 = Game::factory()->create();
        $user = User::factory()->create();

        $game1Id = $game1->id;
        $game2Id = $game2->id;
        $game3Id = $game3->id;
        $game4Id = $game4->id;
        $result = (new BuildGameChecklistAction())->execute("Header:$game1Id,$game2Id|$game3Id|Next:$game4Id,$game2Id", $user);

        $this->assertEquals(3, count($result));

        assertGameGroupData($result[0], 'Header', 0, 0, 0, 0, [$game1Id, $game2Id]);
        assertGameGroupData($result[1], '', 0, 0, 0, 0, [$game3Id]);
        assertGameGroupData($result[2], 'Next', 0, 0, 0, 0, [$game4Id, $game2Id]);
    });

    test('game with subset', function () {
        $game = Game::factory()->create();
        $subset = createSubset($game);
        $user = User::factory()->create();

        $gameId = $game->id;
        $subsetId = $subset->achievement_set_id;
        $result = (new BuildGameChecklistAction())->execute("$gameId,$gameId-$subsetId", $user);

        $this->assertEquals(1, count($result));

        assertGameGroupData($result[0], '', 0, 0, 0, 0, [$gameId, $subset->game_id]);
    });

    test('game with non-matching subset', function () {
        $game1 = Game::factory()->create();
        $game2 = Game::factory()->create();
        $subset = createSubset($game1);
        $user = User::factory()->create();

        $game1Id = $game1->id;
        $game2Id = $game2->id;
        $subsetId = $subset->achievement_set_id;
        $result = (new BuildGameChecklistAction())->execute("$game1Id,$game2Id-$subsetId", $user);

        $this->assertEquals(1, count($result));

        // subset is associated to game1, so it shouldn't be returned when requested through game2
        assertGameGroupData($result[0], '', 0, 0, 0, 0, [$game1Id]);
    });

    test('subset game by backing id', function () {
        $game = Game::factory()->create();
        $subset = createSubset($game);
        $user = User::factory()->create();

        $gameId = $game->id;
        $subsetGameId = $subset->game_id;
        $result = (new BuildGameChecklistAction())->execute("$gameId,$subsetGameId", $user);

        $this->assertEquals(1, count($result));

        assertGameGroupData($result[0], '', 0, 0, 0, 0, [$gameId, $subsetGameId]);
    });
});

describe("Progress", function () {
    test("user has beaten one game", function () {
        $game1 = Game::factory()->create();
        $game2 = Game::factory()->create();
        $user = User::factory()->create();
        $time1 = Carbon::parse('2025-06-03 12:45:33');

        PlayerGame::factory()->create([
            'user_id' => $user->id,
            'game_id' => $game2->id,
            'beaten_at' => $time1,
            'beaten_hardcore_at' => $time1,
        ]);

        $game1Id = $game1->id;
        $game2Id = $game2->id;
        $result = (new BuildGameChecklistAction())->execute("Header:$game1Id,$game2Id", $user);

        $this->assertEquals(1, count($result));

        assertGameGroupData($result[0], 'Header', 0, 0, 1, 0, [$game1Id, $game2Id]);
        $this->assertEquals($time1, $result[0]->games[1]->playerGame->beatenAt);
        $this->assertEquals($time1, $result[0]->games[1]->playerGame->beatenHardcoreAt);
        $this->assertNull($result[0]->games[1]->playerGame->completedAt);
        $this->assertNull($result[0]->games[1]->playerGame->completedHardcoreAt);
    });

    test("user has beaten one game in steps", function () {
        $game1 = Game::factory()->create();
        $game2 = Game::factory()->create();
        $user = User::factory()->create();
        $time1 = Carbon::parse('2025-06-03 12:45:33');
        $time2 = $time1->clone()->addHours(1);
        $time3 = $time1->clone()->addDays(1);
        $time4 = $time3->clone()->addHours(1);

        PlayerGame::factory()->create([
            'user_id' => $user->id,
            'game_id' => $game2->id,
            'beaten_at' => $time1,
            'completed_at' => $time2,
            'beaten_hardcore_at' => $time3,
            'completed_hardcore_at' => $time4,
        ]);

        $game1Id = $game1->id;
        $game2Id = $game2->id;
        $result = (new BuildGameChecklistAction())->execute("Header:$game1Id,$game2Id", $user);

        $this->assertEquals(1, count($result));

        assertGameGroupData($result[0], 'Header', 1, 0, 1, 0, [$game1Id, $game2Id]);
        $this->assertEquals($time1, $result[0]->games[1]->playerGame->beatenAt);
        $this->assertEquals($time3, $result[0]->games[1]->playerGame->beatenHardcoreAt);
        $this->assertEquals($time2, $result[0]->games[1]->playerGame->completedAt);
        $this->assertEquals($time4, $result[0]->games[1]->playerGame->completedHardcoreAt);
    });

    test("user has beaten one game and completed the other in softcore", function () {
        $game1 = Game::factory()->create();
        $game2 = Game::factory()->create();
        $user = User::factory()->create();
        $time1 = Carbon::parse('2025-06-03 12:45:33');
        $time2 = $time1->clone()->addDays(1);
        $time3 = $time2->clone()->addHours(3);

        PlayerGame::factory()->create([
            'user_id' => $user->id,
            'game_id' => $game2->id,
            'beaten_at' => $time1,
            'beaten_hardcore_at' => $time1,
        ]);

        PlayerGame::factory()->create([
            'user_id' => $user->id,
            'game_id' => $game1->id,
            'beaten_at' => $time2,
            'completed_at' => $time3,
        ]);

        $game1Id = $game1->id;
        $game2Id = $game2->id;
        $result = (new BuildGameChecklistAction())->execute("Header:$game1Id,$game2Id", $user);

        $this->assertEquals(1, count($result));

        assertGameGroupData($result[0], 'Header', 0, 1, 1, 1, [$game1Id, $game2Id]);
        $this->assertEquals($time2, $result[0]->games[0]->playerGame->beatenAt);
        $this->assertNull($result[0]->games[0]->playerGame->beatenHardcoreAt);
        $this->assertEquals($time3, $result[0]->games[0]->playerGame->completedAt);
        $this->assertNull($result[0]->games[0]->playerGame->completedHardcoreAt);

        $this->assertEquals($time1, $result[0]->games[1]->playerGame->beatenAt);
        $this->assertEquals($time1, $result[0]->games[1]->playerGame->beatenHardcoreAt);
        $this->assertNull($result[0]->games[1]->playerGame->completedAt);
        $this->assertNull($result[0]->games[1]->playerGame->completedHardcoreAt);
    });

    test("user has beaten both games", function () {
        $game1 = Game::factory()->create();
        $game2 = Game::factory()->create();
        $user = User::factory()->create();
        $time1 = Carbon::parse('2025-06-03 12:45:33');
        $time2 = $time1->clone()->addDays(1);
        $time3 = $time2->clone()->addHours(3);

        PlayerGame::factory()->create([
            'user_id' => $user->id,
            'game_id' => $game2->id,
            'beaten_at' => $time1,
            'beaten_hardcore_at' => $time1,
        ]);

        PlayerGame::factory()->create([
            'user_id' => $user->id,
            'game_id' => $game1->id,
            'beaten_at' => $time2,
            'beaten_hardcore_at' => $time3,
        ]);

        $game1Id = $game1->id;
        $game2Id = $game2->id;
        $result = (new BuildGameChecklistAction())->execute("Header:$game1Id,$game2Id", $user);

        $this->assertEquals(1, count($result));

        assertGameGroupData($result[0], 'Header', 0, 0, 2, 0, [$game1Id, $game2Id]);
        $this->assertEquals($time2, $result[0]->games[0]->playerGame->beatenAt);
        $this->assertEquals($time3, $result[0]->games[0]->playerGame->beatenHardcoreAt);
        $this->assertNull($result[0]->games[0]->playerGame->completedAt);
        $this->assertNull($result[0]->games[0]->playerGame->completedHardcoreAt);

        $this->assertEquals($time1, $result[0]->games[1]->playerGame->beatenAt);
        $this->assertEquals($time1, $result[0]->games[1]->playerGame->beatenHardcoreAt);
        $this->assertNull($result[0]->games[1]->playerGame->completedAt);
        $this->assertNull($result[0]->games[1]->playerGame->completedHardcoreAt);
    });

    test("user has beaten one game but new progression achievements were added", function () {
        $game1 = Game::factory()->create();
        $game2 = Game::factory()->create();
        $user = User::factory()->create();
        $time1 = Carbon::parse('2025-06-03 12:45:33');

        PlayerGame::factory()->create([
            'user_id' => $user->id,
            'game_id' => $game2->id,
        ]);

        PlayerBadge::factory()->create([
            'user_id' => $user->id,
            'award_type' => AwardType::GameBeaten,
            'award_key' => $game2->id,
            'award_tier' => 1,
            'awarded_at' => $time1,
        ]);

        $game1Id = $game1->id;
        $game2Id = $game2->id;
        $result = (new BuildGameChecklistAction())->execute("Header:$game1Id,$game2Id", $user);

        $this->assertEquals(1, count($result));

        assertGameGroupData($result[0], 'Header', 0, 0, 1, 0, [$game1Id, $game2Id]);
        $this->assertEquals($time1, $result[0]->games[1]->playerGame->beatenAt);
        $this->assertEquals($time1, $result[0]->games[1]->playerGame->beatenHardcoreAt);
        $this->assertNull($result[0]->games[1]->playerGame->completedAt);
        $this->assertNull($result[0]->games[1]->playerGame->completedHardcoreAt);
    });
});
