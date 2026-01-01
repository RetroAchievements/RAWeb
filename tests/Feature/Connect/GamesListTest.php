<?php

declare(strict_types=1);

namespace Tests\Feature\Connect;

use App\Models\Game;
use App\Models\System;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class GamesListTest extends TestCase
{
    use BootstrapsConnect;
    use RefreshDatabase;

    public function testGamesList(): void
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

        // all games for console 1
        $this->get($this->apiUrl('gameslist', ['c' => $system1->id]))
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

        // all games for console 2
        $this->get($this->apiUrl('gameslist', ['c' => $system2->id]))
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

        // all games for console 3
        $this->get($this->apiUrl('gameslist', ['c' => $system3->id]))
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

        // games with published achievements for console 1 (sorted by name)
        $this->get($this->apiUrl('officialgameslist', ['c' => $system1->id]))
            ->assertExactJson([
                'Success' => true,
                'Response' => [
                    '1' => 'One',
                ],
            ]);

        // games with published achievements for console 2
        $this->get($this->apiUrl('officialgameslist', ['c' => $system2->id]))
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

        // games with published achievements for console 3
        $this->get($this->apiUrl('officialgameslist', ['c' => $system3->id]))
            ->assertExactJson([
                'Success' => true,
                'Response' => [
                    '3' => 'Three',
                ],
            ]);
    }
}
