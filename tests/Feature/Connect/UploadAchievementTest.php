<?php

declare(strict_types=1);

namespace Tests\Feature\Connect;

use App\Enums\GameHashCompatibility;
use App\Models\Achievement;
use App\Models\AchievementSetClaim;
use App\Models\Game;
use App\Models\GameAchievementSet;
use App\Models\GameHash;
use App\Models\PlayerGame;
use App\Models\Role;
use App\Models\System;
use App\Models\Trigger;
use App\Models\User;
use App\Platform\Actions\UpdatePlayerGameMetricsAction;
use App\Platform\Enums\AchievementType;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Feature\Concerns\TestsEmulatorUserAgent;
use Tests\Feature\Platform\Concerns\TestsPlayerAchievements;

uses(LazilyRefreshDatabase::class);
uses(TestsConnect::class);
uses(TestsEmulatorUserAgent::class);
uses(TestsPlayerAchievements::class);

class UploadAchievementTestHelpers
{
    public static function createGame(): Game
    {
        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create(['system_id' => $system->id]);
        /** @var GameAchievementSet $gameAchievementSet */
        $gameAchievementSet = GameAchievementSet::factory()->create(['game_id' => $game->id]);
        /** @var GameHash $gameHash */
        $gameHash = GameHash::create([
            'game_id' => $game->id,
            'system_id' => $game->system_id,
            'compatibility' => GameHashCompatibility::Compatible,
            'md5' => fake()->md5,
            'name' => 'hash_' . $game->id,
            'description' => 'hash_' . $game->id,
        ]);

        return $game;
    }

    public static function createUnpromotedAchievement(Game $game, User $author): Achievement
    {
        $achievement = Achievement::Factory()->for($game)->create([
            'user_id' => $author->id,
            'trigger_definition' => '0xH0000=1',
        ]);

        $trigger = $achievement->trigger()->save(new Trigger([
            'conditions' => $achievement->trigger_definition,
            'version' => null,
            'user_id' => $author->id,
        ]));
        $achievement->update(['trigger_id' => $trigger->id]);

        return $achievement;
    }

    public static function createPromotedAchievement(Game $game, User $author): Achievement
    {
        $achievement = Achievement::Factory()->for($game)->promoted()->create([
            'user_id' => $author->id,
            'trigger_definition' => '0xH0000=1',
        ]);

        $trigger = $achievement->trigger()->save(new Trigger([
            'conditions' => $achievement->trigger_definition,
            'version' => 1,
            'user_id' => $author->id,
        ]));
        $achievement->update(['trigger_id' => $trigger->id]);

        return $achievement;
    }

    public static function addClaim(Game $game, User $user): AchievementSetClaim
    {
        return AchievementSetClaim::factory()->create([
            'user_id' => $user->id,
            'game_id' => $game->id,
        ]);
    }
}

beforeEach(function () {
    Carbon::setTestNow(Carbon::now());

    $this->seedEmulatorUserAgents();
    $this->createConnectUser();

    Role::create(['name' => Role::DEVELOPER, 'display' => 1]);
    Role::create(['name' => Role::DEVELOPER_JUNIOR, 'display' => 2]);
});

describe('developer', function () {
    test('can create new achievement', function () {
        $this->user->assignRole(Role::DEVELOPER);
        $game = UploadAchievementTestHelpers::createGame();
        UploadAchievementTestHelpers::addClaim($game, $this->user);

        $this->get($this->apiUrl('uploadachievement', [
            'g' => $game->id,
            'n' => 'Title1',
            'd' => 'Description1',
            'z' => 5,
            'm' => '0xH0000=1',
            'f' => 5, // Unpromoted - hardcode for test to prevent false success if enum changes
            'b' => '001234',
        ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => 1,
                'Error' => '',
            ]);

        $achievement = Achievement::findOrFail(1);
        $this->assertEquals($game->id, $achievement->game_id);
        $this->assertEquals('Title1', $achievement->title);
        $this->assertEquals('Description1', $achievement->description);
        $this->assertEquals('0xH0000=1', $achievement->trigger_definition);
        $this->assertEquals(5, $achievement->points);
        $this->assertFalse($achievement->is_promoted);
        $this->assertNull($achievement->type);
        $this->assertEquals($this->user->id, $achievement->user_id);
        $this->assertEquals('001234', $achievement->image_name);
        $this->assertNotNull($achievement->modified_at);

        $game->refresh();
        $this->assertEquals(0, $game->achievements_published);
        $this->assertEquals(1, $game->achievements_unpublished);
        $this->assertEquals(0, $game->points_total); // unpromoted achievements don't contribute to points_total

        // achievement should have a trigger, but it should be unversioned
        $this->assertNotNull($achievement->trigger);
        $this->assertEquals('0xH0000=1', $achievement->trigger->conditions);
        $this->assertNull($achievement->trigger->version);

        // achievement should also be added to the core achievement set
        $coreSet = $game->gameAchievementSets()->core()->first()->achievementSet;
        $this->assertEquals(1, $coreSet->achievements()->count());
        $this->assertEquals($achievement->id, $coreSet->achievements()->first()->id);
        $this->assertEquals(0, $coreSet->achievements_published);
        $this->assertEquals(1, $coreSet->achievements_unpublished);
        $this->assertEquals(0, $coreSet->points_total);
    });

    test('cannot create new achievement without claim', function () {
        $this->user->assignRole(Role::DEVELOPER);
        $game = UploadAchievementTestHelpers::createGame();

        $this->get($this->apiUrl('uploadachievement', [
            'g' => $game->id,
            'n' => 'Title1',
            'd' => 'Description1',
            'z' => 5,
            'm' => '0xH0000=1',
            'f' => 5, // Unpromoted - hardcode for test to prevent false success if enum changes
            'b' => '001234',
        ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => false,
                'AchievementID' => 0,
                'Error' => 'You must have an active claim on this game to perform this action.',
            ]);

        $this->assertEquals(0, Achievement::count());

        $game->refresh();
        $this->assertEquals(0, $game->achievements_published);
        $this->assertEquals(0, $game->achievements_unpublished);
    });

    test('can update unpromoted own', function () {
        $this->user->assignRole(Role::DEVELOPER);
        $game = UploadAchievementTestHelpers::createGame();
        $achievement = UploadAchievementTestHelpers::createUnpromotedAchievement($game, $this->user);
        $triggerVersion = $achievement->trigger_id;
        // NOTE: developer does not need active claim to update existing

        $this->get($this->apiUrl('uploadachievement', [
            'a' => $achievement->id,
            'g' => $game->id,
            'n' => 'Title2',
            'd' => 'Description2',
            'z' => 10,
            'm' => '0xH0000=2',
            'f' => 5,
            'b' => '002345',
            'x' => 'progression', // hard-code in case enum changes
        ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement->id,
                'Error' => '',
            ]);

        $achievement->refresh();

        $this->assertEquals($game->id, $achievement->game_id);
        $this->assertEquals('Title2', $achievement->title);
        $this->assertEquals('Description2', $achievement->description);
        $this->assertEquals('0xH0000=2', $achievement->trigger_definition);
        $this->assertEquals(10, $achievement->points);
        $this->assertFalse($achievement->is_promoted);
        $this->assertEquals(AchievementType::Progression, $achievement->type);
        $this->assertEquals($this->user->id, $achievement->user_id);
        $this->assertEquals('002345', $achievement->image_name);
        $this->assertNotNull($achievement->modified_at);
        $this->assertEquals($triggerVersion, $achievement->trigger_id); // changes before promoting aren't tracked

        $game->refresh();
        $this->assertEquals(0, $game->achievements_published);
        $this->assertEquals(1, $game->achievements_unpublished);
        $this->assertEquals(0, $game->points_total);

        // unversioned trigger should be updated in place
        $this->assertNotNull($achievement->trigger);
        $this->assertEquals('0xH0000=2', $achievement->trigger->conditions);
        $this->assertNull($achievement->trigger->version);
        $this->assertEquals($triggerVersion, $achievement->trigger->id);
    });

    test('can promote own', function () {
        $this->user->assignRole(Role::DEVELOPER);
        $game = UploadAchievementTestHelpers::createGame();
        $achievement = UploadAchievementTestHelpers::createUnpromotedAchievement($game, $this->user);
        $triggerVersion = $achievement->trigger_id;
        // NOTE: developer does not need active claim to promote

        $this->get($this->apiUrl('uploadachievement', [
            'a' => $achievement->id,
            'g' => $game->id,
            'n' => 'Title1',
            'd' => 'Description1',
            'z' => 5,
            'm' => '0xH0000=1',
            'f' => 3, // Publish - hardcode for test to prevent false success if enum changes
            'b' => '001234',
        ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement->id,
                'Error' => '',
            ]);

        $achievement->refresh();
        $this->assertEquals($game->id, $achievement->game_id);
        $this->assertEquals('Title1', $achievement->title);
        $this->assertEquals('Description1', $achievement->description);
        $this->assertEquals('0xH0000=1', $achievement->trigger_definition);
        $this->assertEquals(5, $achievement->points);
        $this->assertTrue($achievement->is_promoted);
        $this->assertNull($achievement->type);
        $this->assertEquals($this->user->id, $achievement->user_id);
        $this->assertEquals('001234', $achievement->image_name);
        $this->assertNotNull($achievement->modified_at);

        $game->refresh();
        $this->assertEquals(1, $game->achievements_published);
        $this->assertEquals(0, $game->achievements_unpublished);
        $this->assertEquals(5, $game->points_total);

        // trigger should be versioned on promotion
        $this->assertNotNull($achievement->trigger);
        $this->assertEquals('0xH0000=1', $achievement->trigger->conditions);
        $this->assertEquals(1, $achievement->trigger->version);
        $this->assertEquals($triggerVersion, $achievement->trigger->id);

        // core achievement set should also be updated
        $coreSet = $game->gameAchievementSets()->core()->first()->achievementSet;
        $this->assertEquals(1, $coreSet->achievements()->count());
        $this->assertEquals($achievement->id, $coreSet->achievements()->first()->id);
        $this->assertEquals(1, $coreSet->achievements_published);
        $this->assertEquals(0, $coreSet->achievements_unpublished);
        $this->assertEquals(5, $coreSet->points_total);
    });

    test('can update promoted own', function () {
        $this->user->assignRole(Role::DEVELOPER);
        $game = UploadAchievementTestHelpers::createGame();
        $achievement = UploadAchievementTestHelpers::createPromotedAchievement($game, $this->user);
        $triggerVersion = $achievement->trigger_id;
        // NOTE: developer does not need active claim to update existing

        $this->get($this->apiUrl('uploadachievement', [
            'a' => $achievement->id,
            'g' => $game->id,
            'n' => 'Title2',
            'd' => 'Description2',
            'z' => 10,
            'm' => '0xH0000=2',
            'f' => 3,
            'b' => '002345',
            'x' => 'progression',
        ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement->id,
                'Error' => '',
            ]);

        $achievement->refresh();

        $this->assertEquals($game->id, $achievement->game_id);
        $this->assertEquals('Title2', $achievement->title);
        $this->assertEquals('Description2', $achievement->description);
        $this->assertEquals('0xH0000=2', $achievement->trigger_definition);
        $this->assertEquals(10, $achievement->points);
        $this->assertTrue($achievement->is_promoted);
        $this->assertEquals(AchievementType::Progression, $achievement->type);
        $this->assertEquals($this->user->id, $achievement->user_id);
        $this->assertEquals('002345', $achievement->image_name);
        $this->assertNotNull($achievement->modified_at);
        $this->assertNotEquals($triggerVersion, $achievement->trigger_id); // changes after publishing are tracked

        $game->refresh();
        $this->assertEquals(1, $game->achievements_published);
        $this->assertEquals(0, $game->achievements_unpublished);
        $this->assertEquals(10, $game->points_total);

        // new trigger version should be registered
        $this->assertNotNull($achievement->trigger);
        $this->assertEquals('0xH0000=2', $achievement->trigger->conditions);
        $this->assertEquals(2, $achievement->trigger->version);
        $this->assertEquals($achievement->trigger_id, $achievement->trigger->id);
    });

    test('can demote own', function () {
        $this->user->assignRole(Role::DEVELOPER);
        $game = UploadAchievementTestHelpers::createGame();
        $achievement = UploadAchievementTestHelpers::createPromotedAchievement($game, $this->user);
        $achievement->points = 5;
        $achievement->save();

        $player = User::factory()->create();
        $this->addHardcoreUnlock($player, $achievement);

        $this->user->refresh();
        $this->assertEquals(1, $this->user->yield_unlocks);
        $this->assertEquals(5, $this->user->yield_points);

        // NOTE: developer does not need active claim to demote

        $this->get($this->apiUrl('uploadachievement', [
            'a' => $achievement->id,
            'g' => $game->id,
            'n' => 'Title2',
            'd' => 'Description2',
            'z' => 10,
            'm' => '0xH0000=2',
            'f' => 5,
            'b' => '002345',
            'x' => 'progression',
        ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement->id,
                'Error' => '',
            ]);

        $achievement->refresh();
        $this->assertEquals(10, $achievement->points);
        $this->assertFalse($achievement->is_promoted);

        $game->refresh();
        $this->assertEquals(0, $game->achievements_published);
        $this->assertEquals(1, $game->achievements_unpublished);
        $this->assertEquals(0, $game->points_total);

        $this->user->refresh();
        $this->assertEquals(0, $this->user->yield_unlocks);
        $this->assertEquals(0, $this->user->yield_points);
    });

    test('can repromote own', function () {
        $this->user->assignRole(Role::DEVELOPER);
        $game = UploadAchievementTestHelpers::createGame();
        $achievement = UploadAchievementTestHelpers::createUnpromotedAchievement($game, $this->user);
        $achievement->points = 5;
        $achievement->author_yield_unlocks = 1; // addHardcoreUnlock won't set this for unpromoted achievement
        $achievement->save();

        $player = User::factory()->create();
        $this->addHardcoreUnlock($player, $achievement);

        $this->user->refresh();
        $this->assertEquals(0, $this->user->yield_unlocks); // unpromoted doesn't affect yield
        $this->assertEquals(0, $this->user->yield_points);

        // NOTE: developer does not need active claim to promote

        $this->get($this->apiUrl('uploadachievement', [
            'a' => $achievement->id,
            'g' => $game->id,
            'n' => 'Title2',
            'd' => 'Description2',
            'z' => 10,
            'm' => '0xH0000=2',
            'f' => 3,
            'b' => '002345',
            'x' => 'progression',
        ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement->id,
                'Error' => '',
            ]);

        $achievement->refresh();
        $this->assertEquals(10, $achievement->points);
        $this->assertTrue($achievement->is_promoted);

        $game->refresh();
        $this->assertEquals(1, $game->achievements_published);
        $this->assertEquals(0, $game->achievements_unpublished);
        $this->assertEquals(10, $game->points_total);

        $this->user->refresh();
        $this->assertEquals(1, $this->user->yield_unlocks);
        $this->assertEquals(10, $this->user->yield_points);
    });

    test('can update unpromoted someone elses', function () {
        $this->user->assignRole(Role::DEVELOPER);
        $game = UploadAchievementTestHelpers::createGame();
        $otherUser = User::factory()->create();
        $achievement = UploadAchievementTestHelpers::createUnpromotedAchievement($game, $otherUser);
        $triggerVersion = $achievement->trigger_id;
        // NOTE: developer does not need active claim to update existing

        $this->get($this->apiUrl('uploadachievement', [
            'a' => $achievement->id,
            'g' => $game->id,
            'n' => 'Title2',
            'd' => 'Description2',
            'z' => 10,
            'm' => '0xH0000=2',
            'f' => 5,
            'b' => '002345',
            'x' => 'progression', // hard-code in case enum changes
        ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement->id,
                'Error' => '',
            ]);

        $achievement->refresh();

        $this->assertEquals($game->id, $achievement->game_id);
        $this->assertEquals('Title2', $achievement->title);
        $this->assertEquals('Description2', $achievement->description);
        $this->assertEquals('0xH0000=2', $achievement->trigger_definition);
        $this->assertEquals(10, $achievement->points);
        $this->assertFalse($achievement->is_promoted);
        $this->assertEquals(AchievementType::Progression, $achievement->type);
        $this->assertEquals($otherUser->id, $achievement->user_id);
        $this->assertEquals('002345', $achievement->image_name);
        $this->assertNotNull($achievement->modified_at);
        $this->assertEquals($triggerVersion, $achievement->trigger_id); // changes before promoting aren't tracked

        $game->refresh();
        $this->assertEquals(0, $game->achievements_published);
        $this->assertEquals(1, $game->achievements_unpublished);
        $this->assertEquals(0, $game->points_total);
    });

    test('can update promoted someone elses', function () {
        $this->user->assignRole(Role::DEVELOPER);
        $game = UploadAchievementTestHelpers::createGame();
        $otherUser = User::factory()->create();
        $achievement = UploadAchievementTestHelpers::createPromotedAchievement($game, $otherUser);
        $triggerVersion = $achievement->trigger_id;
        // NOTE: developer does not need active claim to update existing

        $this->get($this->apiUrl('uploadachievement', [
            'a' => $achievement->id,
            'g' => $game->id,
            'n' => 'Title2',
            'd' => 'Description2',
            'z' => 10,
            'm' => '0xH0000=2',
            'f' => 3,
            'b' => '002345',
            'x' => 'progression',
        ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement->id,
                'Error' => '',
            ]);

        $achievement->refresh();

        $this->assertEquals($game->id, $achievement->game_id);
        $this->assertEquals('Title2', $achievement->title);
        $this->assertEquals('Description2', $achievement->description);
        $this->assertEquals('0xH0000=2', $achievement->trigger_definition);
        $this->assertEquals(10, $achievement->points);
        $this->assertTrue($achievement->is_promoted);
        $this->assertEquals(AchievementType::Progression, $achievement->type);
        $this->assertEquals($otherUser->id, $achievement->user_id);
        $this->assertEquals('002345', $achievement->image_name);
        $this->assertNotNull($achievement->modified_at);
        $this->assertNotEquals($triggerVersion, $achievement->trigger_id); // changes after publishing are tracked

        $game->refresh();
        $this->assertEquals(1, $game->achievements_published);
        $this->assertEquals(0, $game->achievements_unpublished);
        $this->assertEquals(10, $game->points_total);
    });

    test('can demote someone elses', function () {
        $this->user->assignRole(Role::DEVELOPER);
        $game = UploadAchievementTestHelpers::createGame();
        $otherUser = User::factory()->create();
        $achievement = UploadAchievementTestHelpers::createPromotedAchievement($game, $otherUser);
        $achievement->points = 5;
        $achievement->save();

        $player = User::factory()->create();
        $this->addHardcoreUnlock($player, $achievement);

        $otherUser->refresh();
        $this->assertEquals(1, $otherUser->yield_unlocks);
        $this->assertEquals(5, $otherUser->yield_points);

        // NOTE: developer does not need active claim to demote

        $this->get($this->apiUrl('uploadachievement', [
            'a' => $achievement->id,
            'g' => $game->id,
            'n' => 'Title2',
            'd' => 'Description2',
            'z' => 10,
            'm' => '0xH0000=2',
            'f' => 5,
            'b' => '002345',
            'x' => 'progression',
        ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement->id,
                'Error' => '',
            ]);

        $achievement->refresh();
        $this->assertEquals(10, $achievement->points);
        $this->assertFalse($achievement->is_promoted);

        $game->refresh();
        $this->assertEquals(0, $game->achievements_published);
        $this->assertEquals(1, $game->achievements_unpublished);
        $this->assertEquals(0, $game->points_total);

        $otherUser->refresh();
        $this->assertEquals(0, $otherUser->yield_unlocks);
        $this->assertEquals(0, $otherUser->yield_points);
    });

    test('can repromote someone elses', function () {
        $this->user->assignRole(Role::DEVELOPER);
        $game = UploadAchievementTestHelpers::createGame();
        $otherUser = User::factory()->create();
        $achievement = UploadAchievementTestHelpers::createUnpromotedAchievement($game, $otherUser);
        $achievement->points = 5;
        $achievement->author_yield_unlocks = 1; // addHardcoreUnlock won't set this for unpromoted achievement
        $achievement->save();

        $player = User::factory()->create();
        $this->addHardcoreUnlock($player, $achievement);

        $otherUser->refresh();
        $this->assertEquals(0, $otherUser->yield_unlocks); // unpromoted doesn't affect yield
        $this->assertEquals(0, $otherUser->yield_points);

        // NOTE: developer does not need active claim to promote

        $this->get($this->apiUrl('uploadachievement', [
            'a' => $achievement->id,
            'g' => $game->id,
            'n' => 'Title2',
            'd' => 'Description2',
            'z' => 10,
            'm' => '0xH0000=2',
            'f' => 3,
            'b' => '002345',
            'x' => 'progression',
        ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement->id,
                'Error' => '',
            ]);

        $achievement->refresh();
        $this->assertEquals(10, $achievement->points);
        $this->assertTrue($achievement->is_promoted);

        $game->refresh();
        $this->assertEquals(1, $game->achievements_published);
        $this->assertEquals(0, $game->achievements_unpublished);
        $this->assertEquals(10, $game->points_total);

        $otherUser->refresh();
        $this->assertEquals(1, $otherUser->yield_unlocks);
        $this->assertEquals(10, $otherUser->yield_points);
    });

    test('can create new achievement via set id', function () {
        $this->user->assignRole(Role::DEVELOPER);
        $game = UploadAchievementTestHelpers::createGame();
        UploadAchievementTestHelpers::addClaim($game, $this->user);

        $achievementSet = GameAchievementSet::where('game_id', $game->id)->first();

        $this->get($this->apiUrl('uploadachievement', [
            's' => $achievementSet->id,
            'n' => 'Title1',
            'd' => 'Description1',
            'z' => 5,
            'm' => '0xH0000=1',
            'f' => 5, // Unpromoted - hardcode for test to prevent false success if enum changes
            'b' => '001234',
        ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => 1,
                'Error' => '',
            ]);

        $achievement = Achievement::findOrFail(1);
        $this->assertEquals($game->id, $achievement->game_id);
        $this->assertEquals('Title1', $achievement->title);
        $this->assertEquals('Description1', $achievement->description);
        $this->assertEquals('0xH0000=1', $achievement->trigger_definition);
        $this->assertEquals(5, $achievement->points);
        $this->assertFalse($achievement->is_promoted);
        $this->assertNull($achievement->type);
        $this->assertEquals($this->user->id, $achievement->user_id);
        $this->assertEquals('001234', $achievement->image_name);
        $this->assertNotNull($achievement->modified_at);

        $game->refresh();
        $this->assertEquals(0, $game->achievements_published);
        $this->assertEquals(1, $game->achievements_unpublished);
        $this->assertEquals(0, $game->points_total); // unpromoted achievements don't contribute to points_total
    });
});

describe('junior developer', function () {
    test('can create new achievement', function () {
        $this->user->assignRole(Role::DEVELOPER_JUNIOR);
        $game = UploadAchievementTestHelpers::createGame();
        UploadAchievementTestHelpers::addClaim($game, $this->user);

        $this->get($this->apiUrl('uploadachievement', [
            'g' => $game->id,
            'n' => 'Title1',
            'd' => 'Description1',
            'z' => 5,
            'm' => '0xH0000=1',
            'f' => 5, // Unpromoted - hardcode for test to prevent false success if enum changes
            'b' => '001234',
        ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => 1,
                'Error' => '',
            ]);

        $achievement = Achievement::findOrFail(1);
        $this->assertEquals($game->id, $achievement->game_id);
        $this->assertEquals('Title1', $achievement->title);
        $this->assertEquals('Description1', $achievement->description);
        $this->assertEquals('0xH0000=1', $achievement->trigger_definition);
        $this->assertEquals(5, $achievement->points);
        $this->assertFalse($achievement->is_promoted);
        $this->assertNull($achievement->type);
        $this->assertEquals($this->user->id, $achievement->user_id);
        $this->assertEquals('001234', $achievement->image_name);
        $this->assertNotNull($achievement->modified_at);

        $game->refresh();
        $this->assertEquals(0, $game->achievements_published);
        $this->assertEquals(1, $game->achievements_unpublished);
        $this->assertEquals(0, $game->points_total); // unpromoted achievements don't contribute to points_total
    });

    test('cannot create new achievement without claim', function () {
        $this->user->assignRole(Role::DEVELOPER_JUNIOR);
        $game = UploadAchievementTestHelpers::createGame();

        $this->get($this->apiUrl('uploadachievement', [
            'g' => $game->id,
            'n' => 'Title1',
            'd' => 'Description1',
            'z' => 5,
            'm' => '0xH0000=1',
            'f' => 5, // Unpromoted - hardcode for test to prevent false success if enum changes
            'b' => '001234',
        ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => false,
                'AchievementID' => 0,
                'Error' => 'You must have an active claim on this game to perform this action.',
            ]);

        $this->assertEquals(0, Achievement::count());

        $game->refresh();
        $this->assertEquals(0, $game->achievements_published);
        $this->assertEquals(0, $game->achievements_unpublished);
    });

    test('cannot promote own', function () {
        $this->user->assignRole(Role::DEVELOPER_JUNIOR);
        $game = UploadAchievementTestHelpers::createGame();
        $achievement = UploadAchievementTestHelpers::createUnpromotedAchievement($game, $this->user);

        // even with a claim, junior developer is not allowed to promote
        UploadAchievementTestHelpers::addClaim($game, $this->user);

        $this->get($this->apiUrl('uploadachievement', [
            'a' => $achievement->id,
            'g' => $game->id,
            'n' => 'Title1',
            'd' => 'Description1',
            'z' => 5,
            'm' => '0xH0000=1',
            'f' => 3, // Publish - hardcode for test to prevent false success if enum changes
            'b' => '001234',
        ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => false,
                'AchievementID' => $achievement->id,
                'Error' => 'You must be a developer to perform this action! Please drop a message in the forums to apply.',
            ]);

        $achievement->refresh();
        $this->assertFalse($achievement->is_promoted);

        $game->refresh();
        $this->assertEquals(0, $game->achievements_published);
        $this->assertEquals(1, $game->achievements_unpublished);
        $this->assertEquals(0, $game->points_total);
    });

    test('can update own unpromoted', function () {
        $this->user->assignRole(Role::DEVELOPER_JUNIOR);
        $game = UploadAchievementTestHelpers::createGame();
        $achievement = UploadAchievementTestHelpers::createUnpromotedAchievement($game, $this->user);
        $triggerVersion = $achievement->trigger_id;
        UploadAchievementTestHelpers::addClaim($game, $this->user);

        $this->get($this->apiUrl('uploadachievement', [
            'a' => $achievement->id,
            'g' => $game->id,
            'n' => 'Title2',
            'd' => 'Description2',
            'z' => 10,
            'm' => '0xH0000=2',
            'f' => 5,
            'b' => '002345',
            'x' => 'progression', // hard-code in case enum changes
        ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement->id,
                'Error' => '',
            ]);

        $achievement->refresh();

        $this->assertEquals($game->id, $achievement->game_id);
        $this->assertEquals('Title2', $achievement->title);
        $this->assertEquals('Description2', $achievement->description);
        $this->assertEquals('0xH0000=2', $achievement->trigger_definition);
        $this->assertEquals(10, $achievement->points);
        $this->assertFalse($achievement->is_promoted);
        $this->assertEquals(AchievementType::Progression, $achievement->type);
        $this->assertEquals($this->user->id, $achievement->user_id);
        $this->assertEquals('002345', $achievement->image_name);
        $this->assertNotNull($achievement->modified_at);
        $this->assertEquals($triggerVersion, $achievement->trigger_id); // changes before promoting aren't tracked

        $game->refresh();
        $this->assertEquals(0, $game->achievements_published);
        $this->assertEquals(1, $game->achievements_unpublished);
        $this->assertEquals(0, $game->points_total);
    });

    test('cannot update own promoted logic', function () {
        $this->user->assignRole(Role::DEVELOPER_JUNIOR);
        $game = UploadAchievementTestHelpers::createGame();
        $achievement = UploadAchievementTestHelpers::createPromotedAchievement($game, $this->user);
        $achievement->title = 'Title1';
        $achievement->description = 'Description1';
        $achievement->trigger_definition = '0xH0000=1';
        $achievement->points = 5;
        $achievement->image_name = '001234';
        $achievement->save();

        // even with a claim, junior developer is not allowed to edit promoted logic
        UploadAchievementTestHelpers::addClaim($game, $this->user);

        $this->get($this->apiUrl('uploadachievement', [
            'a' => $achievement->id,
            'g' => $game->id,
            'n' => 'Title1',
            'd' => 'Description1',
            'z' => 5,
            'm' => '0xH0000=2',
            'f' => 3,
            'b' => '001234',
        ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => false,
                'AchievementID' => $achievement->id,
                'Error' => 'You must be a developer to perform this action! Please drop a message in the forums to apply.',
            ]);

        $achievement->refresh();
        $this->assertEquals($game->id, $achievement->game_id);
        $this->assertEquals('Title1', $achievement->title);
        $this->assertEquals('Description1', $achievement->description);
        $this->assertEquals('0xH0000=1', $achievement->trigger_definition);
        $this->assertEquals(5, $achievement->points);
        $this->assertTrue($achievement->is_promoted);
        $this->assertEquals($this->user->id, $achievement->user_id);
        $this->assertEquals('001234', $achievement->image_name);
    });

    test('can update own promoted non-logic', function () {
        $this->user->assignRole(Role::DEVELOPER_JUNIOR);
        $game = UploadAchievementTestHelpers::createGame();
        $achievement = UploadAchievementTestHelpers::createPromotedAchievement($game, $this->user);
        $achievement->title = 'Title1';
        $achievement->description = 'Description1';
        $achievement->trigger_definition = '0xH0000=1';
        $achievement->points = 5;
        $achievement->image_name = '001234';
        $achievement->save();

        // even with a claim, junior developer is not allowed to edit promoted
        UploadAchievementTestHelpers::addClaim($game, $this->user);

        $this->get($this->apiUrl('uploadachievement', [
            'a' => $achievement->id,
            'g' => $game->id,
            'n' => 'Title2',
            'd' => 'Description2',
            'z' => 10,
            'm' => '0xH0000=1',
            'f' => 3,
            'b' => '002345',
            'x' => 'progression',
        ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement->id,
                'Error' => '',
            ]);

        $achievement->refresh();
        $this->assertEquals($game->id, $achievement->game_id);
        $this->assertEquals('Title2', $achievement->title);
        $this->assertEquals('Description2', $achievement->description);
        $this->assertEquals('0xH0000=1', $achievement->trigger_definition);
        $this->assertEquals(10, $achievement->points);
        $this->assertTrue($achievement->is_promoted);
        $this->assertEquals($this->user->id, $achievement->user_id);
        $this->assertEquals('002345', $achievement->image_name);
    });

    test('cannot demote own', function () {
        $this->user->assignRole(Role::DEVELOPER_JUNIOR);
        $game = UploadAchievementTestHelpers::createGame();
        $achievement = UploadAchievementTestHelpers::createPromotedAchievement($game, $this->user);
        $achievement->points = 5;
        $achievement->save();

        $player = User::factory()->create();
        $this->addHardcoreUnlock($player, $achievement);

        $this->user->refresh();
        $this->assertEquals(1, $this->user->yield_unlocks);
        $this->assertEquals(5, $this->user->yield_points);

        // even with a claim, junior developer is not allowed to demote
        UploadAchievementTestHelpers::addClaim($game, $this->user);

        $this->get($this->apiUrl('uploadachievement', [
            'a' => $achievement->id,
            'g' => $game->id,
            'n' => 'Title2',
            'd' => 'Description2',
            'z' => 10,
            'm' => '0xH0000=2',
            'f' => 5,
            'b' => '002345',
            'x' => 'progression',
        ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => false,
                'AchievementID' => $achievement->id,
                'Error' => 'You must be a developer to perform this action! Please drop a message in the forums to apply.',
            ]);

        $achievement->refresh();
        $this->assertTrue($achievement->is_promoted);

        $game->refresh();
        $this->assertEquals(1, $game->achievements_published);
        $this->assertEquals(0, $game->achievements_unpublished);
    });

    test('cannot repromote own', function () {
        $this->user->assignRole(Role::DEVELOPER_JUNIOR);
        $game = UploadAchievementTestHelpers::createGame();
        $achievement = UploadAchievementTestHelpers::createUnpromotedAchievement($game, $this->user);

        $player = User::factory()->create();
        $this->addHardcoreUnlock($player, $achievement);

        // even with a claim, junior developer is not allowed to promote
        UploadAchievementTestHelpers::addClaim($game, $this->user);

        $this->get($this->apiUrl('uploadachievement', [
            'a' => $achievement->id,
            'g' => $game->id,
            'n' => 'Title2',
            'd' => 'Description2',
            'z' => 10,
            'm' => '0xH0000=2',
            'f' => 3,
            'b' => '002345',
            'x' => 'progression',
        ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => false,
                'AchievementID' => $achievement->id,
                'Error' => 'You must be a developer to perform this action! Please drop a message in the forums to apply.',
            ]);

        $achievement->refresh();
        $this->assertFalse($achievement->is_promoted);

        $game->refresh();
        $this->assertEquals(0, $game->achievements_published);
        $this->assertEquals(1, $game->achievements_unpublished);
    });

    test('cannot promote someone elses', function () {
        $this->user->assignRole(Role::DEVELOPER_JUNIOR);
        $game = UploadAchievementTestHelpers::createGame();
        $otherUser = User::factory()->create();
        $achievement = UploadAchievementTestHelpers::createUnpromotedAchievement($game, $otherUser);

        // even with a claim, junior developer is not allowed to promote
        UploadAchievementTestHelpers::addClaim($game, $this->user);

        $this->get($this->apiUrl('uploadachievement', [
            'a' => $achievement->id,
            'g' => $game->id,
            'n' => 'Title1',
            'd' => 'Description1',
            'z' => 5,
            'm' => '0xH0000=1',
            'f' => 3, // Publish - hardcode for test to prevent false success if enum changes
            'b' => '001234',
        ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => false,
                'AchievementID' => $achievement->id,
                'Error' => 'You must be a developer to perform this action! Please drop a message in the forums to apply.',
            ]);

        $achievement->refresh();
        $this->assertFalse($achievement->is_promoted);

        $game->refresh();
        $this->assertEquals(0, $game->achievements_published);
        $this->assertEquals(1, $game->achievements_unpublished);
        $this->assertEquals(0, $game->points_total);
    });

    test('cannot update someone elses unpromoted', function () {
        $this->user->assignRole(Role::DEVELOPER_JUNIOR);
        $game = UploadAchievementTestHelpers::createGame();
        $otherUser = User::factory()->create();
        $achievement = UploadAchievementTestHelpers::createUnpromotedAchievement($game, $otherUser);
        $achievement->title = 'Title1';
        $achievement->description = 'Description1';
        $achievement->trigger_definition = '0xH0000=1';
        $achievement->points = 5;
        $achievement->image_name = '001234';
        $achievement->save();

        // even with a claim, junior developer can only modify their own achievements
        UploadAchievementTestHelpers::addClaim($game, $this->user);

        $this->get($this->apiUrl('uploadachievement', [
            'a' => $achievement->id,
            'g' => $game->id,
            'n' => 'Title2',
            'd' => 'Description2',
            'z' => 10,
            'm' => '0xH0000=2',
            'f' => 5,
            'b' => '002345',
            'x' => 'progression', // hard-code in case enum changes
        ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => false,
                'AchievementID' => $achievement->id,
                'Error' => 'You must be a developer to perform this action! Please drop a message in the forums to apply.',
            ]);

        $achievement->refresh();

        $this->assertEquals($game->id, $achievement->game_id);
        $this->assertEquals('Title1', $achievement->title);
        $this->assertEquals('Description1', $achievement->description);
        $this->assertEquals('0xH0000=1', $achievement->trigger_definition);
        $this->assertEquals(5, $achievement->points);
        $this->assertFalse($achievement->is_promoted);
        $this->assertEquals($otherUser->id, $achievement->user_id);
        $this->assertEquals('001234', $achievement->image_name);

        $game->refresh();
        $this->assertEquals(0, $game->achievements_published);
        $this->assertEquals(1, $game->achievements_unpublished);
        $this->assertEquals(0, $game->points_total);
    });

    test('cannot update someone elses promoted', function () {
        $this->user->assignRole(Role::DEVELOPER_JUNIOR);
        $game = UploadAchievementTestHelpers::createGame();
        $otherUser = User::factory()->create();
        $achievement = UploadAchievementTestHelpers::createPromotedAchievement($game, $otherUser);
        $achievement->title = 'Title1';
        $achievement->description = 'Description1';
        $achievement->trigger_definition = '0xH0000=1';
        $achievement->points = 5;
        $achievement->image_name = '001234';
        $achievement->save();
        $triggerVersion = $achievement->trigger_id;

        // even with a claim, junior developer is not allowed to edit promoted
        UploadAchievementTestHelpers::addClaim($game, $this->user);

        $this->get($this->apiUrl('uploadachievement', [
            'a' => $achievement->id,
            'g' => $game->id,
            'n' => 'Title2',
            'd' => 'Description2',
            'z' => 10,
            'm' => '0xH0000=2',
            'f' => 3,
            'b' => '002345',
            'x' => 'progression',
        ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => false,
                'AchievementID' => $achievement->id,
                'Error' => 'You must be a developer to perform this action! Please drop a message in the forums to apply.',
            ]);

        $achievement->refresh();
        $this->assertEquals($game->id, $achievement->game_id);
        $this->assertEquals('Title1', $achievement->title);
        $this->assertEquals('Description1', $achievement->description);
        $this->assertEquals('0xH0000=1', $achievement->trigger_definition);
        $this->assertEquals(5, $achievement->points);
        $this->assertTrue($achievement->is_promoted);
        $this->assertEquals($otherUser->id, $achievement->user_id);
        $this->assertEquals('001234', $achievement->image_name);
        $this->assertEquals($triggerVersion, $achievement->trigger_id);
    });

    test('cannot demote someone elses', function () {
        $this->user->assignRole(Role::DEVELOPER_JUNIOR);
        $game = UploadAchievementTestHelpers::createGame();
        $otherUser = User::factory()->create();
        $achievement = UploadAchievementTestHelpers::createPromotedAchievement($game, $otherUser);

        // even with a claim, junior developer is not allowed to demote
        UploadAchievementTestHelpers::addClaim($game, $this->user);

        $this->get($this->apiUrl('uploadachievement', [
            'a' => $achievement->id,
            'g' => $game->id,
            'n' => 'Title2',
            'd' => 'Description2',
            'z' => 10,
            'm' => '0xH0000=2',
            'f' => 5,
            'b' => '002345',
            'x' => 'progression',
        ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => false,
                'AchievementID' => $achievement->id,
                'Error' => 'You must be a developer to perform this action! Please drop a message in the forums to apply.',
            ]);

        $achievement->refresh();
        $this->assertTrue($achievement->is_promoted);

        $game->refresh();
        $this->assertEquals(1, $game->achievements_published);
        $this->assertEquals(0, $game->achievements_unpublished);
    });

    test('cannot repromote someone elses', function () {
        $this->user->assignRole(Role::DEVELOPER_JUNIOR);
        $game = UploadAchievementTestHelpers::createGame();
        $otherUser = User::factory()->create();
        $achievement = UploadAchievementTestHelpers::createUnpromotedAchievement($game, $otherUser);

        // even with a claim, junior developer is not allowed to promote
        UploadAchievementTestHelpers::addClaim($game, $this->user);

        $this->get($this->apiUrl('uploadachievement', [
            'a' => $achievement->id,
            'g' => $game->id,
            'n' => 'Title2',
            'd' => 'Description2',
            'z' => 10,
            'm' => '0xH0000=2',
            'f' => 3,
            'b' => '002345',
            'x' => 'progression',
        ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => false,
                'AchievementID' => $achievement->id,
                'Error' => 'You must be a developer to perform this action! Please drop a message in the forums to apply.',
            ]);

        $achievement->refresh();
        $this->assertFalse($achievement->is_promoted);

        $game->refresh();
        $this->assertEquals(0, $game->achievements_published);
        $this->assertEquals(1, $game->achievements_unpublished);
    });
});

describe('non-developer', function () {
    test('cannot create new achievement', function () {
        $game = UploadAchievementTestHelpers::createGame();

        $this->get($this->apiUrl('uploadachievement', [
            'g' => $game->id,
            'n' => 'Title1',
            'd' => 'Description1',
            'z' => 5,
            'm' => '0xH0000=1',
            'f' => 5, // Unpromoted - hardcode for test to prevent false success if enum changes
            'b' => '001234',
        ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => false,
                'AchievementID' => 0,
                'Error' => 'You must be a developer to perform this action! Please drop a message in the forums to apply.',
            ]);

        $this->assertEquals(0, Achievement::count());

        $game->refresh();
        $this->assertEquals(0, $game->achievements_published);
        $this->assertEquals(0, $game->achievements_unpublished);
    });

    test('cannot promote', function () {
        $game = UploadAchievementTestHelpers::createGame();
        $achievement = UploadAchievementTestHelpers::createUnpromotedAchievement($game, $this->user);

        $this->get($this->apiUrl('uploadachievement', [
            'a' => $achievement->id,
            'g' => $game->id,
            'n' => 'Title1',
            'd' => 'Description1',
            'z' => 5,
            'm' => '0xH0000=1',
            'f' => 3, // Publish - hardcode for test to prevent false success if enum changes
            'b' => '001234',
        ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => false,
                'AchievementID' => $achievement->id,
                'Error' => 'You must be a developer to perform this action! Please drop a message in the forums to apply.',
            ]);

        $this->assertEquals(1, Achievement::count());

        $game->refresh();
        $this->assertEquals(0, $game->achievements_published);
        $this->assertEquals(1, $game->achievements_unpublished);
    });

    test('cannot update unpromoted', function () {
        $game = UploadAchievementTestHelpers::createGame();
        $achievement = UploadAchievementTestHelpers::createUnpromotedAchievement($game, $this->user);
        $triggerVersion = $achievement->trigger_id;

        $this->get($this->apiUrl('uploadachievement', [
            'a' => $achievement->id,
            'g' => $game->id,
            'n' => 'Title2',
            'd' => 'Description2',
            'z' => 10,
            'm' => '0xH0000=2',
            'f' => 5,
            'b' => '002345',
            'x' => 'progression', // hard-code in case enum changes
        ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => false,
                'AchievementID' => $achievement->id,
                'Error' => 'You must be a developer to perform this action! Please drop a message in the forums to apply.',
            ]);

        $this->assertEquals(1, Achievement::count());

        $game->refresh();
        $this->assertEquals(0, $game->achievements_published);
        $this->assertEquals(1, $game->achievements_unpublished);
    });

    test('cannot update promoted', function () {
        $game = UploadAchievementTestHelpers::createGame();
        $achievement = UploadAchievementTestHelpers::createPromotedAchievement($game, $this->user);
        $triggerVersion = $achievement->trigger_id;

        $this->get($this->apiUrl('uploadachievement', [
            'a' => $achievement->id,
            'g' => $game->id,
            'n' => 'Title2',
            'd' => 'Description2',
            'z' => 10,
            'm' => '0xH0000=2',
            'f' => 3,
            'b' => '002345',
            'x' => 'progression',
        ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => false,
                'AchievementID' => $achievement->id,
                'Error' => 'You must be a developer to perform this action! Please drop a message in the forums to apply.',
            ]);

        $this->assertEquals(1, Achievement::count());

        $game->refresh();
        $this->assertEquals(1, $game->achievements_published);
        $this->assertEquals(0, $game->achievements_unpublished);
    });

    test('cannot demote', function () {
        $game = UploadAchievementTestHelpers::createGame();
        $achievement = UploadAchievementTestHelpers::createPromotedAchievement($game, $this->user);
        $achievement->points = 5;
        $achievement->save();

        $player = User::factory()->create();
        $this->addHardcoreUnlock($player, $achievement);

        $this->user->refresh();
        $this->assertEquals(1, $this->user->yield_unlocks);
        $this->assertEquals(5, $this->user->yield_points);

        $this->get($this->apiUrl('uploadachievement', [
            'a' => $achievement->id,
            'g' => $game->id,
            'n' => 'Title2',
            'd' => 'Description2',
            'z' => 10,
            'm' => '0xH0000=2',
            'f' => 5,
            'b' => '002345',
            'x' => 'progression',
        ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => false,
                'AchievementID' => $achievement->id,
                'Error' => 'You must be a developer to perform this action! Please drop a message in the forums to apply.',
            ]);

        $this->assertEquals(1, Achievement::count());

        $game->refresh();
        $this->assertEquals(1, $game->achievements_published);
        $this->assertEquals(0, $game->achievements_unpublished);
    });
});

describe('subset', function () {
    test('can create new achievement', function () {
        $this->user->assignRole(Role::DEVELOPER);
        $game = UploadAchievementTestHelpers::createGame();
        $game->title .= ' [Subset - Testing]';
        $game->save();
        UploadAchievementTestHelpers::addClaim($game, $this->user);

        $this->get($this->apiUrl('uploadachievement', [
            'g' => $game->id,
            'n' => 'Title1',
            'd' => 'Description1',
            'z' => 5,
            'm' => '0xH0000=1',
            'f' => 5, // Unpromoted - hardcode for test to prevent false success if enum changes
            'b' => '001234',
        ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => 1,
                'Error' => '',
            ]);

        $achievement = Achievement::findOrFail(1);
        $this->assertEquals($game->id, $achievement->game_id);
        $this->assertEquals('Title1', $achievement->title);
        $this->assertEquals('Description1', $achievement->description);
        $this->assertEquals('0xH0000=1', $achievement->trigger_definition);
        $this->assertEquals(5, $achievement->points);
        $this->assertFalse($achievement->is_promoted);
        $this->assertNull($achievement->type);
        $this->assertEquals($this->user->id, $achievement->user_id);
        $this->assertEquals('001234', $achievement->image_name);
        $this->assertNotNull($achievement->modified_at);

        $game->refresh();
        $this->assertEquals(0, $game->achievements_published);
        $this->assertEquals(1, $game->achievements_unpublished);
        $this->assertEquals(0, $game->points_total); // unpromoted achievements don't contribute to points_total
    });

    test('cannot create new achievement without claim', function () {
        $this->user->assignRole(Role::DEVELOPER);
        $game = UploadAchievementTestHelpers::createGame();
        $game->title .= ' [Subset - Testing]';
        $game->save();

        $this->get($this->apiUrl('uploadachievement', [
            'g' => $game->id,
            'n' => 'Title1',
            'd' => 'Description1',
            'z' => 5,
            'm' => '0xH0000=1',
            'f' => 5, // Unpromoted - hardcode for test to prevent false success if enum changes
            'b' => '001234',
        ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => false,
                'AchievementID' => 0,
                'Error' => 'You must have an active claim on this game to perform this action.',
            ]);

        $this->assertEquals(0, Achievement::count());

        $game->refresh();
        $this->assertEquals(0, $game->achievements_published);
        $this->assertEquals(0, $game->achievements_unpublished);
    });

    test('cannot set progression', function () {
        $this->user->assignRole(Role::DEVELOPER);
        $game = UploadAchievementTestHelpers::createGame();
        $game->title .= ' [Subset - Testing]';
        $game->save();
        $achievement = UploadAchievementTestHelpers::createUnpromotedAchievement($game, $this->user);

        // NOTE: developer does not need active claim to update existing

        $this->get($this->apiUrl('uploadachievement', [
            'a' => $achievement->id,
            'g' => $game->id,
            'n' => 'Title2',
            'd' => 'Description2',
            'z' => 10,
            'm' => '0xH0000=2',
            'f' => 5,
            'b' => '002345',
            'x' => 'progression', // hard-code in case enum changes
        ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => false,
                'AchievementID' => $achievement->id,
                'Error' => 'Cannot set progression or win condition type on achievement in subset, test kit, or event.',
            ]);

        $achievement->refresh();
        $this->assertNull($achievement->type);
    });

    test('cannot set win condition', function () {
        $this->user->assignRole(Role::DEVELOPER);
        $game = UploadAchievementTestHelpers::createGame();
        $game->title .= ' [Subset - Testing]';
        $game->save();
        $achievement = UploadAchievementTestHelpers::createUnpromotedAchievement($game, $this->user);

        // NOTE: developer does not need active claim to update existing

        $this->get($this->apiUrl('uploadachievement', [
            'a' => $achievement->id,
            'g' => $game->id,
            'n' => 'Title2',
            'd' => 'Description2',
            'z' => 10,
            'm' => '0xH0000=2',
            'f' => 5,
            'b' => '002345',
            'x' => 'win_condition', // hard-code in case enum changes
        ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => false,
                'AchievementID' => $achievement->id,
                'Error' => 'Cannot set progression or win condition type on achievement in subset, test kit, or event.',
            ]);

        $achievement->refresh();
        $this->assertNull($achievement->type);
    });

    test('can set missable', function () {
        $this->user->assignRole(Role::DEVELOPER);
        $game = UploadAchievementTestHelpers::createGame();
        $game->title .= ' [Subset - Testing]';
        $game->save();
        $achievement = UploadAchievementTestHelpers::createUnpromotedAchievement($game, $this->user);

        // NOTE: developer does not need active claim to update existing

        $this->get($this->apiUrl('uploadachievement', [
            'a' => $achievement->id,
            'g' => $game->id,
            'n' => 'Title2',
            'd' => 'Description2',
            'z' => 10,
            'm' => '0xH0000=2',
            'f' => 5,
            'b' => '002345',
            'x' => 'missable', // hard-code in case enum changes
        ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement->id,
                'Error' => '',
            ]);

        $achievement->refresh();
        $this->assertEquals(AchievementType::Missable, $achievement->type);
    });
});

describe('behavior', function () {
    test('changing promoted points affects developer yield', function () {
        $this->user->assignRole(Role::DEVELOPER);
        $game = UploadAchievementTestHelpers::createGame();
        $achievement = UploadAchievementTestHelpers::createPromotedAchievement($game, $this->user);
        $achievement->points = 5;
        $achievement->save();

        $player = User::factory()->create();
        $this->addHardcoreUnlock($player, $achievement);

        $this->user->refresh();
        $this->assertEquals(1, $this->user->yield_unlocks);
        $this->assertEquals(5, $this->user->yield_points);

        $this->get($this->apiUrl('uploadachievement', [
            'a' => $achievement->id,
            'g' => $game->id,
            'n' => 'Title2',
            'd' => 'Description2',
            'z' => 10,
            'm' => '0xH0000=2',
            'f' => 3,
            'b' => '002345',
            'x' => 'progression',
        ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement->id,
                'Error' => '',
            ]);

        $achievement->refresh();
        $this->assertEquals(10, $achievement->points);

        $this->user->refresh();
        $this->assertEquals(1, $this->user->yield_unlocks);
        $this->assertEquals(10, $this->user->yield_points);
    });

    test('can create new achievement for inactive console', function () {
        $this->user->assignRole(Role::DEVELOPER);
        $game = UploadAchievementTestHelpers::createGame();
        $game->system->active = false;
        $game->system->save();
        UploadAchievementTestHelpers::addClaim($game, $this->user);

        $this->get($this->apiUrl('uploadachievement', [
            'g' => $game->id,
            'n' => 'Title1',
            'd' => 'Description1',
            'z' => 5,
            'm' => '0xH0000=1',
            'f' => 5, // Unpromoted - hardcode for test to prevent false success if enum changes
            'b' => '001234',
        ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => 1,
                'Error' => '',
            ]);

        $achievement = Achievement::findOrFail(1);
        $this->assertEquals($game->id, $achievement->game_id);
        $this->assertEquals('Title1', $achievement->title);
        $this->assertEquals('Description1', $achievement->description);
        $this->assertEquals('0xH0000=1', $achievement->trigger_definition);
        $this->assertEquals(5, $achievement->points);
        $this->assertFalse($achievement->is_promoted);
        $this->assertNull($achievement->type);
        $this->assertEquals($this->user->id, $achievement->user_id);
        $this->assertEquals('001234', $achievement->image_name);
        $this->assertNotNull($achievement->modified_at);

        $game->refresh();
        $this->assertEquals(0, $game->achievements_published);
        $this->assertEquals(1, $game->achievements_unpublished);
        $this->assertEquals(0, $game->points_total); // unpromoted achievements don't contribute to points_total
    });

    test('can update achievement for inactive console', function () {
        $this->user->assignRole(Role::DEVELOPER);
        $game = UploadAchievementTestHelpers::createGame();
        $game->system->active = false;
        $game->system->save();
        $achievement = UploadAchievementTestHelpers::createUnpromotedAchievement($game, $this->user);
        UploadAchievementTestHelpers::addClaim($game, $this->user);

        $this->get($this->apiUrl('uploadachievement', [
            'a' => $achievement->id,
            'g' => $game->id,
            'n' => 'Title2',
            'd' => 'Description2',
            'z' => 10,
            'm' => '0xH0000=2',
            'f' => 5, // Unpromoted - hardcode for test to prevent false success if enum changes
            'b' => '002345',
        ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => 1,
                'Error' => '',
            ]);

        $achievement = Achievement::findOrFail(1);
        $this->assertEquals($game->id, $achievement->game_id);
        $this->assertEquals('Title2', $achievement->title);
        $this->assertEquals('Description2', $achievement->description);
        $this->assertEquals('0xH0000=2', $achievement->trigger_definition);
        $this->assertEquals(10, $achievement->points);
        $this->assertFalse($achievement->is_promoted);
        $this->assertNull($achievement->type);
        $this->assertEquals($this->user->id, $achievement->user_id);
        $this->assertEquals('002345', $achievement->image_name);
        $this->assertNotNull($achievement->modified_at);

        $game->refresh();
        $this->assertEquals(0, $game->achievements_published);
        $this->assertEquals(1, $game->achievements_unpublished);
        $this->assertEquals(0, $game->points_total); // unpromoted achievements don't contribute to points_total
    });

    test('cannot promote for inactive console', function () {
        $this->user->assignRole(Role::DEVELOPER);
        $game = UploadAchievementTestHelpers::createGame();
        $game->system->active = false;
        $game->system->save();
        $achievement = UploadAchievementTestHelpers::createUnpromotedAchievement($game, $this->user);
        // NOTE: developer does not need active claim to promote

        $this->get($this->apiUrl('uploadachievement', [
            'a' => $achievement->id,
            'g' => $game->id,
            'n' => 'Title1',
            'd' => 'Description1',
            'z' => 5,
            'm' => '0xH0000=1',
            'f' => 3, // Publish - hardcode for test to prevent false success if enum changes
            'b' => '001234',
        ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => false,
                'AchievementID' => $achievement->id,
                'Error' => 'You cannot promote achievements for a game from an unsupported console (console ID: 1).',
            ]);

        $achievement->refresh();
        $this->assertFalse($achievement->is_promoted);

        $game->refresh();
        $this->assertEquals(0, $game->achievements_published);
        $this->assertEquals(1, $game->achievements_unpublished);
    });

    test('type remains unchanged if not provided', function () {
        $this->user->assignRole(Role::DEVELOPER);
        $game = UploadAchievementTestHelpers::createGame();
        $achievement = UploadAchievementTestHelpers::createPromotedAchievement($game, $this->user);
        $achievement->type = AchievementType::Missable;
        $achievement->save();
        // NOTE: developer does not need active claim to update existing

        $this->get($this->apiUrl('uploadachievement', [
            'a' => $achievement->id,
            'g' => $game->id,
            'n' => 'Title2',
            'd' => 'Description2',
            'z' => 10,
            'm' => '0xH0000=2',
            'f' => 3,
            'b' => '002345',
            // 'x' not provided
        ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement->id,
                'Error' => '',
            ]);

        $achievement->refresh();
        $this->assertEquals(AchievementType::Missable, $achievement->type);
    });

    test('smart quotes are normalized', function () {
        $this->user->assignRole(Role::DEVELOPER);
        $game = UploadAchievementTestHelpers::createGame();
        $achievement = UploadAchievementTestHelpers::createPromotedAchievement($game, $this->user);
        $achievement->type = AchievementType::Missable;
        $achievement->save();
        // NOTE: developer does not need active claim to update existing

        $this->get($this->apiUrl('uploadachievement', [
            'a' => $achievement->id,
            'g' => $game->id,
            'n' => "\u{201C}Test\u{2019}s Achievement\u{201D}",
            'd' => "It\u{2019}s a \u{201C}test\u{201D} description",
            'z' => 10,
            'm' => '0xH0000=2',
            'f' => 3,
            'b' => '002345',
            // 'x' not provided
        ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement->id,
                'Error' => '',
            ]);

        $achievement->refresh();
        $this->assertEquals("\"Test's Achievement\"", $achievement->title);
        $this->assertEquals("It's a \"test\" description", $achievement->description);
    });

    test('demoting win condition updates beat time', function () {
        $this->user->assignRole(Role::DEVELOPER);
        $game = UploadAchievementTestHelpers::createGame();

        $achievement1 = UploadAchievementTestHelpers::createPromotedAchievement($game, $this->user);
        $achievement1->type = AchievementType::Progression;
        $achievement1->save();
        $achievement2 = UploadAchievementTestHelpers::createPromotedAchievement($game, $this->user);
        $achievement2->type = AchievementType::Progression;
        $achievement2->save();
        $achievement3 = UploadAchievementTestHelpers::createPromotedAchievement($game, $this->user);
        $achievement3->type = AchievementType::WinCondition;
        $achievement3->save();
        $achievement4 = UploadAchievementTestHelpers::createPromotedAchievement($game, $this->user);

        $player = User::factory()->create();
        $time1 = Carbon::now()->subMinutes(25)->startOfSecond();
        $this->addHardcoreUnlock($player, $achievement1, $time1);
        $time2 = $time1->clone()->addMinutes(6);
        $this->addHardcoreUnlock($player, $achievement2, $time2);
        $time3 = $time2->clone()->addMinutes(8);
        $this->addHardcoreUnlock($player, $achievement3, $time3);
        $time4 = $time3->clone()->addMinutes(2);
        $this->addHardcoreUnlock($player, $achievement4, $time4);

        $playerGame = PlayerGame::first();
        (new UpdatePlayerGameMetricsAction())->execute($playerGame);
        $playerGame->refresh();
        $this->assertEquals($time3, $playerGame->beaten_hardcore_at);
        $this->assertEquals(14 * 60, $playerGame->time_to_beat_hardcore);

        // NOTE: developer does not need active claim to demote

        $this->get($this->apiUrl('uploadachievement', [
            'a' => $achievement3->id,
            'g' => $game->id,
            'n' => $achievement3->title,
            'd' => $achievement3->description,
            'z' => $achievement3->points,
            'm' => $achievement3->trigger_definition,
            'f' => Achievement::FLAG_UNPROMOTED,
            'b' => $achievement3->image_name,
            'x' => $achievement3->type,
        ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement3->id,
                'Error' => '',
            ]);

        $playerGame->refresh();
        $this->assertEquals($time2, $playerGame->beaten_hardcore_at);
        $this->assertEquals(6 * 60, $playerGame->time_to_beat_hardcore);
    });

    test('repromoting win condition updates beat time', function () {
        $this->user->assignRole(Role::DEVELOPER);
        $game = UploadAchievementTestHelpers::createGame();

        $achievement1 = UploadAchievementTestHelpers::createPromotedAchievement($game, $this->user);
        $achievement1->type = AchievementType::Progression;
        $achievement1->save();
        $achievement2 = UploadAchievementTestHelpers::createPromotedAchievement($game, $this->user);
        $achievement2->type = AchievementType::Progression;
        $achievement2->save();
        $achievement3 = UploadAchievementTestHelpers::createPromotedAchievement($game, $this->user);
        $achievement3->type = AchievementType::WinCondition;
        $achievement3->save();
        $achievement4 = UploadAchievementTestHelpers::createPromotedAchievement($game, $this->user);

        $player = User::factory()->create();
        $time1 = Carbon::now()->subMinutes(25)->startOfSecond();
        $this->addHardcoreUnlock($player, $achievement1, $time1);
        $time2 = $time1->clone()->addMinutes(6);
        $this->addHardcoreUnlock($player, $achievement2, $time2);
        $time3 = $time2->clone()->addMinutes(8);
        $this->addHardcoreUnlock($player, $achievement3, $time3);
        $time4 = $time3->clone()->addMinutes(2);
        $this->addHardcoreUnlock($player, $achievement4, $time4);

        $achievement3->is_promoted = false;
        $achievement3->save();

        $playerGame = PlayerGame::first();
        (new UpdatePlayerGameMetricsAction())->execute($playerGame);
        $playerGame->refresh();
        $this->assertEquals($time2, $playerGame->beaten_hardcore_at);
        $this->assertEquals(6 * 60, $playerGame->time_to_beat_hardcore);

        // NOTE: developer does not need active claim to demote

        $this->get($this->apiUrl('uploadachievement', [
            'a' => $achievement3->id,
            'g' => $game->id,
            'n' => $achievement3->title,
            'd' => $achievement3->description,
            'z' => $achievement3->points,
            'm' => $achievement3->trigger_definition,
            'f' => Achievement::FLAG_PROMOTED,
            'b' => $achievement3->image_name,
            'x' => $achievement3->type,
        ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement3->id,
                'Error' => '',
            ]);

        $playerGame->refresh();
        $this->assertEquals($time3, $playerGame->beaten_hardcore_at);
        $this->assertEquals(14 * 60, $playerGame->time_to_beat_hardcore);
    });

    test('changing non-progression to progression updates beat time', function () {
        $this->user->assignRole(Role::DEVELOPER);
        $game = UploadAchievementTestHelpers::createGame();

        $achievement1 = UploadAchievementTestHelpers::createPromotedAchievement($game, $this->user);
        $achievement1->type = AchievementType::Progression;
        $achievement1->save();
        $achievement2 = UploadAchievementTestHelpers::createPromotedAchievement($game, $this->user);
        $achievement2->type = AchievementType::Progression;
        $achievement2->save();
        $achievement3 = UploadAchievementTestHelpers::createPromotedAchievement($game, $this->user);
        $achievement3->type = AchievementType::WinCondition;
        $achievement3->save();
        $achievement4 = UploadAchievementTestHelpers::createPromotedAchievement($game, $this->user);

        $player = User::factory()->create();
        $time1 = Carbon::now()->subMinutes(25)->startOfSecond();
        $this->addHardcoreUnlock($player, $achievement1, $time1);
        $time2 = $time1->clone()->addMinutes(6);
        $this->addHardcoreUnlock($player, $achievement2, $time2);
        $time3 = $time2->clone()->addMinutes(8);
        $this->addHardcoreUnlock($player, $achievement3, $time3);
        $time4 = $time3->clone()->addMinutes(2);
        $this->addHardcoreUnlock($player, $achievement4, $time4);

        $playerGame = PlayerGame::first();
        (new UpdatePlayerGameMetricsAction())->execute($playerGame);
        $playerGame->refresh();
        $this->assertEquals($time3, $playerGame->beaten_hardcore_at);
        $this->assertEquals(14 * 60, $playerGame->time_to_beat_hardcore);

        // NOTE: developer does not need active claim to demote

        $this->get($this->apiUrl('uploadachievement', [
            'a' => $achievement4->id,
            'g' => $game->id,
            'n' => $achievement4->title,
            'd' => $achievement4->description,
            'z' => $achievement4->points,
            'm' => $achievement4->trigger_definition,
            'f' => Achievement::FLAG_PROMOTED,
            'b' => $achievement4->image_name,
            'x' => AchievementType::Progression,
        ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement4->id,
                'Error' => '',
            ]);

        $playerGame->refresh();
        $this->assertEquals($time4, $playerGame->beaten_hardcore_at);
        $this->assertEquals(16 * 60, $playerGame->time_to_beat_hardcore);
    });

    test('changing progression to non-progression updates beat time', function () {
        $this->user->assignRole(Role::DEVELOPER);
        $game = UploadAchievementTestHelpers::createGame();

        $achievement1 = UploadAchievementTestHelpers::createPromotedAchievement($game, $this->user);
        $achievement1->type = AchievementType::Progression;
        $achievement1->save();
        $achievement2 = UploadAchievementTestHelpers::createPromotedAchievement($game, $this->user);
        $achievement2->type = AchievementType::Progression;
        $achievement2->save();
        $achievement3 = UploadAchievementTestHelpers::createPromotedAchievement($game, $this->user);
        $achievement3->type = AchievementType::WinCondition;
        $achievement3->save();
        $achievement4 = UploadAchievementTestHelpers::createPromotedAchievement($game, $this->user);
        $achievement4->type = AchievementType::Progression;
        $achievement4->save();

        $player = User::factory()->create();
        $time1 = Carbon::now()->subMinutes(25)->startOfSecond();
        $this->addHardcoreUnlock($player, $achievement1, $time1);
        $time2 = $time1->clone()->addMinutes(6);
        $this->addHardcoreUnlock($player, $achievement2, $time2);
        $time3 = $time2->clone()->addMinutes(8);
        $this->addHardcoreUnlock($player, $achievement3, $time3);
        $time4 = $time3->clone()->addMinutes(2);
        $this->addHardcoreUnlock($player, $achievement4, $time4);

        $playerGame = PlayerGame::first();
        (new UpdatePlayerGameMetricsAction())->execute($playerGame);
        $playerGame->refresh();
        $this->assertEquals($time4, $playerGame->beaten_hardcore_at);
        $this->assertEquals(16 * 60, $playerGame->time_to_beat_hardcore);

        // NOTE: developer does not need active claim to demote

        $this->get($this->apiUrl('uploadachievement', [
            'a' => $achievement4->id,
            'g' => $game->id,
            'n' => $achievement4->title,
            'd' => $achievement4->description,
            'z' => $achievement4->points,
            'm' => $achievement4->trigger_definition,
            'f' => Achievement::FLAG_PROMOTED,
            'b' => $achievement4->image_name,
            'x' => '',
        ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement4->id,
                'Error' => '',
            ]);

        $playerGame->refresh();
        $this->assertEquals($time3, $playerGame->beaten_hardcore_at);
        $this->assertEquals(14 * 60, $playerGame->time_to_beat_hardcore);
    });
});

describe('validation', function () {
    test('g or s is required', function () {
        $this->user->assignRole(Role::DEVELOPER);
        $game = UploadAchievementTestHelpers::createGame();
        UploadAchievementTestHelpers::addClaim($game, $this->user);

        $this->get($this->apiUrl('uploadachievement', [
            'n' => 'Title1',
            'd' => 'Description1',
            'z' => 5,
            'm' => '0xH0000=1',
            'f' => 5, // Unpromoted - hardcode for test to prevent false success if enum changes
            'b' => '001234',
        ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => false,
                'AchievementID' => 0,
                'Error' => 'You must provide a game ID or a game achievement set ID.',
            ]);

        $this->assertEquals(0, Achievement::count());
    });

    test('unknown flag', function () {
        $this->user->assignRole(Role::DEVELOPER);
        $game = UploadAchievementTestHelpers::createGame();
        UploadAchievementTestHelpers::addClaim($game, $this->user);

        $this->get($this->apiUrl('uploadachievement', [
            'g' => $game->id,
            'n' => 'Title1',
            'd' => 'Description1',
            'z' => 5,
            'm' => '0xH0000=1',
            'f' => 4,
            'b' => '001234',
        ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => false,
                'AchievementID' => 0,
                'Error' => 'Invalid achievement flag',
            ]);

        $this->assertEquals(0, Achievement::count());
    });

    test('invalid points', function () {
        $this->user->assignRole(Role::DEVELOPER);
        $game = UploadAchievementTestHelpers::createGame();
        UploadAchievementTestHelpers::addClaim($game, $this->user);

        $this->get($this->apiUrl('uploadachievement', [
            'g' => $game->id,
            'n' => 'Title1',
            'd' => 'Description1',
            'z' => 15,
            'm' => '0xH0000=1',
            'f' => 5,
            'b' => '001234',
        ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => false,
                'AchievementID' => 0,
                'Error' => 'Invalid points value (15).',
            ]);

        $this->assertEquals(0, Achievement::count());
    });

    test('invalid type', function () {
        $this->user->assignRole(Role::DEVELOPER);
        $game = UploadAchievementTestHelpers::createGame();
        UploadAchievementTestHelpers::addClaim($game, $this->user);

        $this->get($this->apiUrl('uploadachievement', [
            'g' => $game->id,
            'n' => 'Title1',
            'd' => 'Description1',
            'z' => 10,
            'm' => '0xH0000=1',
            'f' => 5,
            'b' => '001234',
            'x' => 'unknown',
        ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => false,
                'AchievementID' => 0,
                'Error' => 'Invalid achievement type',
            ]);

        $this->assertEquals(0, Achievement::count());
    });
});
