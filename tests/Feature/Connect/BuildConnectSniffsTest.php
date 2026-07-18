<?php

declare(strict_types=1);

namespace Tests\Feature\Connect;

use App\Connect\Actions\BuildConnectSniffsAction;
use App\Models\Achievement;
use App\Models\ConnectWarning;
use App\Models\Game;
use App\Models\GameHash;
use App\Models\Leaderboard;
use App\Models\PlayerSession;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Carbon::setTestNow(Carbon::now());
});

class BuildConnectSniffsTestHelpers
{
    public static function createLeaderboardWarning(
        string $user,
        Leaderboard $leaderboard,
        int $score,
        ?string $smells = 'unknown_client',
        ?string $userAgent = 'MyClient/1.5',
        ?string $validationHash = null,
        ): ConnectWarning {
        if ($validationHash === null) {
            $validationHash = md5($leaderboard->id . $user . $score);
        }

        return ConnectWarning::create([
            'method' => 'submitlbentry',
            'username' => $user,
            'user_agent' => $userAgent ?? '',
            'validation_hash' => $validationHash,
            'related_type' => 'leaderboard',
            'related_id' => $leaderboard->id,
            'hardcore' => 1,
            'extra' => $score,
            'smells' => $smells,
        ]);
    }

    public static function createAchievementWarning(
        string $user,
        Achievement $achievement,
        bool $hardcore,
        ?string $smells = 'unknown_client',
        ?string $userAgent = 'MyClient/1.5',
        ?string $validationHash = null,
        ): ConnectWarning {
        if ($validationHash === null) {
            $validationHash = md5($achievement->id . $user . ($hardcore ? '1' : '0'));
        }

        return ConnectWarning::create([
            'method' => 'awardachievement',
            'username' => $user,
            'user_agent' => $userAgent ?? '',
            'validation_hash' => $validationHash,
            'related_type' => 'achievement',
            'related_id' => $achievement->id,
            'hardcore' => $hardcore ? 1 : 0,
            'smells' => $smells,
        ]);
    }
}

describe('returns entries', function () {
    test('warning data', function () {
        Carbon::setTestNow('2026-05-01 12:34:56');
        $now = Carbon::now();
        $leaderboard1 = Leaderboard::factory()->create();
        $entry1 = BuildConnectSniffsTestHelpers::createLeaderboardWarning('Player1', $leaderboard1, 1234);
        Carbon::setTestNow('2026-05-02 23:45:01');

        $clients = [];
        $sniffs = (new BuildConnectSniffsAction())->execute($now, $clients);
        $this->assertEquals(1, count($sniffs));
        $this->assertEquals('2026-05-01 12:34:56', $sniffs[0]['date']);
        $this->assertEquals('MyClient/1.5', $sniffs[0]['userAgent']);
        $this->assertEquals($entry1->validation_hash, $sniffs[0]['validationHash']);
        $this->assertEquals($entry1->method, $sniffs[0]['method']);
        $this->assertEquals($entry1->username, $sniffs[0]['user']);
    });

    test('newest first', function () {
        $now = Carbon::now()->subDays(1)->startOfDay()->addHours(4);
        Carbon::setTestNow($now);
        $leaderboard1 = Leaderboard::factory()->create();

        Carbon::setTestNow($now->addMinutes(70));
        $entry1 = BuildConnectSniffsTestHelpers::createLeaderboardWarning('Player1', $leaderboard1, 1234);

        Carbon::setTestNow($now->addMinutes(90));
        $entry2 = BuildConnectSniffsTestHelpers::createLeaderboardWarning('Player2', $leaderboard1, 2345);

        Carbon::setTestNow($now->addMinutes(120));
        $entry3 = BuildConnectSniffsTestHelpers::createLeaderboardWarning('Player1', $leaderboard1, 2345);

        $clients = [];
        $sniffs = (new BuildConnectSniffsAction())->execute($now, $clients);
        $this->assertEquals(3, count($sniffs));
        $this->assertEquals($entry3->validation_hash, $sniffs[0]['validationHash']);
        $this->assertEquals($entry2->validation_hash, $sniffs[1]['validationHash']);
        $this->assertEquals($entry1->validation_hash, $sniffs[2]['validationHash']);
    });

    test('achievement data', function () {
        $achievement1 = Achievement::factory()->create();
        $entry1 = BuildConnectSniffsTestHelpers::createAchievementWarning('Player1', $achievement1, true);

        $clients = [];
        $sniffs = (new BuildConnectSniffsAction())->execute(Carbon::now(), $clients);
        $this->assertEquals(1, count($sniffs));
        $this->assertEquals('awardachievement', $sniffs[0]['method']);
        $this->assertEquals($achievement1->id, $sniffs[0]['achievementId']);
        $this->assertEquals($achievement1->id, $sniffs[0]['achievement']->id);
        $this->assertEquals($achievement1->title, $sniffs[0]['achievement']->title);
        $this->assertEquals(1, $sniffs[0]['hardcore']);
        $this->assertEquals([
            'id_user_hardcore' => md5($achievement1->id . 'Player11'),
        ], $sniffs[0]['serverValidationHashes']);
        $this->assertStringContainsString('Player1', $sniffs[0]['link']);
    });

    test('achievement data with offset', function () {
        $achievement1 = Achievement::factory()->create();
        $entry1 = BuildConnectSniffsTestHelpers::createAchievementWarning('Player1', $achievement1, true);
        $entry1->offset = 5;
        $entry1->save();

        $clients = [];
        $sniffs = (new BuildConnectSniffsAction())->execute(Carbon::now(), $clients);
        $this->assertEquals(1, count($sniffs));
        $this->assertEquals('awardachievement', $sniffs[0]['method']);
        $this->assertEquals($achievement1->id, $sniffs[0]['achievementId']);
        $this->assertEquals($achievement1->id, $sniffs[0]['achievement']->id);
        $this->assertEquals($achievement1->title, $sniffs[0]['achievement']->title);
        $this->assertEquals(1, $sniffs[0]['hardcore']);
        $this->assertEquals([
            'id_user_hardcore' => md5($achievement1->id . 'Player11'),
            'id_user_hardcore_id_offset' => md5($achievement1->id . 'Player11' . $achievement1->id . '5'),
        ], $sniffs[0]['serverValidationHashes']);
        $this->assertStringContainsString('Player1', $sniffs[0]['link']);
    });

    test('leaderboard data', function () {
        $leaderboard1 = Leaderboard::factory()->create();
        $entry1 = BuildConnectSniffsTestHelpers::createLeaderboardWarning('Player1', $leaderboard1, 1234);

        $clients = [];
        $sniffs = (new BuildConnectSniffsAction())->execute(Carbon::now(), $clients);
        $this->assertEquals(1, count($sniffs));
        $this->assertEquals('submitlbentry', $sniffs[0]['method']);
        $this->assertEquals($leaderboard1->id, $sniffs[0]['leaderboardId']);
        $this->assertEquals($leaderboard1->id, $sniffs[0]['leaderboard']->id);
        $this->assertEquals($leaderboard1->title, $sniffs[0]['leaderboard']->title);
        $this->assertEquals(1234, $sniffs[0]['score']);
        $this->assertEquals([
            'id_user_score' => md5($leaderboard1->id . 'Player11234'),
        ], $sniffs[0]['serverValidationHashes']);
        $this->assertStringContainsString('Player1', $sniffs[0]['link']);
    });

    test('leaderboard data with offset', function () {
        $leaderboard1 = Leaderboard::factory()->create();
        $entry1 = BuildConnectSniffsTestHelpers::createLeaderboardWarning('Player1', $leaderboard1, 1234);
        $entry1->offset = 5;
        $entry1->save();

        $clients = [];
        $sniffs = (new BuildConnectSniffsAction())->execute(Carbon::now(), $clients);
        $this->assertEquals(1, count($sniffs));
        $this->assertEquals('submitlbentry', $sniffs[0]['method']);
        $this->assertEquals($leaderboard1->id, $sniffs[0]['leaderboardId']);
        $this->assertEquals($leaderboard1->id, $sniffs[0]['leaderboard']->id);
        $this->assertEquals($leaderboard1->title, $sniffs[0]['leaderboard']->title);
        $this->assertEquals(1234, $sniffs[0]['score']);
        $this->assertEquals([
            'id_user_score' => md5($leaderboard1->id . 'Player11234'),
            'id_user_score_offset' => md5($leaderboard1->id . 'Player112345'),
        ], $sniffs[0]['serverValidationHashes']);
        $this->assertStringContainsString('Player1', $sniffs[0]['link']);
    });

    test('user data by display_name', function () {
        $leaderboard1 = Leaderboard::factory()->create();
        $user1 = User::factory()->create(['username' => 'UserName', 'display_name' => 'DisplayName']);
        $entry1 = BuildConnectSniffsTestHelpers::createLeaderboardWarning($user1->display_name, $leaderboard1, 1234);

        $clients = [];
        $sniffs = (new BuildConnectSniffsAction())->execute(Carbon::now(), $clients);
        $this->assertEquals(1, count($sniffs));
        $this->assertEquals($user1->display_name, $sniffs[0]['user']);
        $this->assertEquals($user1->id, $sniffs[0]['userinfo']->id);
        $this->assertEquals($user1->username, $sniffs[0]['userinfo']->username);
        $this->assertEquals($user1->display_name, $sniffs[0]['userinfo']->display_name);
        $this->assertEquals($user1->Permissions, $sniffs[0]['userinfo']->Permissions);
        $this->assertNull($sniffs[0]['userinfo']->deleted_at);
        $this->assertNull($sniffs[0]['userinfo']->unranked_at);
    });

    test('user data by username', function () {
        $leaderboard1 = Leaderboard::factory()->create();
        $user1 = User::factory()->create(['username' => 'UserName', 'display_name' => 'DisplayName']);
        $entry1 = BuildConnectSniffsTestHelpers::createLeaderboardWarning($user1->username, $leaderboard1, 1234);

        $clients = [];
        $sniffs = (new BuildConnectSniffsAction())->execute(Carbon::now(), $clients);
        $this->assertEquals(1, count($sniffs));
        $this->assertEquals($user1->username, $sniffs[0]['user']);
        $this->assertEquals($user1->id, $sniffs[0]['userinfo']->id);
        $this->assertEquals($user1->username, $sniffs[0]['userinfo']->username);
        $this->assertEquals($user1->display_name, $sniffs[0]['userinfo']->display_name);
        $this->assertEquals($user1->Permissions, $sniffs[0]['userinfo']->Permissions);
        $this->assertNull($sniffs[0]['userinfo']->deleted_at);
        $this->assertNull($sniffs[0]['userinfo']->unranked_at);
    });

    test('deleted user data', function () {
        $leaderboard1 = Leaderboard::factory()->create();
        $user1 = User::factory()->create();
        $user1->delete();
        $entry1 = BuildConnectSniffsTestHelpers::createLeaderboardWarning($user1->display_name, $leaderboard1, 1234);

        $clients = [];
        $sniffs = (new BuildConnectSniffsAction())->execute(Carbon::now(), $clients);
        $this->assertEquals(1, count($sniffs));
        $this->assertEquals($user1->display_name, $sniffs[0]['user']);
        $this->assertEquals($user1->id, $sniffs[0]['userinfo']->id);
        $this->assertEquals($user1->username, $sniffs[0]['userinfo']->username);
        $this->assertEquals($user1->display_name, $sniffs[0]['userinfo']->display_name);
        $this->assertEquals($user1->Permissions, $sniffs[0]['userinfo']->Permissions);
        $this->assertNotNull($sniffs[0]['userinfo']->deleted_at);
        $this->assertNull($sniffs[0]['userinfo']->unranked_at);
    });

    test('only for user by display_name', function () {
        $leaderboard1 = Leaderboard::factory()->create();
        $user1 = User::factory()->create(['username' => 'UserName', 'display_name' => 'DisplayName']);
        $entry1 = BuildConnectSniffsTestHelpers::createLeaderboardWarning($user1->display_name, $leaderboard1, 1234);
        $entry2 = BuildConnectSniffsTestHelpers::createLeaderboardWarning('Player1', $leaderboard1, 1234);

        $clients = [];
        $sniffs = (new BuildConnectSniffsAction())->execute(null, $clients, 'DisplayName');
        $this->assertEquals(1, count($sniffs));
        $this->assertEquals($user1->display_name, $sniffs[0]['user']);
        $this->assertEquals($user1->id, $sniffs[0]['userinfo']->id);
        $this->assertEquals($user1->username, $sniffs[0]['userinfo']->username);
        $this->assertEquals($user1->display_name, $sniffs[0]['userinfo']->display_name);
        $this->assertEquals($user1->Permissions, $sniffs[0]['userinfo']->Permissions);
        $this->assertNull($sniffs[0]['userinfo']->deleted_at);
        $this->assertNull($sniffs[0]['userinfo']->unranked_at);
    });

    test('only for user by username', function () {
        $leaderboard1 = Leaderboard::factory()->create();
        $user1 = User::factory()->create(['username' => 'UserName', 'display_name' => 'DisplayName']);
        $entry1 = BuildConnectSniffsTestHelpers::createLeaderboardWarning($user1->username, $leaderboard1, 1234);
        $entry2 = BuildConnectSniffsTestHelpers::createLeaderboardWarning('Player1', $leaderboard1, 1234);

        $clients = [];
        $sniffs = (new BuildConnectSniffsAction())->execute(null, $clients, 'UserName');
        $this->assertEquals(1, count($sniffs));
        $this->assertEquals($user1->username, $sniffs[0]['user']);
        $this->assertEquals($user1->id, $sniffs[0]['userinfo']->id);
        $this->assertEquals($user1->username, $sniffs[0]['userinfo']->username);
        $this->assertEquals($user1->display_name, $sniffs[0]['userinfo']->display_name);
        $this->assertEquals($user1->Permissions, $sniffs[0]['userinfo']->Permissions);
        $this->assertNull($sniffs[0]['userinfo']->deleted_at);
        $this->assertNull($sniffs[0]['userinfo']->unranked_at);
    });

    test('session data', function () {
        $game1 = Game::factory()->create();
        $gameHash1 = GameHash::factory()->create(['game_id' => $game1->id]);
        $leaderboard1 = Leaderboard::factory()->create();
        $user1 = User::factory()->create();
        $session1 = PlayerSession::factory()->create(['user_id' => $user1->id, 'game_id' => $game1->id, 'game_hash_id' => $gameHash1->id]);
        $entry1 = BuildConnectSniffsTestHelpers::createLeaderboardWarning($user1->display_name, $leaderboard1, 1234);
        $entry1->player_session_id = $session1->id;
        $entry1->save();

        $clients = [];
        $sniffs = (new BuildConnectSniffsAction())->execute(Carbon::now(), $clients);
        $this->assertEquals(1, count($sniffs));
        $this->assertEquals($gameHash1->md5, $sniffs[0]['gameHash']);
    });
});

describe('smells', function () {
    test('captured smells', function () {
        $leaderboard1 = Leaderboard::factory()->create();
        $user1 = User::factory()->create();
        $entry1 = BuildConnectSniffsTestHelpers::createLeaderboardWarning($user1->display_name, $leaderboard1, 1234, smells: 'wrong_client,bad_validation');

        $clients = [];
        $sniffs = (new BuildConnectSniffsAction())->execute(Carbon::now(), $clients);
        $this->assertEquals(1, count($sniffs));
        $this->assertEquals(['wrong_client', 'bad_validation'], $sniffs[0]['smells']);
    });

    test('unknown user', function () {
        $leaderboard1 = Leaderboard::factory()->create();
        $entry1 = BuildConnectSniffsTestHelpers::createLeaderboardWarning('Player1', $leaderboard1, 1234, smells: 'bad_validation');

        $clients = [];
        $sniffs = (new BuildConnectSniffsAction())->execute(Carbon::now(), $clients);
        $this->assertEquals(1, count($sniffs));

        // bad_validation provided to createLeaderboardWarning
        $this->assertEquals(['bad_validation', 'unknown_user'], $sniffs[0]['smells']);
        $this->assertStringContainsString('Player1', $sniffs[0]['link']);
    });

    test('no user', function () {
        $leaderboard1 = Leaderboard::factory()->create();
        $entry1 = BuildConnectSniffsTestHelpers::createLeaderboardWarning('', $leaderboard1, 1234, smells: 'bad_validation');

        $clients = [];
        $sniffs = (new BuildConnectSniffsAction())->execute(Carbon::now(), $clients);
        $this->assertEquals(1, count($sniffs));

        // bad_validation provided to createLeaderboardWarning
        $this->assertEquals(['bad_validation', 'no_user'], $sniffs[0]['smells']);
        $this->assertEquals('', $sniffs[0]['link']);
    });

    test('null user agent', function () {
        $leaderboard1 = Leaderboard::factory()->create();
        $user1 = User::factory()->create();
        $entry1 = BuildConnectSniffsTestHelpers::createLeaderboardWarning($user1->display_name, $leaderboard1, 1234, userAgent: null, smells: 'unknown_client');

        $clients = [];
        $sniffs = (new BuildConnectSniffsAction())->execute(Carbon::now(), $clients);
        $this->assertEquals(1, count($sniffs));
        $this->assertEquals(['unknown_client', 'no_user_agent'], $sniffs[0]['smells']);
        // unknown_client comes from the entry, so it's not added to clients
        $this->assertEquals(['no_user_agent'], $clients);
    });

    test('unknown user agent', function () {
        $leaderboard1 = Leaderboard::factory()->create();
        $user1 = User::factory()->create();
        $entry1 = BuildConnectSniffsTestHelpers::createLeaderboardWarning($user1->display_name, $leaderboard1, 1234, userAgent: 'MyClient/1.2.3', smells: 'unknown_client');

        $clients = [];
        $sniffs = (new BuildConnectSniffsAction())->execute(Carbon::now(), $clients);
        $this->assertEquals(1, count($sniffs));
        $this->assertEquals(['unknown_client', 'MyClient'], $sniffs[0]['smells']);
        // unknown_client comes from the entry, so it's not added to clients
        $this->assertEquals(['MyClient'], $clients);
    });

    test('browser user agent', function () {
        $leaderboard1 = Leaderboard::factory()->create();
        $user1 = User::factory()->create();
        $entry1 = BuildConnectSniffsTestHelpers::createLeaderboardWarning($user1->display_name, $leaderboard1, 1234, userAgent: 'Mozilla/5.0 (Windows NT 10.0) Gecko/20100101 Firefox/85.0', smells: 'unknown_client');

        $clients = [];
        $sniffs = (new BuildConnectSniffsAction())->execute(Carbon::now(), $clients);
        $this->assertEquals(1, count($sniffs));
        $this->assertEquals(['unknown_client', 'browser'], $sniffs[0]['smells']);
        // unknown_client comes from the entry, so it's not added to clients
        $this->assertEquals(['browser'], $clients);
    });

    test('blocked user agent', function () {
        $leaderboard1 = Leaderboard::factory()->create();
        $user1 = User::factory()->create();
        $entry1 = BuildConnectSniffsTestHelpers::createLeaderboardWarning($user1->display_name, $leaderboard1, 1234, userAgent: 'curl/8.5.0', smells: 'blocked_client');

        $clients = [];
        $sniffs = (new BuildConnectSniffsAction())->execute(Carbon::now(), $clients);
        $this->assertEquals(1, count($sniffs));
        $this->assertEquals(['blocked_client', 'curl'], $sniffs[0]['smells']);
        // blocked_client comes from the entry, so it's not added to clients
        $this->assertEquals(['curl'], $clients);
    });
});
