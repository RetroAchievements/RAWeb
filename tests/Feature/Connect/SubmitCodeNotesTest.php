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

        $this->post('dorequest.php', $this->apiParams('submitcodenotes', [
            'g' => $game->id,
            'n' => "4660:This is a note\n",
        ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'SuccessfulAddresses' => [0x1234],
            ]);

        $game->refresh();
        $note = $game->memoryNotes->where('address', 0x1234)->first();
        $this->assertNotNull($note);
        $this->assertEquals('This is a note', $note->body);
    });

    test('can create new memory notes', function () {
        $this->user->assignRole(Role::DEVELOPER);
        $game = Game::factory()->create();

        $this->post('dorequest.php', $this->apiParams('submitcodenotes', [
            'g' => $game->id,
            'n' => "4660:This is a note\n4661:This is another note\n4096:Third note here.",
        ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'SuccessfulAddresses' => [0x1234, 0x1235, 0x1000],
            ]);

        $game->refresh();
        $note = $game->memoryNotes->where('address', 0x1234)->first();
        $this->assertNotNull($note);
        $this->assertEquals('This is a note', $note->body);
        $note = $game->memoryNotes->where('address', 0x1235)->first();
        $this->assertNotNull($note);
        $this->assertEquals('This is another note', $note->body);
        $note = $game->memoryNotes->where('address', 0x1000)->first();
        $this->assertNotNull($note);
        $this->assertEquals('Third note here.', $note->body);
    });

    test('can create new memory notes containing special characters', function () {
        $this->user->assignRole(Role::DEVELOPER);
        $game = Game::factory()->create();

        $this->post('dorequest.php', $this->apiParams('submitcodenotes', [
            'g' => $game->id,
            'n' => "4660:[8-bit] In-game\\n0=No\\n1=Yes\n4661:This \"note\" is \$pec!al \\\\O/ +2\n",
        ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'SuccessfulAddresses' => [0x1234, 0x1235],
            ]);

        $game->refresh();
        $note = $game->memoryNotes->where('address', 0x1234)->first();
        $this->assertNotNull($note);
        $this->assertEquals("[8-bit] In-game\n0=No\n1=Yes", $note->body);
        $note = $game->memoryNotes->where('address', 0x1235)->first();
        $this->assertNotNull($note);
        $this->assertEquals('This "note" is $pec!al \O/ +2', $note->body);
    });

    test('can update own memory note', function () {
        $this->user->assignRole(Role::DEVELOPER);
        $game = Game::factory()->create();
        $note = MemoryNote::create(['game_id' => $game->id, 'user_id' => $this->user->id, 'address' => 0x1234, 'body' => 'Test']);

        $this->post('dorequest.php', $this->apiParams('submitcodenotes', [
            'g' => $game->id,
            'n' => "4660:This is a note\n",
        ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'SuccessfulAddresses' => [0x1234],
            ]);

        $note->refresh();
        $this->assertNotNull($note);
        $this->assertEquals('This is a note', $note->body);
    });

    test('can update other\'s memory note', function () {
        $this->user->assignRole(Role::DEVELOPER);
        $game = Game::factory()->create();
        $otherUser = User::factory()->create();
        $note = MemoryNote::create(['game_id' => $game->id, 'user_id' => $otherUser->id, 'address' => 0x1234, 'body' => 'Test']);

        $this->post('dorequest.php', $this->apiParams('submitcodenotes', [
            'g' => $game->id,
            'n' => "4660:This is a note\n",
        ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'SuccessfulAddresses' => [0x1234],
            ]);

        $note->refresh();
        $this->assertNotNull($note);
        $this->assertEquals('This is a note', $note->body);
        $this->assertEquals($this->user->id, $note->user_id);
    });

    test('can update memory note through compatibility mapped game', function () {
        $this->user->assignRole(Role::DEVELOPER);
        $game = Game::factory()->create();
        $note = MemoryNote::create(['game_id' => $game->id, 'user_id' => $this->user->id, 'address' => 0x1234, 'body' => 'Test']);

        $this->post('dorequest.php', $this->apiParams('submitcodenotes', [
            'g' => VirtualGameIdService::encodeVirtualGameId($game->id, GameHashCompatibility::Untested),
            'n' => "4660:This is a note\n",
        ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'SuccessfulAddresses' => [0x1234],
            ]);

        $note->refresh();
        $this->assertNotNull($note);
        $this->assertEquals('This is a note', $note->body);
    });

    test('can delete own memory note', function () {
        $this->user->assignRole(Role::DEVELOPER);
        $game = Game::factory()->create();
        $note = MemoryNote::create(['game_id' => $game->id, 'user_id' => $this->user->id, 'address' => 0x1234, 'body' => 'Test']);

        $this->post('dorequest.php', $this->apiParams('submitcodenotes', [
            'g' => $game->id,
            'n' => "4660:\n",
        ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'SuccessfulAddresses' => [0x1234],
            ]);

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

        $this->post('dorequest.php', $this->apiParams('submitcodenotes', [
            'g' => $game->id,
            'n' => "4660:\n",
        ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'SuccessfulAddresses' => [0x1234],
            ]);

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

        $this->post('dorequest.php', $this->apiParams('submitcodenotes', [
            'g' => $game->id,
            'n' => "4660:This is a note\n",
        ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'SuccessfulAddresses' => [0x1234],
            ]);

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

        $this->post('dorequest.php', $this->apiParams('submitcodenotes', [
            'g' => $game->id,
            'n' => "4660:This is a note\n",
        ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'SuccessfulAddresses' => [0x1234],
            ]);

        $note->refresh();
        $this->assertNotNull($note);
        $this->assertEquals('This is a note', $note->body);
        $this->assertEquals($this->user->id, $note->user_id);
        $this->assertFalse($note->trashed());
    });

    test('can create and delete in same request', function () {
        $this->user->assignRole(Role::DEVELOPER);
        $game = Game::factory()->create();
        $otherUser = User::factory()->create();
        $note = MemoryNote::create(['game_id' => $game->id, 'user_id' => $otherUser->id, 'address' => 0x1234, 'body' => 'Test']);

        $this->post('dorequest.php', $this->apiParams('submitcodenotes', [
            'g' => $game->id,
            'n' => "4660:\n4661:This is another note\n",
        ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'SuccessfulAddresses' => [0x1234, 0x1235],
            ]);

        $game->refresh();
        $note = $game->memoryNotes->where('address', 0x1234)->first();
        $this->assertNull($note);
        $note = $game->memoryNotes->where('address', 0x1235)->first();
        $this->assertNotNull($note);
        $this->assertEquals('This is another note', $note->body);
    });

    test('cannot submit to invalid game', function () {
        $this->user->assignRole(Role::DEVELOPER);
        $game = Game::factory()->create();

        $this->post('dorequest.php', $this->apiParams('submitcodenotes', [
            'g' => 99,
            'n' => "4660:This is a note\n",
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

    test('cannot submit invalidly encoded notes', function () {
        $this->user->assignRole(Role::DEVELOPER);
        $game = Game::factory()->create();

        $this->post('dorequest.php', $this->apiParams('submitcodenotes', [
            'g' => 99,
            'n' => "4660=This is a note\n",
        ]))
            ->assertStatus(422)
            ->assertExactJson([
                'Success' => false,
                'Status' => 422,
                'Code' => 'invalid_parameter',
                'Error' => 'Improperly encoded notes list.',
            ]);

        $this->assertEquals(0, MemoryNote::count());
    });

    test('cannot submit more notes than the per-request limit', function () {
        $this->user->assignRole(Role::DEVELOPER);
        $game = Game::factory()->create();

        $lines = [];
        for ($i = 1; $i <= 501; $i++) {
            $lines[] = $i . ':Note ' . $i;
        }

        $this->post('dorequest.php', $this->apiParams('submitcodenotes', [
            'g' => $game->id,
            'n' => implode("\n", $lines),
        ]))
            ->assertStatus(422)
            ->assertExactJson([
                'Success' => false,
                'Status' => 422,
                'Code' => 'invalid_parameter',
                'Error' => 'Too many notes in a single request.',
            ]);

        $this->assertEquals(0, MemoryNote::count());
    });
});

describe('junior developer', function () {
    test('can create new memory note', function () {
        $this->user->assignRole(Role::DEVELOPER_JUNIOR);
        $game = Game::factory()->create();

        $this->post('dorequest.php', $this->apiParams('submitcodenotes', [
            'g' => $game->id,
            'n' => "4660:This is a note\n",
        ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'SuccessfulAddresses' => [0x1234],
            ]);

        $game->refresh();
        $note = $game->memoryNotes->where('address', 0x1234)->first();
        $this->assertNotNull($note);
        $this->assertEquals('This is a note', $note->body);
    });

    test('can update own memory note', function () {
        $this->user->assignRole(Role::DEVELOPER_JUNIOR);
        $game = Game::factory()->create();
        $note = MemoryNote::create(['game_id' => $game->id, 'user_id' => $this->user->id, 'address' => 0x1234, 'body' => 'Test']);

        $this->post('dorequest.php', $this->apiParams('submitcodenotes', [
            'g' => $game->id,
            'n' => "4660:This is a note\n",
        ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'SuccessfulAddresses' => [0x1234],
            ]);

        $note->refresh();
        $this->assertNotNull($note);
        $this->assertEquals('This is a note', $note->body);
    });

    test('cannot update other\'s memory note', function () {
        $this->user->assignRole(Role::DEVELOPER_JUNIOR);
        $game = Game::factory()->create();
        $otherUser = User::factory()->create();
        $note = MemoryNote::create(['game_id' => $game->id, 'user_id' => $otherUser->id, 'address' => 0x1234, 'body' => 'Test']);

        $this->post('dorequest.php', $this->apiParams('submitcodenotes', [
            'g' => $game->id,
            'n' => "4660:This is a note\n",
        ]))
            ->assertStatus(403)
            ->assertExactJson([
                'Success' => false,
                'Status' => 403,
                'Code' => 'access_denied',
                'Error' => 'Access denied.',
                'SuccessfulAddresses' => [],
                'AccessDeniedAddresses' => [0x1234],
            ]);

        $note->refresh();
        $this->assertNotNull($note);
        $this->assertEquals('Test', $note->body);
        $this->assertEquals($otherUser->id, $note->user_id);
    });

    test('can update own memory note but not other\'s', function () {
        $this->user->assignRole(Role::DEVELOPER_JUNIOR);
        $game = Game::factory()->create();
        $ownNote = MemoryNote::create(['game_id' => $game->id, 'user_id' => $this->user->id, 'address' => 0x1234, 'body' => 'Test']);
        $otherUser = User::factory()->create();
        $otherNote = MemoryNote::create(['game_id' => $game->id, 'user_id' => $otherUser->id, 'address' => 0x1235, 'body' => 'Test']);

        $this->post('dorequest.php', $this->apiParams('submitcodenotes', [
            'g' => $game->id,
            'n' => "4660:This is a note\n4661:This is another note\n",
        ]))
            ->assertStatus(403)
            ->assertExactJson([
                'Success' => false,
                'Status' => 403,
                'Code' => 'access_denied',
                'Error' => 'Access denied.',
                'SuccessfulAddresses' => [0x1234],
                'AccessDeniedAddresses' => [0x1235],
            ]);

        $ownNote->refresh();
        $this->assertEquals('This is a note', $ownNote->body);
        $otherNote->refresh();
        $this->assertEquals('Test', $otherNote->body);
        $this->assertEquals($otherUser->id, $otherNote->user_id);
    });

    test('can delete own memory note', function () {
        $this->user->assignRole(Role::DEVELOPER_JUNIOR);
        $game = Game::factory()->create();
        $note = MemoryNote::create(['game_id' => $game->id, 'user_id' => $this->user->id, 'address' => 0x1234, 'body' => 'Test']);

        $this->post('dorequest.php', $this->apiParams('submitcodenotes', [
            'g' => $game->id,
            'n' => "4660:\n",
        ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'SuccessfulAddresses' => [0x1234],
            ]);

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

        $this->post('dorequest.php', $this->apiParams('submitcodenotes', [
            'g' => $game->id,
            'n' => "4660:\n",
        ]))
            ->assertStatus(403)
            ->assertExactJson([
                'Success' => false,
                'Status' => 403,
                'Code' => 'access_denied',
                'Error' => 'Access denied.',
                'SuccessfulAddresses' => [],
                'AccessDeniedAddresses' => [0x1234],
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

        $this->post('dorequest.php', $this->apiParams('submitcodenotes', [
            'g' => $game->id,
            'n' => "4660:This is a note\n",
        ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'SuccessfulAddresses' => [0x1234],
            ]);

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

        $this->post('dorequest.php', $this->apiParams('submitcodenotes', [
            'g' => $game->id,
            'n' => "4660:This is a note\n",
        ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'SuccessfulAddresses' => [0x1234],
            ]);

        $note->refresh();
        $this->assertNotNull($note);
        $this->assertEquals('This is a note', $note->body);
        $this->assertEquals($this->user->id, $note->user_id);
        $this->assertFalse($note->trashed());
    });
});

describe('non-developer', function () {
    test('is rejected by the developer gate without touching any notes', function () {
        /**
         * Non-developers have no MemoryNote capability, so the endpoint must
         * short-circuit before parsing or querying. The fixture mixes a
         * pre-existing live not and a soft-deleted note to confirm neither
         * is touched by the request.
         */
        $game = Game::factory()->create();
        $otherUser = User::factory()->create();
        $live = MemoryNote::create(['game_id' => $game->id, 'user_id' => $otherUser->id, 'address' => 0x1234, 'body' => 'Live']);
        $trashed = MemoryNote::create(['game_id' => $game->id, 'user_id' => $otherUser->id, 'address' => 0x1235, 'body' => 'Trashed']);
        $trashed->delete();

        $this->post('dorequest.php', $this->apiParams('submitcodenotes', [
            'g' => $game->id,
            'n' => "4660:Try to update\n4661:Try to replace\n4662:Try to create\n",
        ]))
            ->assertStatus(403)
            ->assertExactJson([
                'Success' => false,
                'Status' => 403,
                'Code' => 'access_denied',
                'Error' => 'You must be a developer to perform this action! Please drop a message in the forums to apply.',
            ]);

        $live->refresh();
        $this->assertEquals('Live', $live->body);
        $this->assertEquals($otherUser->id, $live->user_id);
        $this->assertFalse($live->trashed());

        $trashed->refresh();
        $this->assertEquals('Trashed', $trashed->body);
        $this->assertTrue($trashed->trashed());

        $this->assertNull($game->memoryNotes()->where('address', 0x1236)->first());
    });

    test('is rejected before parsing an oversized payload', function () {
        /**
         * Non-developers must be rejected up front so a potential attacker can't
         * force unbounded parsing/IN() queries by submitting massive note payloads.
         */
        $game = Game::factory()->create();

        $lines = [];
        for ($i = 1; $i <= 501; $i++) {
            $lines[] = $i . ':Note ' . $i;
        }

        $this->post('dorequest.php', $this->apiParams('submitcodenotes', [
            'g' => $game->id,
            'n' => implode("\n", $lines),
        ]))
            ->assertStatus(403)
            ->assertExactJson([
                'Success' => false,
                'Status' => 403,
                'Code' => 'access_denied',
                'Error' => 'You must be a developer to perform this action! Please drop a message in the forums to apply.',
            ]);

        $this->assertEquals(0, MemoryNote::count());
    });
});
