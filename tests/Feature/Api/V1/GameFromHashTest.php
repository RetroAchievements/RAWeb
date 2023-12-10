<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Platform\Models\Game;
use App\Platform\Models\GameHash;
use App\Platform\Models\System;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GameFromHashTest extends TestCase
{
    use RefreshDatabase;
    use BootstrapsApiV1;

    public function testGetGameFromHashIfNoHashParam(): void
    {
        $this->get($this->apiUrl('GetGameFromHash'))
            ->assertStatus(422)
            ->assertJson([
                'message' => 'The h field is required.',
            ]);
    }

    public function testGetGameFromHashIfInvalidHashParam(): void
    {
        $this->get($this->apiUrl('GetGameFromHash', ['h' => '12345']))
            ->assertStatus(422)
            ->assertJson([
                'message' => 'The h must be 32 characters.',
            ]);
    }

    public function testGetGameFromHashIfGameNotFound(): void
    {
        $targetHash = '1bc674be034e43c96b86487ac69d9293';

        $this->get($this->apiUrl('GetGameFromHash', ['h' => $targetHash]))
            ->assertStatus(404)
            ->assertJson([
                'status' => 404,
                'code' => 'not_found',
                'error' => "Unknown hash: $targetHash",
            ]);
    }

    public function testGetGameMatchingKnownHash(): void
    {
        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create(['ConsoleID' => $system->ID]);
        /** @var GameHash $gameHash */
        $gameHash = GameHash::factory()->create(['GameID' => $game->ID, 'Name' => 'foo']);

        $this->get($this->apiUrl('GetGameFromHash', ['h' => $gameHash->MD5]))
            ->assertSuccessful()
            ->assertJson([
                'ID' => $game->ID,
                'Title' => $game->Title,
                'Description' => $gameHash->Name,
                'ConsoleID' => $system->ID,
                'ConsoleName' => $system->Name,
            ]);
    }
}
