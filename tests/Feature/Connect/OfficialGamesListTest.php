<?php

declare(strict_types=1);

namespace Tests\Feature\Connect;

use App\Models\Game;
use App\Models\System;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;

uses(LazilyRefreshDatabase::class);
uses(TestsConnect::class);

function createGames(): array
{
    /** @var System $system1 */
    $system1 = System::factory()->create();
    /** @var System $system2 */
    $system2 = System::factory()->create();
    /** @var System $system3 */
    $system3 = System::factory()->create();

    // games 1,2,3 have only published achievements
    // games 4,5,6 have only unpublished achievements
    // games 7,8,9 have no achievements
    // game 10 has published and unpublished achievements
    /** @var Game $game1 */
    $game1 = Game::factory()->create(['title' => 'One', 'system_id' => $system1->id, 'image_icon_asset_path' => '/Images/000001.png', 'achievements_published' => 3]);
    /** @var Game $game2 */
    $game2 = Game::factory()->create(['title' => 'Two', 'system_id' => $system2->id, 'image_icon_asset_path' => '/Images/000002.png', 'achievements_published' => 7]);
    /** @var Game $game3 */
    $game3 = Game::factory()->create(['title' => 'Three', 'system_id' => $system3->id, 'image_icon_asset_path' => '/Images/000003.png', 'achievements_published' => 11]);
    /** @var Game $game4 */
    $game4 = Game::factory()->create(['title' => 'Four', 'system_id' => $system2->id, 'image_icon_asset_path' => '/Images/000004.png', 'achievements_unpublished' => 5]);
    /** @var Game $game5 */
    $game5 = Game::factory()->create(['title' => 'Five', 'system_id' => $system1->id, 'image_icon_asset_path' => '/Images/000005.png', 'achievements_unpublished' => 9]);
    /** @var Game $game6 */
    $game6 = Game::factory()->create(['title' => 'Six', 'system_id' => $system3->id, 'image_icon_asset_path' => '/Images/000006.png', 'achievements_unpublished' => 1]);
    /** @var Game $game7 */
    $game7 = Game::factory()->create(['title' => 'Seven', 'system_id' => $system1->id, 'image_icon_asset_path' => '/Images/000007.png']);
    /** @var Game $game8 */
    $game8 = Game::factory()->create(['title' => 'Eight', 'system_id' => $system2->id, 'image_icon_asset_path' => '/Images/000008.png']);
    /** @var Game $game9 */
    $game9 = Game::factory()->create(['title' => 'Nine', 'system_id' => $system3->id, 'image_icon_asset_path' => '/Images/000009.png']);
    /** @var Game $game10 */
    $game10 = Game::factory()->create(['title' => 'Ten', 'system_id' => $system2->id, 'image_icon_asset_path' => '/Images/000010.png', 'achievements_published' => 2, 'achievements_unpublished' => 1]);

    return [
        $game1,
        $game2,
        $game3,
        $game4,
        $game5,
        $game6,
        $game7,
        $game8,
        $game9,
        $game10,
    ];
}

describe('get', function () {
    test('returns data for console 1', function () {
        $games = createGames();

        $this->get($this->apiUrl('officialgameslist', ['c' => $games[0]->system->id], credentials: false))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'Response' => [
                    '1' => 'One',
                ],
            ]);
    });

    test('returns data for console 2', function () {
        $games = createGames();

        $this->get($this->apiUrl('officialgameslist', ['c' => $games[1]->system->id], credentials: false))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'Response' => [
                    '10' => 'Ten',
                    '2' => 'Two',
                ],
            ])
            ->assertJson(function (AssertableJson $json) {
                $json->has("Response", function (AssertableJson $json2) {
                    $this->assertEquals(array_keys($json2->toArray()), [10, 2]);
                    $json2->etc();
                })->etc();
            });
    });

    test('returns data for console 3', function () {
        $games = createGames();

        $this->get($this->apiUrl('officialgameslist', ['c' => $games[2]->system->id], credentials: false))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'Response' => [
                    '3' => 'Three',
                ],
            ]);
    });

    test('returns all data when console not specified', function () {
        $games = createGames();

        $this->get($this->apiUrl('officialgameslist', [], credentials: false))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'Response' => [
                    // grouped by system
                    '1' => 'One',

                    '10' => 'Ten',
                    '2' => 'Two',

                    '3' => 'Three',
                ],
            ])
            ->assertJson(function (AssertableJson $json) {
                $json->has("Response", function (AssertableJson $json2) {
                    $this->assertEquals(array_keys($json2->toArray()), [1, 10, 2, 3]);
                    $json2->etc();
                })->etc();
            });
    });

    test('returns empty array for unknown system', function () {
        createGames();

        $this->get($this->apiUrl('officialgameslist', ['c' => 99], credentials: false))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'Response' => [],
            ])
            // assertExactJson converts the empty object to an array and we want to
            // ensure an empty object was received, so look directly at the response string
            ->assertSee('"Response":{}', escape: false);
    });
});
