<?php

declare(strict_types=1);

namespace Tests\Feature\Connect;

use App\Enums\GameHashCompatibility;
use App\Models\Game;
use App\Models\GameHash;
use App\Models\System;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HashLibraryTest extends TestCase
{
    use BootstrapsConnect;
    use RefreshDatabase;

    private function addHash(Game $game, GameHashCompatibility $compatibility): string
    {
        $hash = GameHash::create([
            'game_id' => $game->id,
            'system_id' => $game->system_id,
            'compatibility' => $compatibility,
            'md5' => fake()->md5,
            'name' => 'hash_' . $game->id,
            'description' => 'hash_' . $game->id,
        ]);

        return $hash->md5;
    }

    public function testGamesList(): void
    {
        /** @var System $system1 */
        $system1 = System::factory()->create();
        /** @var System $system2 */
        $system2 = System::factory()->create();
        /** @var System $system3 */
        $system3 = System::factory()->create();
        /** @var System $system4 */
        $system4 = System::factory()->create();

        $game1 = $this->seedGame(system: $system1);
        $game2 = $this->seedGame(system: $system2);
        $game3 = $this->seedGame(system: $system3);
        $game4 = $this->seedGame(system: $system2);
        $game5 = $this->seedGame(system: $system1);
        $game6 = $this->seedGame(system: $system3);
        $game7 = $this->seedGame(system: $system1);
        $game8 = $this->seedGame(system: $system2);
        $game9 = $this->seedGame(system: $system3);

        // all hashes for console 1
        $this->get($this->apiUrl('hashlibrary', ['c' => $system1->id]))
            ->assertExactJson([
                'Success' => true,
                'MD5List' => [
                    $game1->hashes->first()->md5 => $game1->id,
                    $game5->hashes->first()->md5 => $game5->id,
                    $game7->hashes->first()->md5 => $game7->id,
                ],
            ]);

        // all hashes for console 2
        $this->get($this->apiUrl('hashlibrary', ['c' => $system2->id]))
            ->assertExactJson([
                'Success' => true,
                'MD5List' => [
                    $game2->hashes->first()->md5 => $game2->id,
                    $game4->hashes->first()->md5 => $game4->id,
                    $game8->hashes->first()->md5 => $game8->id,
                ],
            ]);

        // all hashes for console 3
        $this->get($this->apiUrl('hashlibrary', ['c' => $system3->id]))
            ->assertExactJson([
                'Success' => true,
                'MD5List' => [
                    $game3->hashes->first()->md5 => $game3->id,
                    $game6->hashes->first()->md5 => $game6->id,
                    $game9->hashes->first()->md5 => $game9->id,
                ],
            ]);

        // all hashes for console 4
        $this->get($this->apiUrl('hashlibrary', ['c' => $system4->id]))
            ->assertJson([
                'Success' => true,
            ])
            // we can't user assertExactJson because it converts the empty object
            // to an array and we want to ensure an empty object was received
            ->assertSee('"MD5List":{}', escape: false);

        // all hashes for non-existant console
        $this->get($this->apiUrl('hashlibrary', ['c' => 99]))
            ->assertStatus(404)
            ->assertExactJson([
                'Success' => false,
                'Status' => 404,
                'Code' => 'not_found',
                'Error' => 'Unknown console.',
            ]);

        // multiple hashes for some games - only compatible ones are returned
        $hash2b = $this->addHash($game2, GameHashCompatibility::Compatible);
        $hash2c = $this->addHash($game2, GameHashCompatibility::Compatible);
        $hash4b = $this->addHash($game4, GameHashCompatibility::Untested);
        $hash8b = $this->addHash($game8, GameHashCompatibility::PatchRequired);
        $hash8c = $this->addHash($game8, GameHashCompatibility::Compatible);

        $this->get($this->apiUrl('hashlibrary', ['c' => $system2->id]))
            ->assertExactJson([
                'Success' => true,
                'MD5List' => [
                    $game2->hashes->first()->md5 => $game2->id,
                    $hash2b => $game2->id,
                    $hash2c => $game2->id,
                    $game4->hashes->first()->md5 => $game4->id,
                    $game8->hashes->first()->md5 => $game8->id,
                    $hash8c => $game8->id,
                ],
            ]);

        // all hashes for all consoles
        $this->get($this->apiUrl('hashlibrary'))
            ->assertExactJson([
                'Success' => true,
                'MD5List' => [
                    $game1->hashes->first()->md5 => $game1->id,
                    $game2->hashes->first()->md5 => $game2->id,
                    $hash2b => $game2->id,
                    $hash2c => $game2->id,
                    $game3->hashes->first()->md5 => $game3->id,
                    $game4->hashes->first()->md5 => $game4->id,
                    $game5->hashes->first()->md5 => $game5->id,
                    $game6->hashes->first()->md5 => $game6->id,
                    $game7->hashes->first()->md5 => $game7->id,
                    $game8->hashes->first()->md5 => $game8->id,
                    $hash8c => $game8->id,
                    $game9->hashes->first()->md5 => $game9->id,
                ],
            ]);
    }
}
