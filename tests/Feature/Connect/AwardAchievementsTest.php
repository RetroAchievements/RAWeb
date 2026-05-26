<?php

declare(strict_types=1);

namespace Tests\Feature\Connect;

use App\Enums\Permissions;
use App\Models\Achievement;
use App\Models\Game;
use App\Models\PlayerAchievement;
use App\Models\PlayerGame;
use App\Models\PlayerSession;
use App\Models\System;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\Feature\Concerns\TestsEmulatorUserAgent;
use Tests\Feature\Platform\Concerns\TestsPlayerAchievements;

uses(LazilyRefreshDatabase::class);
uses(TestsConnect::class);
uses(TestsEmulatorUserAgent::class);
uses(TestsPlayerAchievements::class);

class AwardAchievementsTestHelpers
{
    public static function buildValidationHash(string $achievementIdsCsv, User $user, int $hardcore): string
    {
        $data = $achievementIdsCsv . $user->username . $hardcore;

        return md5($data);
    }

    public static function createStandaloneGame(): array
    {
        /** @var System $standalonesSystem */
        $standalonesSystem = System::factory()->create(['id' => System::Standalones]);
        /** @var Game $game */
        $game = Game::factory()->create(['system_id' => $standalonesSystem->id]);

        /** @var User $integrationUser */
        $integrationUser = User::factory()->create(['Permissions' => Permissions::Registered, 'connect_token' => Str::random(16)]);
        /** @var User $delegatedUser */
        $delegatedUser = User::factory()->create(['username' => 'Username', 'Permissions' => Permissions::Registered, 'connect_token' => Str::random(16)]);
        $delegatedUser->points = 0;
        $delegatedUser->points_hardcore = 0;
        $delegatedUser->rich_presence_game_id = $game->id;
        $delegatedUser->save();

        /** @var Achievement $achievement1 */
        $achievement1 = Achievement::factory()->promoted()->create(['id' => 1, 'game_id' => $game->id, 'user_id' => $integrationUser->id]);
        /** @var Achievement $achievement2 */
        $achievement2 = Achievement::factory()->promoted()->create(['id' => 2, 'game_id' => $game->id, 'user_id' => $integrationUser->id]);
        /** @var Achievement $achievement3 */
        $achievement3 = Achievement::factory()->promoted()->create(['id' => 3, 'game_id' => $game->id, 'user_id' => $integrationUser->id]);
        /** @var Achievement $achievement4 */
        $achievement4 = Achievement::factory()->promoted()->create(['id' => 4, 'game_id' => $game->id, 'user_id' => $integrationUser->id]);
        /** @var Achievement $achievement5 */
        $achievement5 = Achievement::factory()->promoted()->create(['id' => 5, 'game_id' => $game->id, 'user_id' => $integrationUser->id]);
        /** @var Achievement $achievement6 */
        $achievement6 = Achievement::factory()->promoted()->create(['id' => 6, 'game_id' => $game->id, 'user_id' => $integrationUser->id]);

        return [
            'integrationUser' => $integrationUser,
            'delegatedUser' => $delegatedUser,
            'game' => $game,
            'achievements' => [
                $achievement1,
                $achievement2,
                $achievement3,
                $achievement4,
                $achievement5,
                $achievement6,
            ],
        ];
    }
}

beforeEach(function () {
    Carbon::setTestNow(Carbon::now()->startOfSecond());

    $this->seedEmulatorUserAgents();
    $this->createConnectUser();
});

describe('normal unlock', function () {
    test('single achievement hardcore', function () {
        $data = AwardAchievementsTestHelpers::createStandaloneGame();
        $game = $data['game'];
        $achievement1 = $data['achievements'][0];
        $delegatedUser = $data['delegatedUser'];
        $integrationUser = $data['integrationUser'];
        $now = Carbon::now();

        // cache the unlocks for the game - verify multiple unlocks captured
        $unlocks = getUserAchievementUnlocksForGame($delegatedUser->username, $game->id);
        $this->assertEquals([], array_keys($unlocks));

        // do the delegated unlocks sync
        $scoreBefore = $delegatedUser->points_hardcore;
        $softcoreScoreBefore = $delegatedUser->points;

        $params = [
            'u' => $integrationUser->username,
            't' => $integrationUser->connect_token,
            'r' => 'awardachievements',
            'k' => $delegatedUser->username,
        ];
        $payload = [
            'a' => $achievement1->id,
            'h' => 1,
            'v' => AwardAchievementsTestHelpers::buildValidationHash(strval($achievement1->id), $delegatedUser, 1),
        ];

        $requestUrl = sprintf('dorequest.php?%s', http_build_query($params));
        $this->post($requestUrl, $payload)
            ->assertStatus(200)
            ->assertExactJson([
                "Success" => true,
                "Score" => $scoreBefore + $achievement1->points,
                "SoftcoreScore" => $softcoreScoreBefore,
                "ExistingIDs" => [],
                "SuccessfulIDs" => [
                    $achievement1->id,
                ],
            ]);
        $delegatedUser->refresh();

        // player session resumed
        $playerSession2 = PlayerSession::where([
            'user_id' => $delegatedUser->id,
            'game_id' => $achievement1->game_id,
        ])->orderByDesc('id')->first();
        $this->assertModelExists($playerSession2);

        // game attached
        $playerGame = PlayerGame::where([
            'user_id' => $delegatedUser->id,
            'game_id' => $achievement1->game_id,
        ])->first();
        $this->assertModelExists($playerGame);
        $this->assertNotNull($playerGame->last_played_at);

        // one achievements unlocked
        $playerAchievement1 = PlayerAchievement::where([
            'user_id' => $delegatedUser->id,
            'achievement_id' => $achievement1->id,
        ])->first();
        $this->assertModelExists($playerAchievement1);
        $this->assertNotNull($playerAchievement1->unlocked_at);
        $this->assertNotNull($playerAchievement1->unlocked_hardcore_at);
        $this->assertEquals($playerAchievement1->player_session_id, $playerSession2->id);

        // player score should have increased
        $user1 = User::whereName($delegatedUser->username)->first();
        $this->assertEquals($scoreBefore + $achievement1->points, $user1->points_hardcore);
        $this->assertEquals($softcoreScoreBefore, $user1->points);

        // make sure the unlock cache was updated
        $unlocks = getUserAchievementUnlocksForGame($delegatedUser->username, $game->id);
        $this->assertEqualsCanonicalizing(
            [
                $achievement1->id,
            ],
            array_keys($unlocks)
        );
        $this->assertEquals($now, $unlocks[$achievement1->id]['DateEarnedHardcore']);
        $this->assertEquals($now, $unlocks[$achievement1->id]['DateEarned']);
    });

    test('single achievement softcore', function () {
        $data = AwardAchievementsTestHelpers::createStandaloneGame();
        $game = $data['game'];
        $achievement1 = $data['achievements'][0];
        $delegatedUser = $data['delegatedUser'];
        $integrationUser = $data['integrationUser'];
        $now = Carbon::now();

        // cache the unlocks for the game - verify multiple unlocks captured
        $unlocks = getUserAchievementUnlocksForGame($delegatedUser->username, $game->id);
        $this->assertEquals([], array_keys($unlocks));

        // do the delegated unlocks sync
        $scoreBefore = $delegatedUser->points_hardcore;
        $softcoreScoreBefore = $delegatedUser->points;

        $params = [
            'u' => $integrationUser->username,
            't' => $integrationUser->connect_token,
            'r' => 'awardachievements',
            'k' => $delegatedUser->username,
        ];
        $payload = [
            'a' => $achievement1->id,
            'h' => 0,
            'v' => AwardAchievementsTestHelpers::buildValidationHash(strval($achievement1->id), $delegatedUser, 0),
        ];

        $requestUrl = sprintf('dorequest.php?%s', http_build_query($params));
        $this->post($requestUrl, $payload)
            ->assertStatus(200)
            ->assertExactJson([
                "Success" => true,
                "Score" => $scoreBefore,
                "SoftcoreScore" => $softcoreScoreBefore + $achievement1->points,
                "ExistingIDs" => [],
                "SuccessfulIDs" => [
                    $achievement1->id,
                ],
            ]);
        $delegatedUser->refresh();

        // player session resumed
        $playerSession2 = PlayerSession::where([
            'user_id' => $delegatedUser->id,
            'game_id' => $achievement1->game_id,
        ])->orderByDesc('id')->first();
        $this->assertModelExists($playerSession2);

        // game attached
        $playerGame = PlayerGame::where([
            'user_id' => $delegatedUser->id,
            'game_id' => $achievement1->game_id,
        ])->first();
        $this->assertModelExists($playerGame);
        $this->assertNotNull($playerGame->last_played_at);

        // one achievements unlocked
        $playerAchievement1 = PlayerAchievement::where([
            'user_id' => $delegatedUser->id,
            'achievement_id' => $achievement1->id,
        ])->first();
        $this->assertModelExists($playerAchievement1);
        $this->assertNotNull($playerAchievement1->unlocked_at);
        $this->assertNull($playerAchievement1->unlocked_hardcore_at);
        $this->assertEquals($playerAchievement1->player_session_id, $playerSession2->id);

        // player score should have increased
        $user1 = User::whereName($delegatedUser->username)->first();
        $this->assertEquals($scoreBefore, $user1->points_hardcore);
        $this->assertEquals($softcoreScoreBefore + $achievement1->points, $user1->points);

        // make sure the unlock cache was updated
        $unlocks = getUserAchievementUnlocksForGame($delegatedUser->username, $game->id);
        $this->assertEqualsCanonicalizing(
            [
                $achievement1->id,
            ],
            array_keys($unlocks)
        );
        $this->assertArrayNotHasKey('DateEarnedHardcore', $unlocks[$achievement1->id]);
        $this->assertEquals($now, $unlocks[$achievement1->id]['DateEarned']);
    });

    test('multiple achievements hardcore', function () {
        $data = AwardAchievementsTestHelpers::createStandaloneGame();
        $game = $data['game'];
        $achievement1 = $data['achievements'][0];
        $achievement2 = $data['achievements'][1];
        $achievement3 = $data['achievements'][2];
        $achievement4 = $data['achievements'][3];
        $delegatedUser = $data['delegatedUser'];
        $integrationUser = $data['integrationUser'];

        $now = Carbon::now();
        $unlock1Date = $now->clone()->subMinutes(65);
        $this->addHardcoreUnlock($delegatedUser, $achievement1, $unlock1Date);

        $playerSession1 = PlayerSession::where([
            'user_id' => $delegatedUser->id,
            'game_id' => $achievement1->game_id,
        ])->orderByDesc('id')->first();
        $this->assertModelExists($playerSession1);

        // cache the unlocks for the game - verify multiple unlocks captured
        $unlocks = getUserAchievementUnlocksForGame($delegatedUser->username, $game->id);
        $this->assertEquals([$achievement1->id], array_keys($unlocks));

        // do the delegated unlocks sync
        $scoreBefore = $delegatedUser->points_hardcore;
        $softcoreScoreBefore = $delegatedUser->points;

        $params = [
            'u' => $integrationUser->username,
            't' => $integrationUser->connect_token,
            'r' => 'awardachievements',
            'k' => $delegatedUser->username,
        ];
        $payload = [
            // Note that #0 is already unlocked, thus it will not be in the "SuccessfulIDs" list.
            'a' => "1,2,3,4",
            'h' => 1,
            'v' => AwardAchievementsTestHelpers::buildValidationHash('1,2,3,4', $delegatedUser, 1),
        ];

        $requestUrl = sprintf('dorequest.php?%s', http_build_query($params));
        $this->post($requestUrl, $payload)
            ->assertStatus(200)
            ->assertExactJson([
                "Success" => true,
                "Score" => $scoreBefore + $achievement2->points + $achievement3->points + $achievement4->points,
                "SoftcoreScore" => $softcoreScoreBefore,
                "ExistingIDs" => [
                    $achievement1->id,
                ],
                "SuccessfulIDs" => [
                    $achievement2->id,
                    $achievement3->id,
                    $achievement4->id,
                ],
            ]);
        $delegatedUser->refresh();

        // player session resumed
        $playerSession2 = PlayerSession::where([
            'user_id' => $delegatedUser->id,
            'game_id' => $achievement2->game_id,
        ])->orderByDesc('id')->first();
        $this->assertModelExists($playerSession2);

        // game attached
        $playerGame = PlayerGame::where([
            'user_id' => $delegatedUser->id,
            'game_id' => $achievement2->game_id,
        ])->first();
        $this->assertModelExists($playerGame);
        $this->assertNotNull($playerGame->last_played_at);

        // three achievements unlocked
        $playerAchievement1 = PlayerAchievement::where([
            'user_id' => $delegatedUser->id,
            'achievement_id' => $achievement2->id,
        ])->first();
        $this->assertModelExists($playerAchievement1);
        $this->assertNotNull($playerAchievement1->unlocked_at);
        $this->assertNotNull($playerAchievement1->unlocked_hardcore_at);
        $this->assertEquals($playerAchievement1->player_session_id, $playerSession2->id);

        $playerAchievement2 = PlayerAchievement::where([
            'user_id' => $delegatedUser->id,
            'achievement_id' => $achievement3->id,
        ])->first();
        $this->assertModelExists($playerAchievement2);
        $this->assertNotNull($playerAchievement2->unlocked_at);
        $this->assertNotNull($playerAchievement2->unlocked_hardcore_at);
        $this->assertEquals($playerAchievement2->player_session_id, $playerSession2->id);

        $playerAchievement3 = PlayerAchievement::where([
            'user_id' => $delegatedUser->id,
            'achievement_id' => $achievement4->id,
        ])->first();
        $this->assertModelExists($playerAchievement3);
        $this->assertNotNull($playerAchievement3->unlocked_at);
        $this->assertNotNull($playerAchievement3->unlocked_hardcore_at);
        $this->assertEquals($playerAchievement3->player_session_id, $playerSession2->id);

        // player score should have increased
        $user1 = User::whereName($delegatedUser->username)->first();
        $this->assertEquals(
            $scoreBefore + $achievement2->points + $achievement3->points + $achievement4->points,
            $user1->points_hardcore
        );
        $this->assertEquals($softcoreScoreBefore, $user1->points);

        // make sure the unlock cache was updated
        $unlocks = getUserAchievementUnlocksForGame($delegatedUser->username, $game->id);
        $this->assertEqualsCanonicalizing(
            [
                $achievement1->id,
                $achievement2->id,
                $achievement3->id,
                $achievement4->id,
            ],
            array_keys($unlocks)
        );
        $this->assertEquals($now, $unlocks[$achievement2->id]['DateEarnedHardcore']);
        $this->assertEquals($now, $unlocks[$achievement2->id]['DateEarned']);
    });
});

describe('validation', function () {
    test('non standalone system', function () {
        $game = Game::factory()->create();
        $achievement1 = Achievement::factory()->promoted()->create(['id' => 1, 'game_id' => $game->id]);
        $delegatedUser = User::factory()->create(['username' => 'Username', 'Permissions' => Permissions::Registered]);

        $scoreBefore = $delegatedUser->points_hardcore;
        $softcoreScoreBefore = $delegatedUser->points;

        $params = [
            'u' => $this->user->username,
            't' => $this->user->connect_token,
            'r' => 'awardachievements',
            'k' => $delegatedUser->username,
        ];
        $payload = [
            'a' => $achievement1->id,
            'h' => 1,
            'v' => AwardAchievementsTestHelpers::buildValidationHash(strval($achievement1->id), $delegatedUser, 1),
        ];

        $requestUrl = sprintf('dorequest.php?%s', http_build_query($params));
        $this->post($requestUrl, $payload)
            ->assertStatus(403)
            ->assertExactJson([
                'Success' => false,
                'Status' => 403,
                'Code' => 'access_denied',
                'Error' => 'Access denied.',
            ]);
        $delegatedUser->refresh();

        // Points shouldn't change.
        $this->assertEquals($scoreBefore, $delegatedUser->points_hardcore);
        $this->assertEquals($softcoreScoreBefore, $delegatedUser->points);

        // A session shouldn't have been created.
        $this->assertDatabaseMissing((new PlayerSession())->getTable(), [
            'user_id' => $delegatedUser->id,
            'game_id' => $game->id,
        ]);
    });

    test('wrong validation hash', function () {
        $data = AwardAchievementsTestHelpers::createStandaloneGame();
        $game = $data['game'];
        $achievement1 = $data['achievements'][0];
        $delegatedUser = $data['delegatedUser'];
        $integrationUser = $data['integrationUser'];

        $scoreBefore = $delegatedUser->points_hardcore;
        $softcoreScoreBefore = $delegatedUser->points;

        $params = [
            'u' => $integrationUser->username,
            't' => $integrationUser->connect_token,
            'r' => 'awardachievements',
            'k' => $delegatedUser->username,
        ];
        $payload = [
            'a' => $achievement1->id,
            'h' => 1,
            'v' => md5('ThisIsNotTheCorrectValidationHash'),
        ];

        $requestUrl = sprintf('dorequest.php?%s', http_build_query($params));
        $this->post($requestUrl, $payload)
            ->assertStatus(403)
            ->assertExactJson([
                "Code" => "access_denied",
                "Success" => false,
                "Error" => "Access denied.",
                "Status" => 403,
            ]);
        $delegatedUser->refresh();

        // Points shouldn't change.
        $this->assertEquals($scoreBefore, $delegatedUser->points_hardcore);
        $this->assertEquals($softcoreScoreBefore, $delegatedUser->points);

        // A session shouldn't have been created.
        $this->assertDatabaseMissing((new PlayerSession())->getTable(), [
            'user_id' => $delegatedUser->id,
            'game_id' => $game->id,
        ]);
    });

    test('integration user is not author', function () {
        $data = AwardAchievementsTestHelpers::createStandaloneGame();
        $game = $data['game'];
        $achievement1 = $data['achievements'][0];
        $delegatedUser = $data['delegatedUser'];

        $scoreBefore = $delegatedUser->points_hardcore;
        $softcoreScoreBefore = $delegatedUser->points;

        $params = [
            'u' => $this->user->username,
            't' => $this->user->connect_token,
            'r' => 'awardachievements',
            'k' => $delegatedUser->username,
        ];
        $payload = [
            'a' => $achievement1->id,
            'h' => 1,
            'v' => AwardAchievementsTestHelpers::buildValidationHash(strval($achievement1->id), $delegatedUser, 1),
        ];

        $requestUrl = sprintf('dorequest.php?%s', http_build_query($params));
        $this->post($requestUrl, $payload)
            ->assertStatus(403)
            ->assertExactJson([
                'Success' => false,
                'Status' => 403,
                'Code' => 'access_denied',
                'Error' => 'Access denied.',
            ]);
        $delegatedUser->refresh();

        // Points shouldn't change.
        $this->assertEquals($scoreBefore, $delegatedUser->points_hardcore);
        $this->assertEquals($softcoreScoreBefore, $delegatedUser->points);

        // A session shouldn't have been created.
        $this->assertDatabaseMissing((new PlayerSession())->getTable(), [
            'user_id' => $delegatedUser->id,
            'game_id' => $game->id,
        ]);
    });

    test('no delegated user', function () {
        $data = AwardAchievementsTestHelpers::createStandaloneGame();
        $game = $data['game'];
        $achievement1 = $data['achievements'][0];

        $params = [
            'u' => $this->user->username,
            't' => $this->user->connect_token,
            'r' => 'awardachievements',
        ];
        $payload = [
            'a' => $achievement1->id,
            'h' => 1,
            'v' => md5("{$achievement1->id}SomeGuy1"),
        ];

        $requestUrl = sprintf('dorequest.php?%s', http_build_query($params));
        $this->post($requestUrl, $payload)
            ->assertStatus(422)
            ->assertExactJson([
                'Success' => false,
                'Status' => 422,
                'Code' => 'missing_parameter',
                'Error' => 'One or more required parameters is missing.',
            ]);
    });

    test('delegated user is unknown', function () {
        $data = AwardAchievementsTestHelpers::createStandaloneGame();
        $game = $data['game'];
        $achievement1 = $data['achievements'][0];

        $params = [
            'u' => $this->user->username,
            't' => $this->user->connect_token,
            'r' => 'awardachievements',
            'k' => 'SomeGuy',
        ];
        $payload = [
            'a' => $achievement1->id,
            'h' => 1,
            'v' => md5("{$achievement1->id}SomeGuy1"),
        ];

        $requestUrl = sprintf('dorequest.php?%s', http_build_query($params));
        $this->post($requestUrl, $payload)
            ->assertStatus(404)
            ->assertExactJson([
                'Success' => false,
                'Error' => 'Unknown target user.',
                'Status' => 404,
                'Code' => 'not_found',
            ]);
    });

    test('must be POSTed', function () {
        $data = AwardAchievementsTestHelpers::createStandaloneGame();
        $game = $data['game'];
        $achievement1 = $data['achievements'][0];
        $delegatedUser = $data['delegatedUser'];

        $scoreBefore = $delegatedUser->points_hardcore;
        $softcoreScoreBefore = $delegatedUser->points;

        $params = [
            'u' => $this->user->username,
            't' => $this->user->connect_token,
            'r' => 'awardachievements',
            'k' => $delegatedUser->username,
            'a' => $achievement1->id,
            'h' => 1,
            'v' => AwardAchievementsTestHelpers::buildValidationHash(strval($achievement1->id), $delegatedUser, 1),
        ];

        $requestUrl = sprintf('dorequest.php?%s', http_build_query($params));
        $this->get($requestUrl)
            ->assertStatus(405)
            ->assertJson([
                "Success" => false,
                "Error" => "Access denied.",
                "Status" => 405,
            ]);
    });

    test('unpromoted achievement is not awarded', function () {
        $data = AwardAchievementsTestHelpers::createStandaloneGame();
        $game = $data['game'];
        $achievement1 = $data['achievements'][0];
        $delegatedUser = $data['delegatedUser'];
        $integrationUser = $data['integrationUser'];

        $achievement1->is_promoted = false;
        $achievement1->save();

        $scoreBefore = $delegatedUser->points_hardcore;
        $softcoreScoreBefore = $delegatedUser->points;

        $params = [
            'u' => $integrationUser->username,
            't' => $integrationUser->connect_token,
            'r' => 'awardachievements',
            'k' => $delegatedUser->username,
        ];
        $payload = [
            'a' => $achievement1->id,
            'h' => 1,
            'v' => AwardAchievementsTestHelpers::buildValidationHash(strval($achievement1->id), $delegatedUser, 1),
        ];

        $requestUrl = sprintf('dorequest.php?%s', http_build_query($params));
        $this->post($requestUrl, $payload)
            ->assertStatus(200)
            ->assertExactJson([
                "Success" => true,
                "Score" => $scoreBefore,
                "SoftcoreScore" => $softcoreScoreBefore,
                "ExistingIDs" => [],
                "SuccessfulIDs" => [],
            ]);
        $delegatedUser->refresh();

        // Points shouldn't change.
        $this->assertEquals($scoreBefore, $delegatedUser->points_hardcore);
        $this->assertEquals($softcoreScoreBefore, $delegatedUser->points);

        // A session shouldn't have been created.
        $this->assertDatabaseMissing((new PlayerSession())->getTable(), [
            'user_id' => $delegatedUser->id,
            'game_id' => $game->id,
        ]);
    });
});
