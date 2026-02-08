<?php

declare(strict_types=1);

namespace Tests\Feature\Connect;

use App\Models\Game;
use App\Models\System;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;

uses(LazilyRefreshDatabase::class);
uses(TestsConnect::class);

class GamesListTestHelpers
{
    public static function createGames(): array
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
}

describe('get', function () {
    test('returns data for console 1', function () {
        $games = GamesListTestHelpers::createGames();

        $this->get($this->apiUrl('gameslist', ['c' => $games[0]->system->id], credentials: false))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'Response' => [
                    '5' => 'Five',
                    '1' => 'One',
                    '7' => 'Seven',
                ],
            ])
            // assertExactJson doesn't enforce order. this ugly code pulls the array keys from
            // the Response subobject and validates the order of them.
            ->assertJson(function (AssertableJson $json) {
                $json->has("Response", function (AssertableJson $json2) {
                    $this->assertEquals(array_keys($json2->toArray()), [5, 1, 7]);
                    $json2->etc(); // prevent "Unexpected properties" error for not directly interacting with "Response"
                })->etc(); // prevent "Unexpected properties" error for not directly interacting with "Success"
            });
    });

    test('returns data for console 2', function () {
        $games = GamesListTestHelpers::createGames();

        $this->get($this->apiUrl('gameslist', ['c' => $games[1]->system->id], credentials: false))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'Response' => [
                    '8' => 'Eight',
                    '4' => 'Four',
                    '10' => 'Ten',
                    '2' => 'Two',
                ],
            ])
            ->assertJson(function (AssertableJson $json) {
                $json->has("Response", function (AssertableJson $json2) {
                    $this->assertEquals(array_keys($json2->toArray()), [8, 4, 10, 2]);
                    $json2->etc();
                })->etc();
            });
    });

    test('returns data for console 3', function () {
        $games = GamesListTestHelpers::createGames();

        $this->get($this->apiUrl('gameslist', ['c' => $games[2]->system->id], credentials: false))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'Response' => [
                    '9' => 'Nine',
                    '6' => 'Six',
                    '3' => 'Three',
                ],
            ])
            ->assertJson(function (AssertableJson $json) {
                $json->has("Response", function (AssertableJson $json2) {
                    $this->assertEquals(array_keys($json2->toArray()), [9, 6, 3]);
                    $json2->etc();
                })->etc();
            });
    });

    test('returns empty dictionary for console with no games', function () {
        /** @var System $system1 */
        $system1 = System::factory()->create();

        $this->get($this->apiUrl('gameslist', ['c' => $system1->id], credentials: false))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'Response' => [],
            ])
            // assertExactJson converts the empty object to an array and we want to
            // ensure an empty object was received, so look directly at the response string
            ->assertSee('"Response":{}', escape: false);
    });

    test('returns data sorted by sort_title', function () {
        /** @var System $system1 */
        $system1 = System::factory()->create();

        // intentionally created out of order (both by title and sort_title) to ensure some form of sort is applied
        /** @var Game $game2 */
        $game2 = Game::factory()->create(['title' => 'Game 2', 'system_id' => $system1->id]);
        /** @var Game $game1 */
        $game1 = Game::factory()->create(['title' => 'Game', 'system_id' => $system1->id]);
        /** @var Game $game4 */
        $game4 = Game::factory()->create(['title' => 'Game IV: Revenge', 'system_id' => $system1->id]);
        /** @var Game $game3 */
        $game3 = Game::factory()->create(['title' => 'The Game III', 'system_id' => $system1->id]);

        $this->get($this->apiUrl('gameslist', ['c' => $system1->id], credentials: false))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'Response' => [
                    '2' => 'Game', // sort_title = 'game'
                    '1' => 'Game 2', // sort_title = 'game 0002'
                    '4' => 'The Game III', // sort_title = 'game 0003'
                    '3' => 'Game IV: Revenge', // sort_title = 'game 0004: revenge'
                ],
            ])
            // assertExactJson doesn't enforce order. this ugly code pulls the array keys from
            // the Response subobject and validates the order of them.
            ->assertJson(function (AssertableJson $json) {
                $json->has("Response", function (AssertableJson $json2) {
                    $this->assertEquals(array_keys($json2->toArray()), [2, 1, 4, 3]);
                    $json2->etc();
                })->etc();
            });
    });
});

describe('error', function () {
    test('requires c parameter', function () {
        $this->get($this->apiUrl('gameslist', [], credentials: false))
            ->assertStatus(422)
            ->assertExactJson([
                'Success' => false,
                'Status' => 422,
                'Code' => 'missing_parameter',
                'Error' => 'One or more required parameters is missing.',
            ]);
    });

    test('unknown system', function () {
        GamesListTestHelpers::createGames();

        $this->get($this->apiUrl('gameslist', ['c' => 99], credentials: false))
            ->assertStatus(404)
            ->assertExactJson([
                'Success' => false,
                'Status' => 404,
                'Code' => 'not_found',
                'Error' => 'Unknown system.',
            ]);
    });

    test('system 0', function () {
        GamesListTestHelpers::createGames();

        // at one point, this returned all games in the system, but that list is
        // unmanageable now. require the caller specify a valid system.
        $this->get($this->apiUrl('gameslist', ['c' => 0], credentials: false))
            ->assertStatus(404)
            ->assertExactJson([
                'Success' => false,
                'Status' => 404,
                'Code' => 'not_found',
                'Error' => 'Unknown system.',
            ]);
    });
});
