<?php

declare(strict_types=1);

namespace Tests\Feature\Connect;

use App\Enums\GameHashCompatibility;
use App\Models\Game;
use App\Models\MemoryNote;
use App\Models\Role;
use App\Models\User;
use App\Platform\Services\VirtualGameIdService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;

uses(LazilyRefreshDatabase::class);
uses(TestsConnect::class);

beforeEach(function () {
    Carbon::setTestNow(Carbon::now());

    Role::create(['name' => Role::DEVELOPER, 'display' => 1]);
    Role::create(['name' => Role::DEVELOPER_JUNIOR, 'display' => 2]);

    $this->createConnectUser();
});

describe('developer', function () {
    test('can create new memory note', function () {
        $this->user->assignRole(Role::DEVELOPER);
        $game = Game::factory()->create();

        $this->post('dorequest.php', $this->apiParams('submitcodenote', [
            'g' => $game->id,
            'm' => 0x1234,
            'n' => 'This is a note',
        ]))
            ->assertStatus(200)
            ->assertExactJson(['Success' => true]);

        $game->refresh();
        $note = $game->memoryNotes->where('address', 0x1234)->first();
        $this->assertNotNull($note);
        $this->assertEquals('This is a note', $note->body);
    });

    test('can update own memory note', function () {
        $this->user->assignRole(Role::DEVELOPER);
        $game = Game::factory()->create();
        $note = MemoryNote::create(['game_id' => $game->id, 'user_id' => $this->user->id, 'address' => 0x1234, 'body' => 'Test']);

        $this->post('dorequest.php', $this->apiParams('submitcodenote', [
            'g' => $game->id,
            'm' => $note->address,
            'n' => 'This is a note',
        ]))
            ->assertStatus(200)
            ->assertExactJson(['Success' => true]);

        $note->refresh();
        $this->assertNotNull($note);
        $this->assertEquals('This is a note', $note->body);
    });

    test('can update other\'s memory note', function () {
        $this->user->assignRole(Role::DEVELOPER);
        $game = Game::factory()->create();
        $otherUser = User::factory()->create();
        $note = MemoryNote::create(['game_id' => $game->id, 'user_id' => $otherUser->id, 'address' => 0x1234, 'body' => 'Test']);

        $this->post('dorequest.php', $this->apiParams('submitcodenote', [
            'g' => $game->id,
            'm' => $note->address,
            'n' => 'This is a note',
        ]))
            ->assertStatus(200)
            ->assertExactJson(['Success' => true]);

        $note->refresh();
        $this->assertNotNull($note);
        $this->assertEquals('This is a note', $note->body);
        $this->assertEquals($this->user->id, $note->user_id);
    });

    test('can update memory note through compatibility mapped game', function () {
        $this->user->assignRole(Role::DEVELOPER);
        $game = Game::factory()->create();
        $note = MemoryNote::create(['game_id' => $game->id, 'user_id' => $this->user->id, 'address' => 0x1234, 'body' => 'Test']);

        $this->post('dorequest.php', $this->apiParams('submitcodenote', [
            'g' => VirtualGameIdService::encodeVirtualGameId($game->id, GameHashCompatibility::Untested),
            'm' => $note->address,
            'n' => 'This is a note',
        ]))
            ->assertStatus(200)
            ->assertExactJson(['Success' => true]);

        $note->refresh();
        $this->assertNotNull($note);
        $this->assertEquals('This is a note', $note->body);
    });

    test('can delete own memory note', function () {
        $this->user->assignRole(Role::DEVELOPER);
        $game = Game::factory()->create();
        $note = MemoryNote::create(['game_id' => $game->id, 'user_id' => $this->user->id, 'address' => 0x1234, 'body' => 'Test']);

        $this->post('dorequest.php', $this->apiParams('submitcodenote', [
            'g' => $game->id,
            'm' => $note->address,
            'n' => '',
        ]))
            ->assertStatus(200)
            ->assertExactJson(['Success' => true]);

        $note->refresh();
        $this->assertNotNull($note);
        $this->assertEquals('Test', $note->body); // contents not destroyed
        $this->assertTrue($note->trashed());
    });

    test('can delete other\'s memory note', function () {
        $this->user->assignRole(Role::DEVELOPER);
        $game = Game::factory()->create();
        $otherUser = User::factory()->create();
        $note = MemoryNote::create(['game_id' => $game->id, 'user_id' => $otherUser->id, 'address' => 0x1234, 'body' => 'Test']);

        $this->post('dorequest.php', $this->apiParams('submitcodenote', [
            'g' => $game->id,
            'm' => $note->address,
            'n' => '',
        ]))
            ->assertStatus(200)
            ->assertExactJson(['Success' => true]);

        $note->refresh();
        $this->assertNotNull($note);
        $this->assertEquals('Test', $note->body); // contents not destroyed
        $this->assertTrue($note->trashed());
    });

    test('can replace own deleted memory note', function () {
        $this->user->assignRole(Role::DEVELOPER);
        $game = Game::factory()->create();
        $note = MemoryNote::create(['game_id' => $game->id, 'user_id' => $this->user->id, 'address' => 0x1234, 'body' => 'Test']);
        $note->delete();

        $this->post('dorequest.php', $this->apiParams('submitcodenote', [
            'g' => $game->id,
            'm' => $note->address,
            'n' => 'This is a note',
        ]))
            ->assertStatus(200)
            ->assertExactJson(['Success' => true]);

        $note->refresh();
        $this->assertNotNull($note);
        $this->assertEquals('This is a note', $note->body);
        $this->assertFalse($note->trashed());
    });

    test('can replace other\'s deleted memory note', function () {
        $this->user->assignRole(Role::DEVELOPER);
        $game = Game::factory()->create();
        $otherUser = User::factory()->create();
        $note = MemoryNote::create(['game_id' => $game->id, 'user_id' => $otherUser->id, 'address' => 0x1234, 'body' => 'Test']);
        $note->delete();

        $this->post('dorequest.php', $this->apiParams('submitcodenote', [
            'g' => $game->id,
            'm' => $note->address,
            'n' => 'This is a note',
        ]))
            ->assertStatus(200)
            ->assertExactJson(['Success' => true]);

        $note->refresh();
        $this->assertNotNull($note);
        $this->assertEquals('This is a note', $note->body);
        $this->assertEquals($this->user->id, $note->user_id);
        $this->assertFalse($note->trashed());
    });

    test('cannot submit to invalid game', function () {
        $this->user->assignRole(Role::DEVELOPER);
        $game = Game::factory()->create();

        $this->post('dorequest.php', $this->apiParams('submitcodenote', [
            'g' => 99,
            'm' => 0x1234,
            'n' => 'This is a note',
        ]))
            ->assertStatus(404)
            ->assertExactJson([
                'Success' => false,
                'Status' => 404,
                'Code' => 'not_found',
                'Error' => 'Unknown game.',
            ]);

        $this->assertEquals(0, MemoryNote::count());
    });
});

describe('junior developer', function () {
    test('can create new memory note', function () {
        $this->user->assignRole(Role::DEVELOPER_JUNIOR);
        $game = Game::factory()->create();

        $this->post('dorequest.php', $this->apiParams('submitcodenote', [
            'g' => $game->id,
            'm' => 0x1234,
            'n' => 'This is a note',
        ]))
            ->assertStatus(200)
            ->assertExactJson(['Success' => true]);

        $game->refresh();
        $note = $game->memoryNotes->where('address', 0x1234)->first();
        $this->assertNotNull($note);
        $this->assertEquals('This is a note', $note->body);
    });

    test('can update own memory note', function () {
        $this->user->assignRole(Role::DEVELOPER_JUNIOR);
        $game = Game::factory()->create();
        $note = MemoryNote::create(['game_id' => $game->id, 'user_id' => $this->user->id, 'address' => 0x1234, 'body' => 'Test']);

        $this->post('dorequest.php', $this->apiParams('submitcodenote', [
            'g' => $game->id,
            'm' => $note->address,
            'n' => 'This is a note',
        ]))
            ->assertStatus(200)
            ->assertExactJson(['Success' => true]);

        $note->refresh();
        $this->assertNotNull($note);
        $this->assertEquals('This is a note', $note->body);
    });

    test('cannot update other\'s memory note', function () {
        $this->user->assignRole(Role::DEVELOPER_JUNIOR);
        $game = Game::factory()->create();
        $otherUser = User::factory()->create();
        $note = MemoryNote::create(['game_id' => $game->id, 'user_id' => $otherUser->id, 'address' => 0x1234, 'body' => 'Test']);

        $this->post('dorequest.php', $this->apiParams('submitcodenote', [
            'g' => $game->id,
            'm' => $note->address,
            'n' => 'This is a note',
        ]))
            ->assertStatus(403)
            ->assertExactJson([
                'Success' => false,
                'Status' => 403,
                'Code' => 'access_denied',
                'Error' => 'Access denied.',
            ]);

        $note->refresh();
        $this->assertNotNull($note);
        $this->assertEquals('Test', $note->body);
        $this->assertEquals($otherUser->id, $note->user_id);
    });

    test('can delete own memory note', function () {
        $this->user->assignRole(Role::DEVELOPER_JUNIOR);
        $game = Game::factory()->create();
        $note = MemoryNote::create(['game_id' => $game->id, 'user_id' => $this->user->id, 'address' => 0x1234, 'body' => 'Test']);

        $this->post('dorequest.php', $this->apiParams('submitcodenote', [
            'g' => $game->id,
            'm' => $note->address,
            'n' => '',
        ]))
            ->assertStatus(200)
            ->assertExactJson(['Success' => true]);

        $note->refresh();
        $this->assertNotNull($note);
        $this->assertEquals('Test', $note->body); // contents not destroyed
        $this->assertTrue($note->trashed());
    });

    test('cannot delete other\'s memory note', function () {
        $this->user->assignRole(Role::DEVELOPER_JUNIOR);
        $game = Game::factory()->create();
        $otherUser = User::factory()->create();
        $note = MemoryNote::create(['game_id' => $game->id, 'user_id' => $otherUser->id, 'address' => 0x1234, 'body' => 'Test']);

        $this->post('dorequest.php', $this->apiParams('submitcodenote', [
            'g' => $game->id,
            'm' => $note->address,
            'n' => '',
        ]))
            ->assertStatus(403)
            ->assertExactJson([
                'Success' => false,
                'Status' => 403,
                'Code' => 'access_denied',
                'Error' => 'Access denied.',
            ]);

        $note->refresh();
        $this->assertNotNull($note);
        $this->assertEquals('Test', $note->body);
        $this->assertEquals($otherUser->id, $note->user_id);
        $this->assertFalse($note->trashed());
    });

    test('can replace own deleted memory note', function () {
        $this->user->assignRole(Role::DEVELOPER_JUNIOR);
        $game = Game::factory()->create();
        $note = MemoryNote::create(['game_id' => $game->id, 'user_id' => $this->user->id, 'address' => 0x1234, 'body' => 'Test']);
        $note->delete();

        $this->post('dorequest.php', $this->apiParams('submitcodenote', [
            'g' => $game->id,
            'm' => $note->address,
            'n' => 'This is a note',
        ]))
            ->assertStatus(200)
            ->assertExactJson(['Success' => true]);

        $note->refresh();
        $this->assertNotNull($note);
        $this->assertEquals('This is a note', $note->body);
        $this->assertFalse($note->trashed());
    });

    test('can replace other\'s deleted memory note', function () {
        $this->user->assignRole(Role::DEVELOPER_JUNIOR);
        $game = Game::factory()->create();
        $otherUser = User::factory()->create();
        $note = MemoryNote::create(['game_id' => $game->id, 'user_id' => $otherUser->id, 'address' => 0x1234, 'body' => 'Test']);
        $note->delete();

        $this->post('dorequest.php', $this->apiParams('submitcodenote', [
            'g' => $game->id,
            'm' => $note->address,
            'n' => 'This is a note',
        ]))
            ->assertStatus(200)
            ->assertExactJson(['Success' => true]);

        $note->refresh();
        $this->assertNotNull($note);
        $this->assertEquals('This is a note', $note->body);
        $this->assertEquals($this->user->id, $note->user_id);
        $this->assertFalse($note->trashed());
    });
});

describe('non-developer', function () {
    test('cannot create new memory note', function () {
        $game = Game::factory()->create();

        $this->post('dorequest.php', $this->apiParams('submitcodenote', [
            'g' => $game->id,
            'm' => 0x1234,
            'n' => 'This is a note',
        ]))
            ->assertStatus(403)
            ->assertExactJson([
                'Success' => false,
                'Status' => 403,
                'Code' => 'access_denied',
                'Error' => 'Access denied.',
            ]);

        $game->refresh();
        $note = $game->memoryNotes->where('address', 0x1234)->first();
        $this->assertNull($note);
    });

    test('cannot update other\'s memory note', function () {
        $game = Game::factory()->create();
        $otherUser = User::factory()->create();
        $note = MemoryNote::create(['game_id' => $game->id, 'user_id' => $otherUser->id, 'address' => 0x1234, 'body' => 'Test']);

        $this->post('dorequest.php', $this->apiParams('submitcodenote', [
            'g' => $game->id,
            'm' => $note->address,
            'n' => 'This is a note',
        ]))
            ->assertStatus(403)
            ->assertExactJson([
                'Success' => false,
                'Status' => 403,
                'Code' => 'access_denied',
                'Error' => 'Access denied.',
            ]);

        $note->refresh();
        $this->assertNotNull($note);
        $this->assertEquals('Test', $note->body);
        $this->assertEquals($otherUser->id, $note->user_id);
    });

    test('cannot delete other\'s memory note', function () {
        $this->user->assignRole(Role::DEVELOPER_JUNIOR);
        $game = Game::factory()->create();
        $otherUser = User::factory()->create();
        $note = MemoryNote::create(['game_id' => $game->id, 'user_id' => $otherUser->id, 'address' => 0x1234, 'body' => 'Test']);

        $this->post('dorequest.php', $this->apiParams('submitcodenote', [
            'g' => $game->id,
            'm' => $note->address,
            'n' => '',
        ]))
            ->assertStatus(403)
            ->assertExactJson([
                'Success' => false,
                'Status' => 403,
                'Code' => 'access_denied',
                'Error' => 'Access denied.',
            ]);

        $note->refresh();
        $this->assertNotNull($note);
        $this->assertEquals('Test', $note->body);
        $this->assertEquals($otherUser->id, $note->user_id);
        $this->assertFalse($note->trashed());
    });

    test('cannot replace other\'s deleted memory note', function () {
        $game = Game::factory()->create();
        $otherUser = User::factory()->create();
        $note = MemoryNote::create(['game_id' => $game->id, 'user_id' => $otherUser->id, 'address' => 0x1234, 'body' => 'Test']);
        $note->delete();

        $this->post('dorequest.php', $this->apiParams('submitcodenote', [
            'g' => $game->id,
            'm' => $note->address,
            'n' => 'This is a note',
        ]))
            ->assertStatus(403)
            ->assertExactJson([
                'Success' => false,
                'Status' => 403,
                'Code' => 'access_denied',
                'Error' => 'Access denied.',
            ]);

        $note->refresh();
        $this->assertNotNull($note);
        $this->assertEquals('Test', $note->body);
        $this->assertEquals($otherUser->id, $note->user_id);
        $this->assertTrue($note->trashed());
    });
});
