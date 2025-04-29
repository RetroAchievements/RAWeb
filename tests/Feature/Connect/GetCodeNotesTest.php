<?php

declare(strict_types=1);

namespace Tests\Feature\Connect;

use App\Enums\GameHashCompatibility;
use App\Models\Game;
use App\Models\MemoryNote;
use App\Models\User;
use App\Platform\Services\VirtualGameIdService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class GetCodeNotesTest extends TestCase
{
    use BootstrapsConnect;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['appToken' => Str::random(16), 'display_name' => 'FirstUser']);
    }

    public function testGetCodeNotes(): void
    {
        $game = $this->seedGame();

        $note1 = new MemoryNote([
            'game_id' => $game->id,
            'address' => 0x1234,
            'user_id' => $this->user->id,
            'body' => 'This is a note',
        ]);
        $note1->save();

        $note2 = new MemoryNote([
            'game_id' => $game->id,
            'address' => 0x1235,
            'user_id' => $this->user->id,
            'body' => 'This is another note',
        ]);
        $note2->save();

        $otherUser = User::factory()->create(['display_name' => 'ImUnique']);
        $note3 = new MemoryNote([
            'game_id' => $game->id,
            'address' => 0x0BED,
            'user_id' => $otherUser->id,
            'body' => 'Useful?',
        ]);
        $note3->save();

        // ----------------------------
        // valid notes returned
        $this->post('dorequest.php', $this->apiParams('codenotes2', [
            'g' => $game->ID,
        ]))
            ->assertExactJson([
                'Success' => true,
                'CodeNotes' => [
                    [
                        'User' => $otherUser->display_name,
                        'Address' => '0x000bed',
                        'Note' => 'Useful?',
                    ],
                    [
                        'User' => $this->user->display_name,
                        'Address' => '0x001234',
                        'Note' => 'This is a note',
                    ],
                    [
                        'User' => $this->user->display_name,
                        'Address' => '0x001235',
                        'Note' => 'This is another note',
                    ],
                ],
            ]);

        // ----------------------------
        // empty note not returned (legacy delete)
        $note1->body = '';
        $note1->save();
        $this->post('dorequest.php', $this->apiParams('codenotes2', [
            'g' => $game->ID,
        ]))
            ->assertExactJson([
                'Success' => true,
                'CodeNotes' => [
                    [
                        'User' => $otherUser->display_name,
                        'Address' => '0x000bed',
                        'Note' => 'Useful?',
                    ],
                    [
                        'User' => $this->user->display_name,
                        'Address' => '0x001235',
                        'Note' => 'This is another note',
                    ],
                ],
            ]);

        // ----------------------------
        // deleted note not returned
        $note1->body = 'Not deleted';
        $note1->save();
        $note1->delete();
        $this->post('dorequest.php', $this->apiParams('codenotes2', [
            'g' => $game->ID,
        ]))
            ->assertExactJson([
                'Success' => true,
                'CodeNotes' => [
                    [
                        'User' => $otherUser->display_name,
                        'Address' => '0x000bed',
                        'Note' => 'Useful?',
                    ],
                    [
                        'User' => $this->user->display_name,
                        'Address' => '0x001235',
                        'Note' => 'This is another note',
                    ],
                ],
            ]);

        // ----------------------------
        // unauthenticated
        $this->post('dorequest.php', $this->apiParams('codenotes2', [
            'g' => $game->ID,
        ], credentials: false))
            ->assertExactJson([
                'Success' => true,
                'CodeNotes' => [
                    [
                        'User' => $otherUser->display_name,
                        'Address' => '0x000bed',
                        'Note' => 'Useful?',
                    ],
                    [
                        'User' => $this->user->display_name,
                        'Address' => '0x001235',
                        'Note' => 'This is another note',
                    ],
                ],
            ]);

        // ----------------------------
        // virtualized game id
        $this->post('dorequest.php', $this->apiParams('codenotes2', [
            'g' => VirtualGameIdService::encodeVirtualGameId($game->ID, GameHashCompatibility::Untested),
        ]))
            ->assertExactJson([
                'Success' => true,
                'CodeNotes' => [
                    [
                        'User' => $otherUser->display_name,
                        'Address' => '0x000bed',
                        'Note' => 'Useful?',
                    ],
                    [
                        'User' => $this->user->display_name,
                        'Address' => '0x001235',
                        'Note' => 'This is another note',
                    ],
                ],
            ]);

        // ----------------------------
        // non-existant game
        $this->post('dorequest.php', $this->apiParams('codenotes2', [
            'g' => 99,
        ]))
            ->assertStatus(404)
            ->assertExactJson([
                'Success' => false,
                'Status' => 404,
                'Code' => 'not_found',
                'Error' => 'Unknown game.',
            ]);

    }
}
