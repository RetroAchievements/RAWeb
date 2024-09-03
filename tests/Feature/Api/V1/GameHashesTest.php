<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\Game;
use App\Models\GameHash;
use App\Models\System;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GameHashesTest extends TestCase
{
    use RefreshDatabase;
    use BootstrapsApiV1;

    public function testItValidates(): void
    {
        $this->get($this->apiUrl('GetGameLeaderboards'))
            ->assertJsonValidationErrors([
                'i',
            ]);
    }

    public function testIt404sUnknownGames(): void
    {
        $this->get($this->apiUrl('GetGameLeaderboards', ['i' => 99999]))
            ->assertNotFound()
            ->assertJson([]);
    }

    public function testItReturnsGameHashes(): void
    {
        /** Set up a game with 2 hashes */

        /** @var System $system */
        $system = System::factory()->create();

        /** @var Game $game */
        $game = Game::factory()->create(['ConsoleID' => $system->id]);

        /** @var GameHash $gameHashOne */
        $gameHashOne = GameHash::factory()->create([
            'game_id' => $game->id,
            'name' => 'foo',
            'labels' => '',
            'patch_url' => 'https://github.com/somefile.zip',
        ]);
        /** @var GameHash $gameHashTwo */
        $gameHashTwo = GameHash::factory()->create([
            'game_id' => $game->id,
            'name' => 'bar',
            'labels' => 'nointro,redump',
        ]);

        $this->get($this->apiUrl('GetGameHashes', ['i' => $game->id]))
            ->assertSuccessful()
            ->assertJson([
                'Results' => [
                    [
                        'Name' => 'foo',
                        'MD5' => $gameHashOne->md5,
                        'Labels' => [],
                        'PatchUrl' => 'https://github.com/somefile.zip',
                    ],
                    [
                        'Name' => 'bar',
                        'MD5' => $gameHashTwo->md5,
                        'Labels' => ['nointro', 'redump'],
                        'PatchUrl' => null,
                    ],
                ],
            ]);
    }
}
