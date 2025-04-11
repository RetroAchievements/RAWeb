<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Enums\GameHashCompatibility;
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
        $this->get($this->apiUrl('GetGameHashes'))
            ->assertJsonValidationErrors([
                'i',
            ]);
    }

    public function testIt404sUnknownGames(): void
    {
        $this->get($this->apiUrl('GetGameHashes', ['i' => 99999]))
            ->assertNotFound()
            ->assertJson(['Results' => []]);
    }

    public function testItReturnsGameHashes(): void
    {
        /** Set up a game with 3 hashes (one incompatible) */

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
        /** @var GameHash $gameHashThree */
        $gameHashThree = GameHash::factory()->create([
            'game_id' => $game->id,
            'name' => 'zoo',
            'labels' => '',
            'compatibility' => GameHashCompatibility::Untested,
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
