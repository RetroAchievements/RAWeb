<?php

declare(strict_types=1);

namespace Tests\Feature\Connect;

use App\Community\Enums\ClaimType;
use App\Enums\GameHashCompatibility;
use App\Models\AchievementSet;
use App\Models\AchievementSetClaim;
use App\Models\Game;
use App\Models\GameAchievementSet;
use App\Models\Leaderboard;
use App\Models\Role;
use App\Models\System;
use App\Models\Trigger;
use App\Models\User;
use App\Platform\Enums\LeaderboardState;
use App\Platform\Services\VirtualGameIdService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Feature\Platform\Concerns\TestsAuditComments;

uses(LazilyRefreshDatabase::class);
uses(TestsAuditComments::class);
uses(TestsConnect::class);

class UploadLeaderboardTestHelpers
{
    public static function createGame(): Game
    {
        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create(['system_id' => $system->id]);
        /** @var GameAchievementSet $gameAchievementSet */
        $gameAchievementSet = GameAchievementSet::factory()->create(['game_id' => $game->id]);
        // /** @var GameHash $gameHash */
        // $gameHash = GameHash::create([
        //     'game_id' => $game->id,
        //     'system_id' => $game->system_id,
        //     'compatibility' => GameHashCompatibility::Compatible,
        //     'md5' => fake()->md5(),
        //     'name' => 'hash_' . $game->id,
        //     'description' => 'hash_' . $game->id,
        // ]);

        return $game;
    }

    public static function createPromotedLeaderboard(Game $game, User $author): Leaderboard
    {
        $leaderboard = Leaderboard::factory()->for($game)->create([
            'author_id' => $author->id,
            'trigger_definition' => 'STA:1=0::CAN:3=0::SUB:2=0::VAL:4=0',
        ]);

        $trigger = $leaderboard->trigger()->save(new Trigger([
            'conditions' => $leaderboard->trigger_definition,
            'version' => 1,
            'user_id' => $author->id,
        ]));
        $leaderboard->update(['trigger_id' => $trigger->id]);

        return $leaderboard;
    }

    public static function createUnpromotedLeaderboard(Game $game, User $author): Leaderboard
    {
        $leaderboard = Leaderboard::factory()->for($game)->create([
            'author_id' => $author->id,
            'trigger_definition' => 'STA:1=0::CAN:3=0::SUB:2=0::VAL:4=0',
            'state' => 'unpromoted',
        ]);

        $trigger = $leaderboard->trigger()->save(new Trigger([
            'conditions' => $leaderboard->trigger_definition,
            'version' => null,
            'user_id' => $author->id,
        ]));
        $leaderboard->update(['trigger_id' => $trigger->id]);

        return $leaderboard;
    }

    public static function addClaim(Game $game, User $user): AchievementSetClaim
    {
        return AchievementSetClaim::factory()->create([
            'user_id' => $user->id,
            'game_id' => $game->id,
        ]);
    }

    public static function apiUrlWithChecksum(array $params): string
    {
        // insert checksum
        $leaderboardId = $params['i'] ?? 0;
        $username = $params['u'] ?? '';
        $startTrigger = $params['s'] ?? '';
        $submitTrigger = $params['b'] ?? '';
        $cancelTrigger = $params['c'] ?? '';
        $valueDefinition = $params['l'] ?? '';
        $format = $params['f'] ?? '';

        $message = "{$username}SECRET{$leaderboardId}SEC{$startTrigger}{$submitTrigger}{$cancelTrigger}{$valueDefinition}RE2{$format}";
        $params['h'] = md5($message);

        return sprintf('dorequest.php?%s', http_build_query($params));
    }
}

beforeEach(function () {
    Carbon::setTestNow(Carbon::now());

    $this->createConnectUser();
    $this->addServerUser();

    Role::create(['name' => Role::DEVELOPER, 'display' => 1]);
    Role::create(['name' => Role::DEVELOPER_JUNIOR, 'display' => 2]);
    Role::create(['name' => Role::WRITER, 'display' => 3]);
});

describe('developer', function () {
    test('can create new leaderboard', function () {
        $this->user->assignRole(Role::DEVELOPER);
        $game = UploadLeaderboardTestHelpers::createGame();
        UploadLeaderboardTestHelpers::addClaim($game, $this->user);

        $this->get(UploadLeaderboardTestHelpers::apiUrlWithChecksum($this->apiParams('uploadleaderboard', [
            'g' => $game->id,
            'n' => 'Title',
            'd' => 'Description',
            's' => '1=0',
            'b' => '2=0',
            'c' => '3=0',
            'l' => '4=0',
            'w' => 1,
            'f' => 'VALUE',
            'm' => 'unpromoted',
        ])))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'LeaderboardID' => 1,
            ]);

        $leaderboard1 = Leaderboard::find(1);
        $this->assertEquals('Title', $leaderboard1->title);
        $this->assertEquals('Description', $leaderboard1->description);
        $this->assertEquals('STA:1=0::CAN:3=0::SUB:2=0::VAL:4=0', $leaderboard1->trigger_definition);
        $this->assertEquals(true, $leaderboard1->rank_asc);
        $this->assertEquals('VALUE', $leaderboard1->format);
        $this->assertEquals(1, $leaderboard1->order_column);
        $this->assertEquals(LeaderboardState::Unpromoted, $leaderboard1->state);

        // leaderboard should have a trigger without a version
        $this->assertNotNull($leaderboard1->trigger);
        $this->assertEquals('STA:1=0::CAN:3=0::SUB:2=0::VAL:4=0', $leaderboard1->trigger->conditions);
        $this->assertNull($leaderboard1->trigger->version);

        // creation audit log entry should be made
        $activity = $leaderboard1->auditLog->where('event', 'created')->first();
        $this->assertNotNull($activity);
        $this->assertEquals($this->user->id, $activity->causer_id);
    });

    test('cannot create new leaderboard without claim', function () {
        $this->user->assignRole(Role::DEVELOPER);
        $game = UploadLeaderboardTestHelpers::createGame();

        $this->get(UploadLeaderboardTestHelpers::apiUrlWithChecksum($this->apiParams('uploadleaderboard', [
            'g' => $game->id,
            'n' => 'Title',
            'd' => 'Description',
            's' => '1=0',
            'b' => '2=0',
            'c' => '3=0',
            'l' => '4=0',
            'w' => 1,
            'f' => 'VALUE',
        ])))
            ->assertStatus(403)
            ->assertExactJson([
                'Status' => 403,
                'Code' => 'access_denied',
                'Success' => false,
                'Error' => 'You must have an active claim on this game to perform this action.',
            ]);

        $this->assertEquals(0, Leaderboard::count());
    });

    test('can create new leaderboard for collaboration claim', function () {
        $this->user->assignRole(Role::DEVELOPER);
        $game = UploadLeaderboardTestHelpers::createGame();
        $claim = UploadLeaderboardTestHelpers::addClaim($game, $this->user);
        $claim->claim_type = ClaimType::Collaboration;
        $claim->save();

        $this->get(UploadLeaderboardTestHelpers::apiUrlWithChecksum($this->apiParams('uploadleaderboard', [
            'g' => $game->id,
            'n' => 'Title',
            'd' => 'Description',
            's' => '1=0',
            'b' => '2=0',
            'c' => '3=0',
            'l' => '4=0',
            'w' => 1,
            'f' => 'VALUE',
        ])))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'LeaderboardID' => 1,
            ]);

        $leaderboard1 = Leaderboard::find(1);
        $this->assertEquals('Title', $leaderboard1->title);
        $this->assertEquals('Description', $leaderboard1->description);
        $this->assertEquals('STA:1=0::CAN:3=0::SUB:2=0::VAL:4=0', $leaderboard1->trigger_definition);
        $this->assertEquals(true, $leaderboard1->rank_asc);
        $this->assertEquals('VALUE', $leaderboard1->format);
        $this->assertEquals(1, $leaderboard1->order_column);
        $this->assertEquals(LeaderboardState::Active, $leaderboard1->state);

        // leaderboard should have a trigger
        $this->assertNotNull($leaderboard1->trigger);
        $this->assertEquals('STA:1=0::CAN:3=0::SUB:2=0::VAL:4=0', $leaderboard1->trigger->conditions);
        $this->assertEquals(1, $leaderboard1->trigger->version);

        // creation audit log entry should be made
        $activity = $leaderboard1->auditLog->where('event', 'created')->first();
        $this->assertNotNull($activity);
        $this->assertEquals($this->user->id, $activity->causer_id);
    });

    test('can update unpromoted own', function () {
        $this->user->assignRole(Role::DEVELOPER);
        $game = UploadLeaderboardTestHelpers::createGame();
        // NOTE: developer does not need active claim to update unpromoted leaderboards
        $leaderboard1 = UploadLeaderboardTestHelpers::createUnpromotedLeaderboard($game, $this->user);
        $oldOrder = $leaderboard1->order_column;

        $this->get(UploadLeaderboardTestHelpers::apiUrlWithChecksum($this->apiParams('uploadleaderboard', [
            'i' => $leaderboard1->id,
            'g' => $game->id,
            'n' => 'New Title',
            'd' => 'New Description',
            's' => '1=0',
            'b' => '2=1',
            'c' => '3=0',
            'l' => '4=0',
            'w' => 1,
            'f' => 'VALUE',
            'm' => 'unpromoted',
        ])))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'LeaderboardID' => 1,
            ]);

        $leaderboard1->refresh();
        $this->assertEquals('New Title', $leaderboard1->title);
        $this->assertEquals('New Description', $leaderboard1->description);
        $this->assertEquals('STA:1=0::CAN:3=0::SUB:2=1::VAL:4=0', $leaderboard1->trigger_definition);
        $this->assertEquals(true, $leaderboard1->rank_asc);
        $this->assertEquals('VALUE', $leaderboard1->format);
        $this->assertEquals($oldOrder, $leaderboard1->order_column);
        $this->assertEquals(LeaderboardState::Unpromoted, $leaderboard1->state);

        // leaderboard trigger should be updated, but version remains null until promoted
        $this->assertNotNull($leaderboard1->trigger);
        $this->assertEquals('STA:1=0::CAN:3=0::SUB:2=1::VAL:4=0', $leaderboard1->trigger->conditions);
        $this->assertNull($leaderboard1->trigger->version);

        // update audit log entry should be made
        $activity = $leaderboard1->auditLog->where('event', 'updated')->first();
        $this->assertNotNull($activity);
        $this->assertEquals($this->user->id, $activity->causer_id);
    });

    test('can promote own', function () {
        $this->user->assignRole(Role::DEVELOPER);
        $game = UploadLeaderboardTestHelpers::createGame();
        // NOTE: developer does not need active claim to promote
        $leaderboard1 = UploadLeaderboardTestHelpers::createUnpromotedLeaderboard($game, $this->user);
        $oldOrder = $leaderboard1->order_column;

        $this->get(UploadLeaderboardTestHelpers::apiUrlWithChecksum($this->apiParams('uploadleaderboard', [
            'i' => $leaderboard1->id,
            'g' => $game->id,
            'n' => $leaderboard1->title,
            'd' => $leaderboard1->description,
            's' => '1=0',
            'b' => '2=0',
            'c' => '3=0',
            'l' => '4=0',
            'w' => 1,
            'f' => 'VALUE',
            'm' => 'active',
        ])))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'LeaderboardID' => 1,
            ]);

        $leaderboard1->refresh();
        $this->assertEquals('STA:1=0::CAN:3=0::SUB:2=0::VAL:4=0', $leaderboard1->trigger_definition);
        $this->assertEquals(true, $leaderboard1->rank_asc);
        $this->assertEquals('VALUE', $leaderboard1->format);
        $this->assertEquals($oldOrder, $leaderboard1->order_column);
        $this->assertEquals(LeaderboardState::Active, $leaderboard1->state);

        // leaderboard trigger should be updated and initial version assigned
        $this->assertNotNull($leaderboard1->trigger);
        $this->assertEquals('STA:1=0::CAN:3=0::SUB:2=0::VAL:4=0', $leaderboard1->trigger->conditions);
        $this->assertEquals(1, $leaderboard1->trigger->version);

        // update audit log entry should be made
        $activity = $leaderboard1->auditLog->where('event', 'updated')->first();
        $this->assertNotNull($activity);
        $this->assertEquals($this->user->id, $activity->causer_id);
    });

    test('can update promoted own', function () {
        $this->user->assignRole(Role::DEVELOPER);
        $game = UploadLeaderboardTestHelpers::createGame();
        // NOTE: developer does not need active claim to updated promoted leaderboards
        $leaderboard1 = UploadLeaderboardTestHelpers::createPromotedLeaderboard($game, $this->user);
        $oldOrder = $leaderboard1->order_column;

        $this->get(UploadLeaderboardTestHelpers::apiUrlWithChecksum($this->apiParams('uploadleaderboard', [
            'i' => $leaderboard1->id,
            'g' => $game->id,
            'n' => 'New Title',
            'd' => 'New Description',
            's' => '1=0',
            'b' => '2=1',
            'c' => '3=0',
            'l' => '4=0',
            'w' => 1,
            'f' => 'VALUE',
            'm' => 'active',
        ])))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'LeaderboardID' => 1,
            ]);

        $leaderboard1->refresh();
        $this->assertEquals('New Title', $leaderboard1->title);
        $this->assertEquals('New Description', $leaderboard1->description);
        $this->assertEquals('STA:1=0::CAN:3=0::SUB:2=1::VAL:4=0', $leaderboard1->trigger_definition);
        $this->assertEquals(true, $leaderboard1->rank_asc);
        $this->assertEquals('VALUE', $leaderboard1->format);
        $this->assertEquals($oldOrder, $leaderboard1->order_column);
        $this->assertEquals(LeaderboardState::Active, $leaderboard1->state);

        // leaderboard trigger should be updated, and version incremented
        $this->assertNotNull($leaderboard1->trigger);
        $this->assertEquals('STA:1=0::CAN:3=0::SUB:2=1::VAL:4=0', $leaderboard1->trigger->conditions);
        $this->assertEquals(2, $leaderboard1->trigger->version);

        // update audit log entry should be made
        $activity = $leaderboard1->auditLog->where('event', 'updated')->first();
        $this->assertNotNull($activity);
        $this->assertEquals($this->user->id, $activity->causer_id);
    });

    test('can demote own', function () {
        $this->user->assignRole(Role::DEVELOPER);
        $game = UploadLeaderboardTestHelpers::createGame();
        // NOTE: developer does not need active claim to demote
        $leaderboard1 = UploadLeaderboardTestHelpers::createPromotedLeaderboard($game, $this->user);
        $oldOrder = $leaderboard1->order_column;

        $this->get(UploadLeaderboardTestHelpers::apiUrlWithChecksum($this->apiParams('uploadleaderboard', [
            'i' => $leaderboard1->id,
            'g' => $game->id,
            'n' => 'New Title',
            'd' => 'New Description',
            's' => '1=0',
            'b' => '2=1',
            'c' => '3=0',
            'l' => '4=0',
            'w' => 1,
            'f' => 'VALUE',
            'm' => 'unpromoted',
        ])))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'LeaderboardID' => 1,
            ]);

        $leaderboard1->refresh();
        $this->assertEquals('New Title', $leaderboard1->title);
        $this->assertEquals('New Description', $leaderboard1->description);
        $this->assertEquals('STA:1=0::CAN:3=0::SUB:2=1::VAL:4=0', $leaderboard1->trigger_definition);
        $this->assertEquals(true, $leaderboard1->rank_asc);
        $this->assertEquals('VALUE', $leaderboard1->format);
        $this->assertEquals($oldOrder, $leaderboard1->order_column);
        $this->assertEquals(LeaderboardState::Unpromoted, $leaderboard1->state);

        // leaderboard trigger should be updated and version incremented
        $this->assertNotNull($leaderboard1->trigger);
        $this->assertEquals('STA:1=0::CAN:3=0::SUB:2=1::VAL:4=0', $leaderboard1->trigger->conditions);
        $this->assertEquals(2, $leaderboard1->trigger->version);

        // update audit log entry should be made
        $activity = $leaderboard1->auditLog->where('event', 'updated')->first();
        $this->assertNotNull($activity);
        $this->assertEquals($this->user->id, $activity->causer_id);
    });

    test('can update unpromoted someone elses', function () {
        $this->user->assignRole(Role::DEVELOPER);
        $game = UploadLeaderboardTestHelpers::createGame();
        // NOTE: developer does not need active claim to update unpromoted leaderboards
        $user2 = User::factory()->create();
        $leaderboard1 = UploadLeaderboardTestHelpers::createUnpromotedLeaderboard($game, $user2);
        $oldOrder = $leaderboard1->order_column;

        $this->get(UploadLeaderboardTestHelpers::apiUrlWithChecksum($this->apiParams('uploadleaderboard', [
            'i' => $leaderboard1->id,
            'g' => $game->id,
            'n' => 'New Title',
            'd' => 'New Description',
            's' => '1=0',
            'b' => '2=1',
            'c' => '3=0',
            'l' => '4=0',
            'w' => 1,
            'f' => 'VALUE',
            'm' => 'unpromoted',
        ])))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'LeaderboardID' => 1,
            ]);

        $leaderboard1->refresh();
        $this->assertEquals('New Title', $leaderboard1->title);
        $this->assertEquals('New Description', $leaderboard1->description);
        $this->assertEquals('STA:1=0::CAN:3=0::SUB:2=1::VAL:4=0', $leaderboard1->trigger_definition);
        $this->assertEquals(true, $leaderboard1->rank_asc);
        $this->assertEquals('VALUE', $leaderboard1->format);
        $this->assertEquals($oldOrder, $leaderboard1->order_column);
        $this->assertEquals(LeaderboardState::Unpromoted, $leaderboard1->state);

        // leaderboard trigger should be updated, but version remains null until promoted
        $this->assertNotNull($leaderboard1->trigger);
        $this->assertEquals('STA:1=0::CAN:3=0::SUB:2=1::VAL:4=0', $leaderboard1->trigger->conditions);
        $this->assertNull($leaderboard1->trigger->version);

        // update audit log entry should be made
        $activity = $leaderboard1->auditLog->where('event', 'updated')->first();
        $this->assertNotNull($activity);
        $this->assertEquals($this->user->id, $activity->causer_id);
    });

    test('can promote someone elses', function () {
        $this->user->assignRole(Role::DEVELOPER);
        $game = UploadLeaderboardTestHelpers::createGame();
        // NOTE: developer does not need active claim to promote
        $user2 = User::factory()->create();
        $leaderboard1 = UploadLeaderboardTestHelpers::createUnpromotedLeaderboard($game, $user2);
        $oldOrder = $leaderboard1->order_column;

        $this->get(UploadLeaderboardTestHelpers::apiUrlWithChecksum($this->apiParams('uploadleaderboard', [
            'i' => $leaderboard1->id,
            'g' => $game->id,
            'n' => 'New Title',
            'd' => 'New Description',
            's' => '1=0',
            'b' => '2=1',
            'c' => '3=0',
            'l' => '4=0',
            'w' => 1,
            'f' => 'VALUE',
            'm' => 'active',
        ])))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'LeaderboardID' => 1,
            ]);

        $leaderboard1->refresh();
        $this->assertEquals('New Title', $leaderboard1->title);
        $this->assertEquals('New Description', $leaderboard1->description);
        $this->assertEquals('STA:1=0::CAN:3=0::SUB:2=1::VAL:4=0', $leaderboard1->trigger_definition);
        $this->assertEquals(true, $leaderboard1->rank_asc);
        $this->assertEquals('VALUE', $leaderboard1->format);
        $this->assertEquals($oldOrder, $leaderboard1->order_column);
        $this->assertEquals(LeaderboardState::Active, $leaderboard1->state);

        // leaderboard trigger should be updated and initial version assigned
        // NOTE: the definition actually changed during the promote process. it should still be marked as version 1.
        $this->assertNotNull($leaderboard1->trigger);
        $this->assertEquals('STA:1=0::CAN:3=0::SUB:2=1::VAL:4=0', $leaderboard1->trigger->conditions);
        $this->assertEquals(1, $leaderboard1->trigger->version);

        // update audit log entry should be made
        $activity = $leaderboard1->auditLog->where('event', 'updated')->first();
        $this->assertNotNull($activity);
        $this->assertEquals($this->user->id, $activity->causer_id);
    });

    test('can update promoted someone elses', function () {
        $this->user->assignRole(Role::DEVELOPER);
        $game = UploadLeaderboardTestHelpers::createGame();
        // NOTE: developer does not need active claim to updated promoted leaderboards
        $user2 = User::factory()->create();
        $leaderboard1 = UploadLeaderboardTestHelpers::createPromotedLeaderboard($game, $user2);
        $oldOrder = $leaderboard1->order_column;

        $this->get(UploadLeaderboardTestHelpers::apiUrlWithChecksum($this->apiParams('uploadleaderboard', [
            'i' => $leaderboard1->id,
            'g' => $game->id,
            'n' => 'New Title',
            'd' => 'New Description',
            's' => '1=0',
            'b' => '2=1',
            'c' => '3=0',
            'l' => '4=0',
            'w' => 1,
            'f' => 'VALUE',
            'm' => 'active',
        ])))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'LeaderboardID' => 1,
            ]);

        $leaderboard1->refresh();
        $this->assertEquals('New Title', $leaderboard1->title);
        $this->assertEquals('New Description', $leaderboard1->description);
        $this->assertEquals('STA:1=0::CAN:3=0::SUB:2=1::VAL:4=0', $leaderboard1->trigger_definition);
        $this->assertEquals(true, $leaderboard1->rank_asc);
        $this->assertEquals('VALUE', $leaderboard1->format);
        $this->assertEquals($oldOrder, $leaderboard1->order_column);
        $this->assertEquals(LeaderboardState::Active, $leaderboard1->state);

        // leaderboard trigger should be updated, and version incremented
        $this->assertNotNull($leaderboard1->trigger);
        $this->assertEquals('STA:1=0::CAN:3=0::SUB:2=1::VAL:4=0', $leaderboard1->trigger->conditions);
        $this->assertEquals(2, $leaderboard1->trigger->version);

        // update audit log entry should be made
        $activity = $leaderboard1->auditLog->where('event', 'updated')->first();
        $this->assertNotNull($activity);
        $this->assertEquals($this->user->id, $activity->causer_id);
    });

    test('can demote someone elses', function () {
        $this->user->assignRole(Role::DEVELOPER);
        $game = UploadLeaderboardTestHelpers::createGame();
        // NOTE: developer does not need active claim to demote
        $user2 = User::factory()->create();
        $leaderboard1 = UploadLeaderboardTestHelpers::createPromotedLeaderboard($game, $user2);
        $oldOrder = $leaderboard1->order_column;

        $this->get(UploadLeaderboardTestHelpers::apiUrlWithChecksum($this->apiParams('uploadleaderboard', [
            'i' => $leaderboard1->id,
            'g' => $game->id,
            'n' => 'New Title',
            'd' => 'New Description',
            's' => '1=0',
            'b' => '2=1',
            'c' => '3=0',
            'l' => '4=0',
            'w' => 1,
            'f' => 'VALUE',
            'm' => 'unpromoted',
        ])))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'LeaderboardID' => 1,
            ]);

        $leaderboard1->refresh();
        $this->assertEquals('New Title', $leaderboard1->title);
        $this->assertEquals('New Description', $leaderboard1->description);
        $this->assertEquals('STA:1=0::CAN:3=0::SUB:2=1::VAL:4=0', $leaderboard1->trigger_definition);
        $this->assertEquals(true, $leaderboard1->rank_asc);
        $this->assertEquals('VALUE', $leaderboard1->format);
        $this->assertEquals($oldOrder, $leaderboard1->order_column);
        $this->assertEquals(LeaderboardState::Unpromoted, $leaderboard1->state);

        // leaderboard trigger should be updated and version incremented
        $this->assertNotNull($leaderboard1->trigger);
        $this->assertEquals('STA:1=0::CAN:3=0::SUB:2=1::VAL:4=0', $leaderboard1->trigger->conditions);
        $this->assertEquals(2, $leaderboard1->trigger->version);

        // update audit log entry should be made
        $activity = $leaderboard1->auditLog->where('event', 'updated')->first();
        $this->assertNotNull($activity);
        $this->assertEquals($this->user->id, $activity->causer_id);
    });

    test('can create new leaderboard via set id', function () {
        $this->user->assignRole(Role::DEVELOPER);

        // create a dummy AchievementSet so the id and achievement_set_id on the
        // GameAchievementSet for our test game differ.
        AchievementSet::factory()->create();

        $game = UploadLeaderboardTestHelpers::createGame();
        UploadLeaderboardTestHelpers::addClaim($game, $this->user);

        $achievementSet = GameAchievementSet::where('game_id', $game->id)->first();
        $this->assertNotEquals($achievementSet->id, $achievementSet->achievement_set_id);

        $this->get(UploadLeaderboardTestHelpers::apiUrlWithChecksum($this->apiParams('uploadleaderboard', [
            'p' => $achievementSet->achievement_set_id,
            'n' => 'Title',
            'd' => 'Description',
            's' => '1=0',
            'b' => '2=0',
            'c' => '3=0',
            'l' => '4=0',
            'w' => 1,
            'f' => 'VALUE',
        ])))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'LeaderboardID' => 1,
            ]);

        $leaderboard1 = Leaderboard::find(1);
        $this->assertEquals('Title', $leaderboard1->title);
        $this->assertEquals('Description', $leaderboard1->description);
        $this->assertEquals('STA:1=0::CAN:3=0::SUB:2=0::VAL:4=0', $leaderboard1->trigger_definition);
        $this->assertEquals(true, $leaderboard1->rank_asc);
        $this->assertEquals('VALUE', $leaderboard1->format);
        $this->assertEquals(1, $leaderboard1->order_column);
        $this->assertEquals(LeaderboardState::Active, $leaderboard1->state);

        // leaderboard should have a trigger
        $this->assertNotNull($leaderboard1->trigger);
        $this->assertEquals('STA:1=0::CAN:3=0::SUB:2=0::VAL:4=0', $leaderboard1->trigger->conditions);
        $this->assertEquals(1, $leaderboard1->trigger->version);

        // creation audit log entry should be made
        $activity = $leaderboard1->auditLog->where('event', 'created')->first();
        $this->assertNotNull($activity);
        $this->assertEquals($this->user->id, $activity->causer_id);
    });

    test('can create new leaderboard via virtual game id', function () {
        $this->user->assignRole(Role::DEVELOPER);
        $game = UploadLeaderboardTestHelpers::createGame();
        UploadLeaderboardTestHelpers::addClaim($game, $this->user);

        $this->get(UploadLeaderboardTestHelpers::apiUrlWithChecksum($this->apiParams('uploadleaderboard', [
            'g' => VirtualGameIdService::encodeVirtualGameId($game->id, GameHashCompatibility::Untested),
            'n' => 'Title',
            'd' => 'Description',
            's' => '1=0',
            'b' => '2=0',
            'c' => '3=0',
            'l' => '4=0',
            'w' => 1,
            'f' => 'VALUE',
        ])))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'LeaderboardID' => 1,
            ]);

        $leaderboard1 = Leaderboard::find(1);
        $this->assertEquals('Title', $leaderboard1->title);
        $this->assertEquals('Description', $leaderboard1->description);
        $this->assertEquals('STA:1=0::CAN:3=0::SUB:2=0::VAL:4=0', $leaderboard1->trigger_definition);
        $this->assertEquals(true, $leaderboard1->rank_asc);
        $this->assertEquals('VALUE', $leaderboard1->format);
        $this->assertEquals(1, $leaderboard1->order_column);
        $this->assertEquals(LeaderboardState::Active, $leaderboard1->state);

        // leaderboard should have a trigger
        $this->assertNotNull($leaderboard1->trigger);
        $this->assertEquals('STA:1=0::CAN:3=0::SUB:2=0::VAL:4=0', $leaderboard1->trigger->conditions);
        $this->assertEquals(1, $leaderboard1->trigger->version);

        // creation audit log entry should be made
        $activity = $leaderboard1->auditLog->where('event', 'created')->first();
        $this->assertNotNull($activity);
        $this->assertEquals($this->user->id, $activity->causer_id);
    });

    test('legacy call creates active leaderboard', function () {
        $this->user->assignRole(Role::DEVELOPER);
        $game = UploadLeaderboardTestHelpers::createGame();
        UploadLeaderboardTestHelpers::addClaim($game, $this->user);

        $this->get(UploadLeaderboardTestHelpers::apiUrlWithChecksum($this->apiParams('uploadleaderboard', [
            'g' => $game->id,
            'n' => 'Title',
            'd' => 'Description',
            's' => '1=0',
            'b' => '2=0',
            'c' => '3=0',
            'l' => '4=0',
            'w' => 1,
            'f' => 'VALUE',
            // legacy API doesn't specify state; should default to active
        ])))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'LeaderboardID' => 1,
            ]);

        $leaderboard1 = Leaderboard::find(1);
        $this->assertEquals('Title', $leaderboard1->title);
        $this->assertEquals('Description', $leaderboard1->description);
        $this->assertEquals('STA:1=0::CAN:3=0::SUB:2=0::VAL:4=0', $leaderboard1->trigger_definition);
        $this->assertEquals(true, $leaderboard1->rank_asc);
        $this->assertEquals('VALUE', $leaderboard1->format);
        $this->assertEquals(1, $leaderboard1->order_column);
        $this->assertEquals(LeaderboardState::Active, $leaderboard1->state);

        // leaderboard should have a trigger
        $this->assertNotNull($leaderboard1->trigger);
        $this->assertEquals('STA:1=0::CAN:3=0::SUB:2=0::VAL:4=0', $leaderboard1->trigger->conditions);
        $this->assertEquals(1, $leaderboard1->trigger->version);

        // creation audit log entry should be made
        $activity = $leaderboard1->auditLog->where('event', 'created')->first();
        $this->assertNotNull($activity);
        $this->assertEquals($this->user->id, $activity->causer_id);
    });

    test('legacy call does not promote unpromoted leaderboard', function () {
        $this->user->assignRole(Role::DEVELOPER);
        $game = UploadLeaderboardTestHelpers::createGame();
        UploadLeaderboardTestHelpers::addClaim($game, $this->user);
        $leaderboard1 = UploadLeaderboardTestHelpers::createUnpromotedLeaderboard($game, $this->user);

        $this->get(UploadLeaderboardTestHelpers::apiUrlWithChecksum($this->apiParams('uploadleaderboard', [
            'i' => $leaderboard1->id,
            'g' => $game->id,
            'n' => 'Title',
            'd' => 'Description',
            's' => '1=0',
            'b' => '2=0',
            'c' => '3=0',
            'l' => '4=0',
            'w' => 1,
            'f' => 'VALUE',
            // legacy API doesn't specify state; should default to active
        ])))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'LeaderboardID' => 1,
            ]);

        $leaderboard1->refresh();
        $this->assertEquals(LeaderboardState::Unpromoted, $leaderboard1->state);
    });

    test('can create new leaderboard for inactive system', function () {
        $this->user->assignRole(Role::DEVELOPER);
        $game = UploadLeaderboardTestHelpers::createGame();
        $game->system->active = false;
        $game->system->save();
        UploadLeaderboardTestHelpers::addClaim($game, $this->user);

        $this->get(UploadLeaderboardTestHelpers::apiUrlWithChecksum($this->apiParams('uploadleaderboard', [
            'g' => $game->id,
            'n' => 'Title',
            'd' => 'Description',
            's' => '1=0',
            'b' => '2=0',
            'c' => '3=0',
            'l' => '4=0',
            'w' => 1,
            'f' => 'VALUE',
            'm' => 'unpromoted',
        ])))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'LeaderboardID' => 1,
            ]);

        $leaderboard1 = Leaderboard::find(1);
        $this->assertEquals('Title', $leaderboard1->title);
        $this->assertEquals('Description', $leaderboard1->description);
        $this->assertEquals('STA:1=0::CAN:3=0::SUB:2=0::VAL:4=0', $leaderboard1->trigger_definition);
        $this->assertEquals(true, $leaderboard1->rank_asc);
        $this->assertEquals('VALUE', $leaderboard1->format);
        $this->assertEquals(1, $leaderboard1->order_column);
        $this->assertEquals(LeaderboardState::Unpromoted, $leaderboard1->state);

        // leaderboard should have a trigger without a version
        $this->assertNotNull($leaderboard1->trigger);
        $this->assertEquals('STA:1=0::CAN:3=0::SUB:2=0::VAL:4=0', $leaderboard1->trigger->conditions);
        $this->assertNull($leaderboard1->trigger->version);

        // creation audit log entry should be made
        $activity = $leaderboard1->auditLog->where('event', 'created')->first();
        $this->assertNotNull($activity);
        $this->assertEquals($this->user->id, $activity->causer_id);
    });

    test('cannot promote for inactive system', function () {
        $this->user->assignRole(Role::DEVELOPER);
        $game = UploadLeaderboardTestHelpers::createGame();
        $game->system->active = false;
        $game->system->save();
        // NOTE: developer does not need active claim to promote
        $leaderboard1 = UploadLeaderboardTestHelpers::createUnpromotedLeaderboard($game, $this->user);
        $oldOrder = $leaderboard1->order_column;

        $this->get(UploadLeaderboardTestHelpers::apiUrlWithChecksum($this->apiParams('uploadleaderboard', [
            'i' => $leaderboard1->id,
            'g' => $game->id,
            'n' => 'New Title',
            'd' => 'New Description',
            's' => '1=0',
            'b' => '2=1',
            'c' => '3=0',
            'l' => '4=0',
            'w' => 1,
            'f' => 'VALUE',
            'm' => 'active',
        ])))
            ->assertStatus(403)
            ->assertExactJson([
                'Status' => 403,
                'Code' => 'access_denied',
                'Success' => false,
                'Error' => 'You cannot promote leaderboards for a game from an unsupported console (console ID: 1).',
            ]);

        $leaderboard1->refresh();
        $this->assertEquals(LeaderboardState::Unpromoted, $leaderboard1->state);
    });
});

describe('junior developer', function () {
    test('can create new leaderboard', function () {
        $this->user->assignRole(Role::DEVELOPER_JUNIOR);
        $game = UploadLeaderboardTestHelpers::createGame();
        UploadLeaderboardTestHelpers::addClaim($game, $this->user);

        $this->get(UploadLeaderboardTestHelpers::apiUrlWithChecksum($this->apiParams('uploadleaderboard', [
            'g' => $game->id,
            'n' => 'Title',
            'd' => 'Description',
            's' => '1=0',
            'b' => '2=0',
            'c' => '3=0',
            'l' => '4=0',
            'w' => 1,
            'f' => 'VALUE',
            'm' => 'unpromoted',
        ])))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'LeaderboardID' => 1,
            ]);

        $leaderboard1 = Leaderboard::find(1);
        $this->assertEquals('Title', $leaderboard1->title);
        $this->assertEquals('Description', $leaderboard1->description);
        $this->assertEquals('STA:1=0::CAN:3=0::SUB:2=0::VAL:4=0', $leaderboard1->trigger_definition);
        $this->assertEquals(true, $leaderboard1->rank_asc);
        $this->assertEquals('VALUE', $leaderboard1->format);
        $this->assertEquals(1, $leaderboard1->order_column);
        $this->assertEquals(LeaderboardState::Unpromoted, $leaderboard1->state);

        // leaderboard should have an unversioned trigger
        $this->assertNotNull($leaderboard1->trigger);
        $this->assertEquals('STA:1=0::CAN:3=0::SUB:2=0::VAL:4=0', $leaderboard1->trigger->conditions);
        $this->assertNull($leaderboard1->trigger->version);

        // creation audit log entry should be made
        $activity = $leaderboard1->auditLog->where('event', 'created')->first();
        $this->assertNotNull($activity);
        $this->assertEquals($this->user->id, $activity->causer_id);
    });

    test('cannot create new leaderboard without claim', function () {
        $this->user->assignRole(Role::DEVELOPER_JUNIOR);
        $game = UploadLeaderboardTestHelpers::createGame();

        $this->get(UploadLeaderboardTestHelpers::apiUrlWithChecksum($this->apiParams('uploadleaderboard', [
            'g' => $game->id,
            'n' => 'Title',
            'd' => 'Description',
            's' => '1=0',
            'b' => '2=0',
            'c' => '3=0',
            'l' => '4=0',
            'w' => 1,
            'f' => 'VALUE',
            'm' => 'unpromoted',
        ])))
            ->assertStatus(403)
            ->assertExactJson([
                'Status' => 403,
                'Code' => 'access_denied',
                'Success' => false,
                'Error' => 'Access denied.',
            ]);

        $this->assertEquals(0, Leaderboard::count());
    });

    test('can create new leaderboard for collaboration claim', function () {
        $this->user->assignRole(Role::DEVELOPER_JUNIOR);
        $game = UploadLeaderboardTestHelpers::createGame();
        $claim = UploadLeaderboardTestHelpers::addClaim($game, $this->user);
        $claim->claim_type = ClaimType::Collaboration;
        $claim->save();

        $this->get(UploadLeaderboardTestHelpers::apiUrlWithChecksum($this->apiParams('uploadleaderboard', [
            'g' => $game->id,
            'n' => 'Title',
            'd' => 'Description',
            's' => '1=0',
            'b' => '2=0',
            'c' => '3=0',
            'l' => '4=0',
            'w' => 1,
            'f' => 'VALUE',
            'm' => 'unpromoted',
        ])))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'LeaderboardID' => 1,
            ]);

        $leaderboard1 = Leaderboard::find(1);
        $this->assertEquals('Title', $leaderboard1->title);
        $this->assertEquals('Description', $leaderboard1->description);
        $this->assertEquals('STA:1=0::CAN:3=0::SUB:2=0::VAL:4=0', $leaderboard1->trigger_definition);
        $this->assertEquals(true, $leaderboard1->rank_asc);
        $this->assertEquals('VALUE', $leaderboard1->format);
        $this->assertEquals(1, $leaderboard1->order_column);
        $this->assertEquals(LeaderboardState::Unpromoted, $leaderboard1->state);

        // leaderboard should have an unversioned trigger
        $this->assertNotNull($leaderboard1->trigger);
        $this->assertEquals('STA:1=0::CAN:3=0::SUB:2=0::VAL:4=0', $leaderboard1->trigger->conditions);
        $this->assertNull($leaderboard1->trigger->version);

        // creation audit log entry should be made
        $activity = $leaderboard1->auditLog->where('event', 'created')->first();
        $this->assertNotNull($activity);
        $this->assertEquals($this->user->id, $activity->causer_id);
    });

    test('can update unpromoted own', function () {
        $this->user->assignRole(Role::DEVELOPER_JUNIOR);
        $game = UploadLeaderboardTestHelpers::createGame();
        UploadLeaderboardTestHelpers::addClaim($game, $this->user);
        $leaderboard1 = UploadLeaderboardTestHelpers::createUnpromotedLeaderboard($game, $this->user);
        $oldOrder = $leaderboard1->order_column;

        $this->get(UploadLeaderboardTestHelpers::apiUrlWithChecksum($this->apiParams('uploadleaderboard', [
            'i' => $leaderboard1->id,
            'g' => $game->id,
            'n' => 'New Title',
            'd' => 'New Description',
            's' => '1=0',
            'b' => '2=1',
            'c' => '3=0',
            'l' => '4=0',
            'w' => 1,
            'f' => 'VALUE',
            'm' => 'unpromoted',
        ])))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'LeaderboardID' => 1,
            ]);

        $leaderboard1->refresh();
        $this->assertEquals('New Title', $leaderboard1->title);
        $this->assertEquals('New Description', $leaderboard1->description);
        $this->assertEquals('STA:1=0::CAN:3=0::SUB:2=1::VAL:4=0', $leaderboard1->trigger_definition);
        $this->assertEquals(true, $leaderboard1->rank_asc);
        $this->assertEquals('VALUE', $leaderboard1->format);
        $this->assertEquals($oldOrder, $leaderboard1->order_column);
        $this->assertEquals(LeaderboardState::Unpromoted, $leaderboard1->state);

        // leaderboard trigger should be updated, but version not incremented
        $this->assertNotNull($leaderboard1->trigger);
        $this->assertEquals('STA:1=0::CAN:3=0::SUB:2=1::VAL:4=0', $leaderboard1->trigger->conditions);
        $this->assertNull($leaderboard1->trigger->version);

        // update audit log entry should be made
        $activity = $leaderboard1->auditLog->where('event', 'updated')->first();
        $this->assertNotNull($activity);
        $this->assertEquals($this->user->id, $activity->causer_id);
    });

    test('cannot promote own', function () {
        $this->user->assignRole(Role::DEVELOPER_JUNIOR);
        $game = UploadLeaderboardTestHelpers::createGame();
        UploadLeaderboardTestHelpers::addClaim($game, $this->user);
        $leaderboard1 = UploadLeaderboardTestHelpers::createUnpromotedLeaderboard($game, $this->user);
        $oldOrder = $leaderboard1->order_column;

        $this->get(UploadLeaderboardTestHelpers::apiUrlWithChecksum($this->apiParams('uploadleaderboard', [
            'i' => $leaderboard1->id,
            'g' => $game->id,
            'n' => 'New Title',
            'd' => 'New Description',
            's' => '1=0',
            'b' => '2=1',
            'c' => '3=0',
            'l' => '4=0',
            'w' => 1,
            'f' => 'VALUE',
            'm' => 'active',
        ])))
            ->assertStatus(403)
            ->assertExactJson([
                'Status' => 403,
                'Code' => 'access_denied',
                'Success' => false,
                'Error' => 'You must be a developer to perform this action! Please drop a message in the forums to apply.',
            ]);

        $leaderboard1->refresh();
        $this->assertEquals(LeaderboardState::Unpromoted, $leaderboard1->state);
    });

    test('cannot update promoted own', function () {
        $this->user->assignRole(Role::DEVELOPER_JUNIOR);
        $game = UploadLeaderboardTestHelpers::createGame();
        UploadLeaderboardTestHelpers::addClaim($game, $this->user);
        $leaderboard1 = UploadLeaderboardTestHelpers::createPromotedLeaderboard($game, $this->user);
        $oldOrder = $leaderboard1->order_column;

        $this->get(UploadLeaderboardTestHelpers::apiUrlWithChecksum($this->apiParams('uploadleaderboard', [
            'i' => $leaderboard1->id,
            'g' => $game->id,
            'n' => 'New Title',
            'd' => 'New Description',
            's' => '1=0',
            'b' => '2=1',
            'c' => '3=0',
            'l' => '4=0',
            'w' => 1,
            'f' => 'VALUE',
            'm' => 'active',
        ])))
            ->assertStatus(403)
            ->assertExactJson([
                'Status' => 403,
                'Code' => 'access_denied',
                'Success' => false,
                'Error' => 'You must be a developer to perform this action! Please drop a message in the forums to apply.',
            ]);
    });

    test('cannot demote own', function () {
        $this->user->assignRole(Role::DEVELOPER_JUNIOR);
        $game = UploadLeaderboardTestHelpers::createGame();
        UploadLeaderboardTestHelpers::addClaim($game, $this->user);
        $leaderboard1 = UploadLeaderboardTestHelpers::createPromotedLeaderboard($game, $this->user);
        $oldOrder = $leaderboard1->order_column;

        $this->get(UploadLeaderboardTestHelpers::apiUrlWithChecksum($this->apiParams('uploadleaderboard', [
            'i' => $leaderboard1->id,
            'g' => $game->id,
            'n' => 'New Title',
            'd' => 'New Description',
            's' => '1=0',
            'b' => '2=1',
            'c' => '3=0',
            'l' => '4=0',
            'w' => 1,
            'f' => 'VALUE',
            'm' => 'unpromoted',
        ])))
            ->assertStatus(403)
            ->assertExactJson([
                'Status' => 403,
                'Code' => 'access_denied',
                'Success' => false,
                'Error' => 'You must be a developer to perform this action! Please drop a message in the forums to apply.',
            ]);

        $leaderboard1->refresh();
        $this->assertEquals(LeaderboardState::Active, $leaderboard1->state);
    });

    test('cannot update unpromoted someone elses', function () {
        $this->user->assignRole(Role::DEVELOPER_JUNIOR);
        $game = UploadLeaderboardTestHelpers::createGame();
        UploadLeaderboardTestHelpers::addClaim($game, $this->user);
        $user2 = User::factory()->create();
        $leaderboard1 = UploadLeaderboardTestHelpers::createUnpromotedLeaderboard($game, $user2);
        $oldOrder = $leaderboard1->order_column;

        $this->get(UploadLeaderboardTestHelpers::apiUrlWithChecksum($this->apiParams('uploadleaderboard', [
            'i' => $leaderboard1->id,
            'g' => $game->id,
            'n' => 'New Title',
            'd' => 'New Description',
            's' => '1=0',
            'b' => '2=1',
            'c' => '3=0',
            'l' => '4=0',
            'w' => 1,
            'f' => 'VALUE',
            'm' => 'unpromoted',
        ])))
            ->assertStatus(403)
            ->assertExactJson([
                'Status' => 403,
                'Code' => 'access_denied',
                'Success' => false,
                'Error' => 'You must be a developer to perform this action! Please drop a message in the forums to apply.',
            ]);

        $leaderboard1->refresh();
        $this->assertNotEquals('New Title', $leaderboard1->title);
    });

    test('cannot update promoted someone elses', function () {
        $this->user->assignRole(Role::DEVELOPER_JUNIOR);
        $game = UploadLeaderboardTestHelpers::createGame();
        UploadLeaderboardTestHelpers::addClaim($game, $this->user);
        $user2 = User::factory()->create();
        $leaderboard1 = UploadLeaderboardTestHelpers::createPromotedLeaderboard($game, $user2);
        $oldOrder = $leaderboard1->order_column;

        $this->get(UploadLeaderboardTestHelpers::apiUrlWithChecksum($this->apiParams('uploadleaderboard', [
            'i' => $leaderboard1->id,
            'g' => $game->id,
            'n' => 'New Title',
            'd' => 'New Description',
            's' => '1=0',
            'b' => '2=1',
            'c' => '3=0',
            'l' => '4=0',
            'w' => 1,
            'f' => 'VALUE',
            'm' => 'active',
        ])))
            ->assertStatus(403)
            ->assertExactJson([
                'Status' => 403,
                'Code' => 'access_denied',
                'Success' => false,
                'Error' => 'You must be a developer to perform this action! Please drop a message in the forums to apply.',
            ]);
    });

    test('cannot demote someone elses', function () {
        $this->user->assignRole(Role::DEVELOPER_JUNIOR);
        $game = UploadLeaderboardTestHelpers::createGame();
        UploadLeaderboardTestHelpers::addClaim($game, $this->user);
        $user2 = User::factory()->create();
        $leaderboard1 = UploadLeaderboardTestHelpers::createPromotedLeaderboard($game, $user2);
        $oldOrder = $leaderboard1->order_column;

        $this->get(UploadLeaderboardTestHelpers::apiUrlWithChecksum($this->apiParams('uploadleaderboard', [
            'i' => $leaderboard1->id,
            'g' => $game->id,
            'n' => 'New Title',
            'd' => 'New Description',
            's' => '1=0',
            'b' => '2=1',
            'c' => '3=0',
            'l' => '4=0',
            'w' => 1,
            'f' => 'VALUE',
            'm' => 'unpromoted',
        ])))
            ->assertStatus(403)
            ->assertExactJson([
                'Status' => 403,
                'Code' => 'access_denied',
                'Success' => false,
                'Error' => 'You must be a developer to perform this action! Please drop a message in the forums to apply.',
            ]);

        $leaderboard1->refresh();
        $this->assertEquals(LeaderboardState::Active, $leaderboard1->state);
    });

    test('cannot promote someone elses', function () {
        $this->user->assignRole(Role::DEVELOPER_JUNIOR);
        $game = UploadLeaderboardTestHelpers::createGame();
        UploadLeaderboardTestHelpers::addClaim($game, $this->user);
        $user2 = User::factory()->create();
        $leaderboard1 = UploadLeaderboardTestHelpers::createUnpromotedLeaderboard($game, $user2);
        $oldOrder = $leaderboard1->order_column;

        $this->get(UploadLeaderboardTestHelpers::apiUrlWithChecksum($this->apiParams('uploadleaderboard', [
            'i' => $leaderboard1->id,
            'g' => $game->id,
            'n' => 'New Title',
            'd' => 'New Description',
            's' => '1=0',
            'b' => '2=1',
            'c' => '3=0',
            'l' => '4=0',
            'w' => 1,
            'f' => 'VALUE',
            'm' => 'active',
        ])))
            ->assertStatus(403)
            ->assertExactJson([
                'Status' => 403,
                'Code' => 'access_denied',
                'Success' => false,
                'Error' => 'You must be a developer to perform this action! Please drop a message in the forums to apply.',
            ]);

        $leaderboard1->refresh();
        $this->assertEquals(LeaderboardState::Unpromoted, $leaderboard1->state);
    });

    test('can create new leaderboard via set id', function () {
        $this->user->assignRole(Role::DEVELOPER_JUNIOR);

        // create a dummy AchievementSet so the id and achievement_set_id on the
        // GameAchievementSet for our test game differ.
        AchievementSet::factory()->create();

        $game = UploadLeaderboardTestHelpers::createGame();
        UploadLeaderboardTestHelpers::addClaim($game, $this->user);

        $achievementSet = GameAchievementSet::where('game_id', $game->id)->first();
        $this->assertNotEquals($achievementSet->id, $achievementSet->achievement_set_id);

        $this->get(UploadLeaderboardTestHelpers::apiUrlWithChecksum($this->apiParams('uploadleaderboard', [
            'p' => $achievementSet->achievement_set_id,
            'n' => 'Title',
            'd' => 'Description',
            's' => '1=0',
            'b' => '2=0',
            'c' => '3=0',
            'l' => '4=0',
            'w' => 1,
            'f' => 'VALUE',
        ])))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'LeaderboardID' => 1,
            ]);

        $leaderboard1 = Leaderboard::find(1);
        $this->assertEquals('Title', $leaderboard1->title);
        $this->assertEquals('Description', $leaderboard1->description);
        $this->assertEquals('STA:1=0::CAN:3=0::SUB:2=0::VAL:4=0', $leaderboard1->trigger_definition);
        $this->assertEquals(true, $leaderboard1->rank_asc);
        $this->assertEquals('VALUE', $leaderboard1->format);
        $this->assertEquals(1, $leaderboard1->order_column);
        $this->assertEquals(LeaderboardState::Active, $leaderboard1->state);

        // leaderboard should have a trigger
        $this->assertNotNull($leaderboard1->trigger);
        $this->assertEquals('STA:1=0::CAN:3=0::SUB:2=0::VAL:4=0', $leaderboard1->trigger->conditions);
        $this->assertEquals(1, $leaderboard1->trigger->version);

        // creation audit log entry should be made
        $activity = $leaderboard1->auditLog->where('event', 'created')->first();
        $this->assertNotNull($activity);
        $this->assertEquals($this->user->id, $activity->causer_id);
    });

    test('can create new leaderboard via virtual game id', function () {
        $this->user->assignRole(Role::DEVELOPER_JUNIOR);
        $game = UploadLeaderboardTestHelpers::createGame();
        UploadLeaderboardTestHelpers::addClaim($game, $this->user);

        $this->get(UploadLeaderboardTestHelpers::apiUrlWithChecksum($this->apiParams('uploadleaderboard', [
            'g' => VirtualGameIdService::encodeVirtualGameId($game->id, GameHashCompatibility::Untested),
            'n' => 'Title',
            'd' => 'Description',
            's' => '1=0',
            'b' => '2=0',
            'c' => '3=0',
            'l' => '4=0',
            'w' => 1,
            'f' => 'VALUE',
        ])))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'LeaderboardID' => 1,
            ]);

        $leaderboard1 = Leaderboard::find(1);
        $this->assertEquals('Title', $leaderboard1->title);
        $this->assertEquals('Description', $leaderboard1->description);
        $this->assertEquals('STA:1=0::CAN:3=0::SUB:2=0::VAL:4=0', $leaderboard1->trigger_definition);
        $this->assertEquals(true, $leaderboard1->rank_asc);
        $this->assertEquals('VALUE', $leaderboard1->format);
        $this->assertEquals(1, $leaderboard1->order_column);
        $this->assertEquals(LeaderboardState::Active, $leaderboard1->state);

        // leaderboard should have a trigger
        $this->assertNotNull($leaderboard1->trigger);
        $this->assertEquals('STA:1=0::CAN:3=0::SUB:2=0::VAL:4=0', $leaderboard1->trigger->conditions);
        $this->assertEquals(1, $leaderboard1->trigger->version);

        // creation audit log entry should be made
        $activity = $leaderboard1->auditLog->where('event', 'created')->first();
        $this->assertNotNull($activity);
        $this->assertEquals($this->user->id, $activity->causer_id);
    });

    test('legacy call creates active leaderboard', function () {
        $this->user->assignRole(Role::DEVELOPER_JUNIOR);
        $game = UploadLeaderboardTestHelpers::createGame();
        UploadLeaderboardTestHelpers::addClaim($game, $this->user);

        $this->get(UploadLeaderboardTestHelpers::apiUrlWithChecksum($this->apiParams('uploadleaderboard', [
            'g' => $game->id,
            'n' => 'Title',
            'd' => 'Description',
            's' => '1=0',
            'b' => '2=0',
            'c' => '3=0',
            'l' => '4=0',
            'w' => 1,
            'f' => 'VALUE',
            // legacy API doesn't specify state; should default to active
        ])))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'LeaderboardID' => 1,
            ]);

        $leaderboard1 = Leaderboard::find(1);
        $this->assertEquals('Title', $leaderboard1->title);
        $this->assertEquals('Description', $leaderboard1->description);
        $this->assertEquals('STA:1=0::CAN:3=0::SUB:2=0::VAL:4=0', $leaderboard1->trigger_definition);
        $this->assertEquals(true, $leaderboard1->rank_asc);
        $this->assertEquals('VALUE', $leaderboard1->format);
        $this->assertEquals(1, $leaderboard1->order_column);
        $this->assertEquals(LeaderboardState::Active, $leaderboard1->state);

        // leaderboard should have a trigger
        $this->assertNotNull($leaderboard1->trigger);
        $this->assertEquals('STA:1=0::CAN:3=0::SUB:2=0::VAL:4=0', $leaderboard1->trigger->conditions);
        $this->assertEquals(1, $leaderboard1->trigger->version);

        // creation audit log entry should be made
        $activity = $leaderboard1->auditLog->where('event', 'created')->first();
        $this->assertNotNull($activity);
        $this->assertEquals($this->user->id, $activity->causer_id);
    });

    test('legacy call updates own active leaderboard', function () {
        $this->user->assignRole(Role::DEVELOPER_JUNIOR);
        $game = UploadLeaderboardTestHelpers::createGame();
        UploadLeaderboardTestHelpers::addClaim($game, $this->user);
        $leaderboard1 = UploadLeaderboardTestHelpers::createPromotedLeaderboard($game, $this->user);
        $oldOrder = $leaderboard1->order_column;

        $this->get(UploadLeaderboardTestHelpers::apiUrlWithChecksum($this->apiParams('uploadleaderboard', [
            'i' => $leaderboard1->id,
            'g' => $game->id,
            'n' => 'New Title',
            'd' => 'New Description',
            's' => '1=0',
            'b' => '2=1',
            'c' => '3=0',
            'l' => '4=0',
            'w' => 1,
            'f' => 'VALUE',
            // legacy API doesn't specify state; should default to active
        ])))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'LeaderboardID' => 1,
            ]);

        $leaderboard1->refresh();
        $this->assertEquals('New Title', $leaderboard1->title);
        $this->assertEquals('New Description', $leaderboard1->description);
        $this->assertEquals('STA:1=0::CAN:3=0::SUB:2=1::VAL:4=0', $leaderboard1->trigger_definition);
        $this->assertEquals(true, $leaderboard1->rank_asc);
        $this->assertEquals('VALUE', $leaderboard1->format);
        $this->assertEquals($oldOrder, $leaderboard1->order_column);
        $this->assertEquals(LeaderboardState::Active, $leaderboard1->state);

        // leaderboard trigger should be updated
        $this->assertNotNull($leaderboard1->trigger);
        $this->assertEquals('STA:1=0::CAN:3=0::SUB:2=1::VAL:4=0', $leaderboard1->trigger->conditions);
        $this->assertEquals(2, $leaderboard1->trigger->version);

        // update audit log entry should be made
        $activity = $leaderboard1->auditLog->where('event', 'updated')->first();
        $this->assertNotNull($activity);
        $this->assertEquals($this->user->id, $activity->causer_id);
    });
});

describe('non-developer', function () {
    test('writer can update title and description without claim', function () {
        $this->user->assignRole(Role::WRITER);
        $game = UploadLeaderboardTestHelpers::createGame();
        $user2 = User::factory()->create();
        $leaderboard1 = UploadLeaderboardTestHelpers::createPromotedLeaderboard($game, $user2);
        $oldOrder = $leaderboard1->order_column;

        $this->get(UploadLeaderboardTestHelpers::apiUrlWithChecksum($this->apiParams('uploadleaderboard', [
            'i' => $leaderboard1->id,
            'g' => $game->id,
            'n' => 'New Title',
            'd' => 'New Description',
            's' => '1=0',
            'b' => '2=0',
            'c' => '3=0',
            'l' => '4=0',
            'w' => 0,
            'f' => 'VALUE',
        ])))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'LeaderboardID' => 1,
            ]);

        $leaderboard1->refresh();
        $this->assertEquals('New Title', $leaderboard1->title);
        $this->assertEquals('New Description', $leaderboard1->description);
        $this->assertEquals('STA:1=0::CAN:3=0::SUB:2=0::VAL:4=0', $leaderboard1->trigger_definition);
        $this->assertEquals(false, $leaderboard1->rank_asc);
        $this->assertEquals('VALUE', $leaderboard1->format);
        $this->assertEquals($oldOrder, $leaderboard1->order_column);
        $this->assertEquals(LeaderboardState::Active, $leaderboard1->state);
    });

    test('writer cannot update logic', function () {
        $this->user->assignRole(Role::WRITER);
        $game = UploadLeaderboardTestHelpers::createGame();
        $user2 = User::factory()->create();
        $leaderboard1 = UploadLeaderboardTestHelpers::createPromotedLeaderboard($game, $user2);

        $this->get(UploadLeaderboardTestHelpers::apiUrlWithChecksum($this->apiParams('uploadleaderboard', [
            'i' => $leaderboard1->id,
            'g' => $game->id,
            'n' => $leaderboard1->title,
            'd' => $leaderboard1->description,
            's' => '1=0',
            'b' => '2=1',
            'c' => '3=0',
            'l' => '4=0',
            'w' => 1,
            'f' => 'VALUE',
        ])))
            ->assertStatus(403)
            ->assertExactJson([
                'Status' => 403,
                'Code' => 'access_denied',
                'Success' => false,
                'Error' => 'Access denied.',
            ]);

        $leaderboard1->refresh();
        $this->assertEquals('STA:1=0::CAN:3=0::SUB:2=0::VAL:4=0', $leaderboard1->trigger_definition);
    });

    test('writer cannot update format', function () {
        $this->user->assignRole(Role::WRITER);
        $game = UploadLeaderboardTestHelpers::createGame();
        $user2 = User::factory()->create();
        $leaderboard1 = UploadLeaderboardTestHelpers::createPromotedLeaderboard($game, $user2);

        $this->get(UploadLeaderboardTestHelpers::apiUrlWithChecksum($this->apiParams('uploadleaderboard', [
            'i' => $leaderboard1->id,
            'g' => $game->id,
            'n' => $leaderboard1->title,
            'd' => $leaderboard1->description,
            's' => '1=0',
            'b' => '2=0',
            'c' => '3=0',
            'l' => '4=0',
            'w' => 1,
            'f' => 'SCORE',
        ])))
            ->assertStatus(403)
            ->assertExactJson([
                'Status' => 403,
                'Code' => 'access_denied',
                'Success' => false,
                'Error' => 'Access denied.',
            ]);

        $leaderboard1->refresh();
        $this->assertEquals('VALUE', $leaderboard1->format);
    });

    test('non-developer cannot update title or description', function () {
        $this->user->assignRole(Role::WRITER);
        $game = UploadLeaderboardTestHelpers::createGame();
        $user2 = User::factory()->create();
        $leaderboard1 = UploadLeaderboardTestHelpers::createPromotedLeaderboard($game, $user2);

        $this->get(UploadLeaderboardTestHelpers::apiUrlWithChecksum($this->apiParams('uploadleaderboard', [
            'i' => $leaderboard1->id,
            'g' => $game->id,
            'n' => "New Title",
            'd' => "New Description",
            's' => '1=0',
            'b' => '2=0',
            'c' => '3=0',
            'l' => '4=0',
            'w' => 1,
            'f' => 'VALUE',
        ])))
            ->assertStatus(403)
            ->assertExactJson([
                'Status' => 403,
                'Code' => 'access_denied',
                'Success' => false,
                'Error' => 'Access denied.',
            ]);
    });
});

describe('validation', function () {
    test('g or p is required', function () {
        $this->user->assignRole(Role::DEVELOPER);
        $game = UploadLeaderboardTestHelpers::createGame();
        UploadLeaderboardTestHelpers::addClaim($game, $this->user);

        $this->get(UploadLeaderboardTestHelpers::apiUrlWithChecksum($this->apiParams('uploadleaderboard', [
            'n' => 'Title',
            'd' => 'Description',
            's' => '1=0',
            'b' => '2=0',
            'c' => '3=0',
            'l' => '4=0',
            'w' => 1,
            'f' => 'VALUE',
            'm' => 'unpromoted',
        ])))
            ->assertStatus(422)
            ->assertExactJson([
                'Status' => 422,
                'Code' => 'missing_parameter',
                'Success' => false,
                'Error' => 'One or more required parameters is missing.',
            ]);
    });

    test('create with invalid format', function () {
        $this->user->assignRole(Role::DEVELOPER);
        $game = UploadLeaderboardTestHelpers::createGame();
        UploadLeaderboardTestHelpers::addClaim($game, $this->user);

        $this->get(UploadLeaderboardTestHelpers::apiUrlWithChecksum($this->apiParams('uploadleaderboard', [
            'g' => $game->id,
            'n' => 'New Title',
            'd' => 'New Description',
            's' => '1=0',
            'b' => '2=1',
            'c' => '3=0',
            'l' => '4=0',
            'w' => 1,
            'f' => 'unknown',
        ])))
            ->assertStatus(422)
            ->assertExactJson([
                'Status' => 422,
                'Code' => 'invalid_parameter',
                'Success' => false,
                'Error' => 'Unknown format: unknown',
            ]);

        $this->assertEquals(0, Leaderboard::count());
    });

    test('update with invalid format', function () {
        $this->user->assignRole(Role::DEVELOPER);
        $game = UploadLeaderboardTestHelpers::createGame();
        UploadLeaderboardTestHelpers::addClaim($game, $this->user);
        $leaderboard1 = UploadLeaderboardTestHelpers::createPromotedLeaderboard($game, $this->user);

        $this->get(UploadLeaderboardTestHelpers::apiUrlWithChecksum($this->apiParams('uploadleaderboard', [
            'i' => $leaderboard1->id,
            'g' => $game->id,
            'n' => 'New Title',
            'd' => 'New Description',
            's' => '1=0',
            'b' => '2=1',
            'c' => '3=0',
            'l' => '4=0',
            'w' => 1,
            'f' => 'unknown',
        ])))
            ->assertStatus(422)
            ->assertExactJson([
                'Status' => 422,
                'Code' => 'invalid_parameter',
                'Success' => false,
                'Error' => 'Unknown format: unknown',
            ]);

        $leaderboard1->refresh();
        $this->assertEquals('VALUE', $leaderboard1->format);
    });

    test('unknown leaderboard', function () {
        $this->user->assignRole(Role::DEVELOPER);
        $game = UploadLeaderboardTestHelpers::createGame();
        UploadLeaderboardTestHelpers::addClaim($game, $this->user);

        $this->get(UploadLeaderboardTestHelpers::apiUrlWithChecksum($this->apiParams('uploadleaderboard', [
            'i' => 999999,
            'g' => $game->id,
            'n' => 'New Title',
            'd' => 'New Description',
            's' => '1=0',
            'b' => '2=1',
            'c' => '3=0',
            'l' => '4=0',
            'w' => 1,
            'f' => 'VALUE',
        ])))
            ->assertStatus(404)
            ->assertExactJson([
                'Status' => 404,
                'Code' => 'not_found',
                'Success' => false,
                'Error' => 'Unknown leaderboard.',
            ]);

        $this->assertEquals(0, Leaderboard::count());
    });

    test('unknown game', function () {
        $this->user->assignRole(Role::DEVELOPER);

        $this->get(UploadLeaderboardTestHelpers::apiUrlWithChecksum($this->apiParams('uploadleaderboard', [
            'g' => 999999,
            'n' => 'New Title',
            'd' => 'New Description',
            's' => '1=0',
            'b' => '2=1',
            'c' => '3=0',
            'l' => '4=0',
            'w' => 1,
            'f' => 'VALUE',
        ])))
            ->assertStatus(404)
            ->assertExactJson([
                'Status' => 404,
                'Code' => 'not_found',
                'Success' => false,
                'Error' => 'Unknown game.',
            ]);

        $this->assertEquals(0, Leaderboard::count());
    });
});
