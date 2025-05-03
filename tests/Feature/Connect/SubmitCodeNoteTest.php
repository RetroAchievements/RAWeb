<?php

declare(strict_types=1);

namespace Tests\Feature\Connect;

use App\Enums\GameHashCompatibility;
use App\Enums\Permissions;
use App\Models\Game;
use App\Models\Role;
use App\Models\User;
use App\Platform\Services\VirtualGameIdService;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SubmitCodeNoteTest extends TestCase
{
    use BootstrapsConnect;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesTableSeeder::class);

        $this->user = User::factory()->create(['appToken' => Str::random(16), 'Permissions' => Permissions::Developer]);
        $this->user->assignRole(Role::DEVELOPER);
    }

    public function testSubmitCodeNoteDeveloper(): void
    {
        $game = $this->seedGame();

        // ----------------------------
        // invalid credentials
        $this->post('dorequest.php', $this->apiParams('submitcodenote', [
            't' => 'IvalidToken',
            'g' => $game->ID,
            'm' => 0x1234,
            'n' => 'This is a note',
        ]))
            ->assertExactJson([
                'Success' => false,
                'Status' => 403,
                'Code' => 'access_denied',
                'Error' => 'Access denied.',
            ]);

        $note = $game->memoryNotes->where('address', 0x1234)->first();
        $this->assertNull($note);

        // ----------------------------
        // new note for valid game
        $this->post('dorequest.php', $this->apiParams('submitcodenote', [
            'g' => $game->ID,
            'm' => 0x1234,
            'n' => 'This is a note',
        ]))
            ->assertExactJson(['Success' => true]);

        $game->refresh();
        $note = $game->memoryNotes->where('address', 0x1234)->first();
        $this->assertNotNull($note);
        $this->assertEquals('This is a note', $note->body);

        // ----------------------------
        // update note
        $this->post('dorequest.php', $this->apiParams('submitcodenote', [
            'g' => $game->ID,
            'm' => 0x1234,
            'n' => 'This is a new note',
        ]))
            ->assertExactJson(['Success' => true]);

        $note->refresh();
        $this->assertEquals('This is a new note', $note->body);

        // ----------------------------
        // delete note by setting to empty
        $this->post('dorequest.php', $this->apiParams('submitcodenote', [
            'g' => $game->ID,
            'm' => 0x1234,
            'n' => '',
        ]))
            ->assertExactJson(['Success' => true]);

        $note->refresh();
        $this->assertTrue($note->trashed());
        $this->assertEquals('This is a new note', $note->body);

        // ----------------------------
        // new note for unknown game
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

        // ----------------------------
        // update note for compatibility mapped game
        $this->post('dorequest.php', $this->apiParams('submitcodenote', [
            'g' => VirtualGameIdService::encodeVirtualGameId($game->ID, GameHashCompatibility::Untested),
            'm' => 0x1234,
            'n' => 'This is a virtual note',
        ]))
            ->assertExactJson(['Success' => true]);

        $note->refresh();
        $this->assertFalse($note->trashed());
        $this->assertEquals('This is a virtual note', $note->body);

        // ----------------------------
        // second note
        $this->post('dorequest.php', $this->apiParams('submitcodenote', [
            'g' => $game->ID,
            'm' => 0x1235,
            'n' => 'This "note" is $pec!al',
        ]))
            ->assertExactJson(['Success' => true]);

        $note->refresh();
        $this->assertEquals('This is a virtual note', $note->body);

        $game->refresh();
        $newNote = $game->memoryNotes->where('address', 0x1235)->first();
        $this->assertNotNull($newNote);
        $this->assertEquals('This "note" is $pec!al', $newNote->body);

        // ----------------------------
        // delete note by setting to null
        $this->post('dorequest.php', $this->apiParams('submitcodenote', [
            'g' => $game->ID,
            'm' => 0x1235,
        ]))
            ->assertExactJson(['Success' => true]);

        $newNote->refresh();
        $this->assertTrue($newNote->trashed());
        $this->assertEquals('This "note" is $pec!al', $newNote->body);

    }

    public function testSubmitCodeNoteJuniorDeveloper(): void
    {
        $game = $this->seedGame();

        $developer = $this->user;

        $this->user = User::factory()->create(['appToken' => Str::random(16), 'Permissions' => Permissions::JuniorDeveloper]);
        $this->user->assignRole(Role::DEVELOPER_JUNIOR);

        // ----------------------------
        // new note for valid game
        $this->post('dorequest.php', $this->apiParams('submitcodenote', [
            'g' => $game->ID,
            'm' => 0x1234,
            'n' => 'This is a note',
        ]))
            ->assertExactJson(['Success' => true]);

        $note = $game->memoryNotes->where('address', 0x1234)->first();
        $this->assertNotNull($note);
        $this->assertEquals('This is a note', $note->body);

        // ----------------------------
        // update own note
        $this->post('dorequest.php', $this->apiParams('submitcodenote', [
            'g' => $game->ID,
            'm' => 0x1234,
            'n' => 'This is a new note',
        ]))
            ->assertExactJson(['Success' => true]);

        $note->refresh();
        $this->assertEquals('This is a new note', $note->body);

        // ----------------------------
        // delete own note
        $this->post('dorequest.php', $this->apiParams('submitcodenote', [
            'g' => $game->ID,
            'm' => 0x1234,
            'n' => '',
        ]))
            ->assertExactJson(['Success' => true]);

        $note->refresh();
        $this->assertTrue($note->trashed());
        $this->assertEquals('This is a new note', $note->body);

        // ----------------------------
        // restore own note
        $this->post('dorequest.php', $this->apiParams('submitcodenote', [
            'g' => $game->ID,
            'm' => 0x1234,
            'n' => 'This is a restored note',
        ]))
            ->assertExactJson(['Success' => true]);

        $note->refresh();
        $this->assertEquals('This is a restored note', $note->body);

        // ----------------------------
        // update developer note
        $note->user_id = $developer->id;
        $note->save();

        $this->post('dorequest.php', $this->apiParams('submitcodenote', [
            'g' => $game->ID,
            'm' => 0x1234,
            'n' => 'This is an overwritten note',
        ]))
            ->assertStatus(403)
            ->assertExactJson([
                'Success' => false,
                'Status' => 403,
                'Code' => 'access_denied',
                'Error' => 'Access denied.',
            ]);

        $note->refresh();
        $this->assertEquals('This is a restored note', $note->body);

        // ----------------------------
        // delete developer note
        $this->post('dorequest.php', $this->apiParams('submitcodenote', [
            'g' => $game->ID,
            'm' => 0x1234,
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
        $this->assertEquals('This is a restored note', $note->body);

        // ----------------------------
        // update deleted developer note
        $note->delete();

        $this->post('dorequest.php', $this->apiParams('submitcodenote', [
            'g' => $game->ID,
            'm' => 0x1234,
            'n' => 'This is an overwritten note',
        ]))
            ->assertExactJson(['Success' => true]);

        $note->refresh();
        $this->assertEquals('This is an overwritten note', $note->body);
    }
}
