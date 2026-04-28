<?php

declare(strict_types=1);

namespace Tests\Feature\Connect;

use App\Enums\GameHashCompatibility;
use App\Models\Game;
use App\Models\GameHash;
use App\Models\Leaderboard;
use App\Models\System;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;

uses(LazilyRefreshDatabase::class);
uses(TestsConnect::class);

class SystemGamesTestHelpers
{
    public static function createGames(): array
    {
        /** @var System $system1 */
        $system1 = System::factory()->create();
        /** @var System $system2 */
        $system2 = System::factory()->create();

        // games 1,2,3 have only published achievements
        // games 4,5,6 have only unpublished achievements
        // games 7,8,9 have no achievements
        // game 10 has published and unpublished achievements
        /** @var Game $game1 */
        $game1 = Game::factory()->create(['title' => 'One', 'system_id' => $system1->id, 'sort_title' => 'one',
            'image_icon_asset_path' => '/Images/000001.png',
            'achievements_published' => 3, 'points_total' => 15]);
        /** @var Game $game2 */
        $game2 = Game::factory()->create(['title' => 'Two', 'system_id' => $system2->id, 'sort_title' => 'two',
            'image_icon_asset_path' => '/Images/000002.png',
            'achievements_published' => 7, 'points_total' => 50]);
        /** @var Game $game3 */
        $game3 = Game::factory()->create(['title' => 'Three', 'system_id' => $system1->id, 'sort_title' => 'three',
            'image_icon_asset_path' => '/Images/000003.png',
            'achievements_published' => 11, 'points_total' => 75]);
        /** @var Game $game4 */
        $game4 = Game::factory()->create(['title' => 'Four', 'system_id' => $system2->id, 'sort_title' => 'four',
            'image_icon_asset_path' => '/Images/000004.png',
            'achievements_unpublished' => 5]);
        /** @var Game $game5 */
        $game5 = Game::factory()->create(['title' => 'Five', 'system_id' => $system1->id, 'sort_title' => 'five',
            'image_icon_asset_path' => '/Images/000005.png',
            'achievements_unpublished' => 9]);
        /** @var Game $game6 */
        $game6 = Game::factory()->create(['title' => 'Six', 'system_id' => $system2->id, 'sort_title' => 'six',
            'image_icon_asset_path' => '/Images/000006.png',
            'achievements_unpublished' => 1]);
        /** @var Game $game7 */
        $game7 = Game::factory()->create(['title' => 'Seven', 'system_id' => $system1->id, 'sort_title' => 'seven',
            'image_icon_asset_path' => '/Images/000007.png']);
        /** @var Game $game8 */
        $game8 = Game::factory()->create(['title' => 'Eight', 'system_id' => $system2->id, 'sort_title' => 'eight',
            'image_icon_asset_path' => '/Images/000008.png']);
        /** @var Game $game9 */
        $game9 = Game::factory()->create(['title' => 'Nine', 'system_id' => $system1->id, 'sort_title' => 'nine',
            'image_icon_asset_path' => '/Images/000009.png']);
        /** @var Game $game10 */
        $game10 = Game::factory()->create(['title' => 'Ten', 'system_id' => $system2->id, 'sort_title' => 'ten',
            'image_icon_asset_path' => '/Images/000010.png',
            'achievements_published' => 2, 'points_total' => 100,
            'achievements_unpublished' => 1]);

        // games 1,4,7,10 have one compatible hash
        // games 2,5,8 have multiple compatible hashes
        // game 3 has one compatible and one incompatible hash
        // game 6 has one compatible and two incompatible hashes
        // game 9 has no hashes
        GameHash::factory()->create(['game_id' => $game1->id, 'compatibility' => GameHashCompatibility::Compatible]);
        GameHash::factory()->create(['game_id' => $game2->id, 'compatibility' => GameHashCompatibility::Compatible]);
        GameHash::factory()->create(['game_id' => $game2->id, 'compatibility' => GameHashCompatibility::Compatible]);
        GameHash::factory()->create(['game_id' => $game3->id, 'compatibility' => GameHashCompatibility::Compatible]);
        GameHash::factory()->create(['game_id' => $game3->id, 'compatibility' => GameHashCompatibility::Incompatible]);
        GameHash::factory()->create(['game_id' => $game4->id, 'compatibility' => GameHashCompatibility::Compatible]);
        GameHash::factory()->create(['game_id' => $game5->id, 'compatibility' => GameHashCompatibility::Compatible]);
        GameHash::factory()->create(['game_id' => $game5->id, 'compatibility' => GameHashCompatibility::Compatible]);
        GameHash::factory()->create(['game_id' => $game5->id, 'compatibility' => GameHashCompatibility::Compatible]);
        GameHash::factory()->create(['game_id' => $game6->id, 'compatibility' => GameHashCompatibility::Compatible]);
        GameHash::factory()->create(['game_id' => $game6->id, 'compatibility' => GameHashCompatibility::Untested]);
        GameHash::factory()->create(['game_id' => $game6->id, 'compatibility' => GameHashCompatibility::PatchRequired]);
        GameHash::factory()->create(['game_id' => $game7->id, 'compatibility' => GameHashCompatibility::Compatible]);
        GameHash::factory()->create(['game_id' => $game8->id, 'compatibility' => GameHashCompatibility::Compatible]);
        GameHash::factory()->create(['game_id' => $game8->id, 'compatibility' => GameHashCompatibility::Compatible]);
        GameHash::factory()->create(['game_id' => $game10->id, 'compatibility' => GameHashCompatibility::Compatible]);

        // game4 has two leaderboards
        // game7 have one leaderboard
        Leaderboard::factory()->create(['game_id' => $game4->id]);
        Leaderboard::factory()->create(['game_id' => $game7->id]);
        Leaderboard::factory()->create(['game_id' => $game7->id]);

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

    public static function wrapGame(Game $game): array
    {
        $result = [
            'ID' => $game->id,
            'Title' => $game->title,
            'ImageIcon' => $game->image_icon_asset_path,
            'ImageUrl' => $game->badge_url,
            'NumAchievements' => $game->achievements_published ?? 0,
            'NumLeaderboards' => Leaderboard::where('game_id', $game->id)->count(),
            'Points' => $game->points_total ?? 0,
            'SupportedHashes' => [],
        ];

        $unsupportedHashes = [];
        foreach ($game->hashes as $hash) {
            if ($hash->compatibility === GameHashCompatibility::Compatible) {
                $result['SupportedHashes'][] = $hash->md5;
            } else {
                $unsupportedHashes[] = $hash->md5;
            }
        }

        if (!empty($unsupportedHashes)) {
            $result['UnsupportedHashes'] = $unsupportedHashes;
        }

        return $result;
    }
}

describe('get', function () {
    test('returns data for system 1', function () {
        $games = SystemGamesTestHelpers::createGames();

        $this->get($this->apiUrl('systemgames', ['s' => $games[0]->system->id], credentials: false))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'Response' => [
                    SystemGamesTestHelpers::wrapGame($games[4]), // Five
                    SystemGamesTestHelpers::wrapGame($games[8]), // Nine
                    SystemGamesTestHelpers::wrapGame($games[0]), // One
                    SystemGamesTestHelpers::wrapGame($games[6]), // Seven
                    SystemGamesTestHelpers::wrapGame($games[2]), // Three
                ],
            ])
            // assertExactJson doesn't enforce order. this ugly code pulls the array keys from
            // the Response subobject and validates the order of them.
            ->assertJson(function (AssertableJson $json) use ($games) {
                $json->has("Response", function (AssertableJson $json2) use ($games) {
                    $this->assertEquals(array_column($json2->toArray(), 'ID'), [
                        $games[4]->id,
                        $games[8]->id,
                        $games[0]->id,
                        $games[6]->id,
                        $games[2]->id,
                    ]);
                    $json2->etc(); // prevent "Unexpected properties" error for not directly interacting with "Response"
                })->etc(); // prevent "Unexpected properties" error for not directly interacting with "Success"
            });
    });

    test('returns data for system 2', function () {
        $games = SystemGamesTestHelpers::createGames();

        $this->get($this->apiUrl('systemgames', ['s' => $games[1]->system->id], credentials: false))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'Response' => [
                    SystemGamesTestHelpers::wrapGame($games[7]), // Eight
                    SystemGamesTestHelpers::wrapGame($games[3]), // Four
                    SystemGamesTestHelpers::wrapGame($games[5]), // Six
                    SystemGamesTestHelpers::wrapGame($games[9]), // Ten
                    SystemGamesTestHelpers::wrapGame($games[1]), // Two
                ],
            ])
            // assertExactJson doesn't enforce order. this ugly code pulls the array keys from
            // the Response subobject and validates the order of them.
            ->assertJson(function (AssertableJson $json) use ($games) {
                $json->has("Response", function (AssertableJson $json2) use ($games) {
                    $this->assertEquals(array_column($json2->toArray(), 'ID'), [
                        $games[7]->id,
                        $games[3]->id,
                        $games[5]->id,
                        $games[9]->id,
                        $games[1]->id,
                    ]);
                    $json2->etc(); // prevent "Unexpected properties" error for not directly interacting with "Response"
                })->etc(); // prevent "Unexpected properties" error for not directly interacting with "Success"
            });
    });

    test('returns data for system with no games', function () {
        /** @var System $system1 */
        $system1 = System::factory()->create();

        $this->get($this->apiUrl('systemgames', ['s' => $system1->id], credentials: false))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'Response' => [
                ],
            ]);
    });
});

describe('error', function () {
    test('requires s parameter', function () {
        $this->get($this->apiUrl('systemgames', [], credentials: false))
            ->assertStatus(422)
            ->assertExactJson([
                'Success' => false,
                'Status' => 422,
                'Code' => 'missing_parameter',
                'Error' => 'One or more required parameters is missing.',
            ]);
    });

    test('unknown system', function () {
        SystemGamesTestHelpers::createGames();

        $this->get($this->apiUrl('systemgames', ['s' => 99], credentials: false))
            ->assertStatus(404)
            ->assertExactJson([
                'Success' => false,
                'Status' => 404,
                'Code' => 'not_found',
                'Error' => 'Unknown system.',
            ]);
    });

    test('system 0', function () {
        SystemGamesTestHelpers::createGames();

        $this->get($this->apiUrl('systemgames', ['s' => 0], credentials: false))
            ->assertStatus(404)
            ->assertExactJson([
                'Success' => false,
                'Status' => 404,
                'Code' => 'not_found',
                'Error' => 'Unknown system.',
            ]);
    });
});
