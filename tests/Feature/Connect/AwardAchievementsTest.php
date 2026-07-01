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
use App\Platform\Actions\AssociateAchievementSetToGameAction;
use App\Platform\Enums\AchievementSetType;
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

    public static function createSubset(Game $mainGame, User $author, int $firstId = 18, string $title = 'Bonus'): array
    {
        $subsetGame = Game::factory()->create(['system_id' => $mainGame->system->id, 'parent_game_id' => $mainGame->id]);
        $subsetAchievement1 = Achievement::factory()->promoted()->create(['id' => $firstId, 'game_id' => $subsetGame->id, 'user_id' => $author->id]);
        $subsetAchievement2 = Achievement::factory()->promoted()->create(['id' => $firstId + 1, 'game_id' => $subsetGame->id, 'user_id' => $author->id]);

        (new AssociateAchievementSetToGameAction())->execute(
            $mainGame, $subsetGame, AchievementSetType::Bonus, $title,
        );

        return [
            'game' => $subsetGame,
            'achievements' => [
                $subsetAchievement1,
                $subsetAchievement2,
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
        $casualScoreBefore = $delegatedUser->points;

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
                "SoftcoreScore" => $casualScoreBefore,
                "ExistingIDs" => [],
                "SuccessfulIDs" => [
                    $achievement1->id,
                ],
                "AchievementsRemaining" => 5,
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
        $this->assertEquals($casualScoreBefore, $user1->points);

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
        $casualScoreBefore = $delegatedUser->points;

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
                "SoftcoreScore" => $casualScoreBefore + $achievement1->points,
                "ExistingIDs" => [],
                "SuccessfulIDs" => [
                    $achievement1->id,
                ],
                "AchievementsRemaining" => 5,
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
        $this->assertEquals($casualScoreBefore + $achievement1->points, $user1->points);

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

    test('single achievement previously unlocked in hardcore', function () {
        $data = AwardAchievementsTestHelpers::createStandaloneGame();
        $game = $data['game'];
        $achievement1 = $data['achievements'][0];
        $delegatedUser = $data['delegatedUser'];
        $integrationUser = $data['integrationUser'];
        $now = Carbon::now();

        $unlock1Date = $now->clone()->subMinutes(65);
        $this->addHardcoreUnlock($delegatedUser, $achievement1, $unlock1Date);

        // do the delegated unlocks sync
        $scoreBefore = $delegatedUser->points_hardcore;
        $casualScoreBefore = $delegatedUser->points;

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
                "SoftcoreScore" => $casualScoreBefore,
                "ExistingIDs" => [
                    $achievement1->id,
                ],
                "SuccessfulIDs" => [],
                "AchievementsRemaining" => 5,
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
        $this->assertEquals($now, $playerGame->last_played_at);

        // achievement unlock dates not modified
        $playerAchievement1 = PlayerAchievement::where([
            'user_id' => $delegatedUser->id,
            'achievement_id' => $achievement1->id,
        ])->first();
        $this->assertModelExists($playerAchievement1);
        $this->assertEquals($unlock1Date, $playerAchievement1->unlocked_at);
        $this->assertEquals($unlock1Date, $playerAchievement1->unlocked_hardcore_at);

        // player score should not have increased
        $user1 = User::whereName($delegatedUser->username)->first();
        $this->assertEquals($scoreBefore, $user1->points_hardcore);
        $this->assertEquals($casualScoreBefore, $user1->points);
    });

    test('single achievement upgraded to hardcore', function () {
        $data = AwardAchievementsTestHelpers::createStandaloneGame();
        $game = $data['game'];
        $achievement1 = $data['achievements'][0];
        $delegatedUser = $data['delegatedUser'];
        $integrationUser = $data['integrationUser'];
        $now = Carbon::now();

        $unlock1Date = $now->clone()->subMinutes(65);
        $this->addCasualUnlock($delegatedUser, $achievement1, $unlock1Date);

        // do the delegated unlocks sync
        $scoreBefore = $delegatedUser->points_hardcore;
        $casualScoreBefore = $delegatedUser->points;

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
                "SoftcoreScore" => $casualScoreBefore - $achievement1->points,
                "ExistingIDs" => [],
                "SuccessfulIDs" => [
                    $achievement1->id,
                ],
                "AchievementsRemaining" => 5,
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
        $this->assertEquals($now, $playerGame->last_played_at);

        // hardcore unlock date set, softcore unlock date not modified
        $playerAchievement1 = PlayerAchievement::where([
            'user_id' => $delegatedUser->id,
            'achievement_id' => $achievement1->id,
        ])->first();
        $this->assertModelExists($playerAchievement1);
        $this->assertEquals($unlock1Date, $playerAchievement1->unlocked_at);
        $this->assertEquals($now, $playerAchievement1->unlocked_hardcore_at);

        // player score should not have increased
        $user1 = User::whereName($delegatedUser->username)->first();
        $this->assertEquals($scoreBefore + $achievement1->points, $user1->points_hardcore);
        $this->assertEquals($casualScoreBefore - $achievement1->points, $user1->points);
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
        $casualScoreBefore = $delegatedUser->points;

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
                "SoftcoreScore" => $casualScoreBefore,
                "ExistingIDs" => [
                    $achievement1->id,
                ],
                "SuccessfulIDs" => [
                    $achievement2->id,
                    $achievement3->id,
                    $achievement4->id,
                ],
                "AchievementsRemaining" => 2,
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
        $this->assertEquals($casualScoreBefore, $user1->points);

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

    test('multiple subset achievements hardcore', function () {
        $data = AwardAchievementsTestHelpers::createStandaloneGame();
        $game = $data['game'];
        $delegatedUser = $data['delegatedUser'];
        $integrationUser = $data['integrationUser'];

        $subsetData = AwardAchievementsTestHelpers::createSubset($game, $integrationUser);
        $subsetGame = $subsetData['game'];
        $subsetAchievement1 = $subsetData['achievements'][0];
        $subsetAchievement2 = $subsetData['achievements'][1];

        // do the delegated unlocks sync
        $scoreBefore = $delegatedUser->points_hardcore;
        $casualScoreBefore = $delegatedUser->points;

        $params = [
            'u' => $integrationUser->username,
            't' => $integrationUser->connect_token,
            'r' => 'awardachievements',
            'k' => $delegatedUser->username,
        ];
        $payload = [
            'a' => '18,19',
            'h' => 1,
            'v' => AwardAchievementsTestHelpers::buildValidationHash('18,19', $delegatedUser, 1),
        ];

        $requestUrl = sprintf('dorequest.php?%s', http_build_query($params));
        $this->post($requestUrl, $payload)
            ->assertStatus(200)
            ->assertExactJson([
                "Success" => true,
                "Score" => $scoreBefore + $subsetAchievement1->points + $subsetAchievement2->points,
                "SoftcoreScore" => $casualScoreBefore,
                "ExistingIDs" => [],
                "SuccessfulIDs" => [
                    $subsetAchievement1->id,
                    $subsetAchievement2->id,
                ],
                "AchievementsRemaining" => 0, // subset count
            ]);
        $delegatedUser->refresh();

        // two achievements unlocked
        $playerAchievement1 = PlayerAchievement::where([
            'user_id' => $delegatedUser->id,
            'achievement_id' => $subsetAchievement1->id,
        ])->first();
        $this->assertModelExists($playerAchievement1);
        $this->assertNotNull($playerAchievement1->unlocked_at);
        $this->assertNotNull($playerAchievement1->unlocked_hardcore_at);

        // one subset achievement unlocked
        $playerAchievement2 = PlayerAchievement::where([
            'user_id' => $delegatedUser->id,
            'achievement_id' => $subsetAchievement2->id,
        ])->first();
        $this->assertModelExists($playerAchievement2);
        $this->assertNotNull($playerAchievement2->unlocked_at);
        $this->assertNotNull($playerAchievement2->unlocked_hardcore_at);

        // player score should have increased
        $user1 = User::whereName($delegatedUser->username)->first();
        $this->assertEquals(
            $scoreBefore + $subsetAchievement1->points + $subsetAchievement2->points,
            $user1->points_hardcore
        );
        $this->assertEquals($casualScoreBefore, $user1->points);
    });

    test('base and subset achievements hardcore', function () {
        $data = AwardAchievementsTestHelpers::createStandaloneGame();
        $game = $data['game'];
        $achievement1 = $data['achievements'][0];
        $achievement2 = $data['achievements'][1];
        $delegatedUser = $data['delegatedUser'];
        $integrationUser = $data['integrationUser'];

        $subsetData = AwardAchievementsTestHelpers::createSubset($game, $integrationUser);
        $subsetGame = $subsetData['game'];
        $subsetAchievement1 = $subsetData['achievements'][0];

        $now = Carbon::now();
        $unlock1Date = $now->clone()->subMinutes(65);
        $this->addHardcoreUnlock($delegatedUser, $achievement1, $unlock1Date);

        $playerSession1 = PlayerSession::where([
            'user_id' => $delegatedUser->id,
            'game_id' => $achievement1->game_id,
        ])->orderByDesc('id')->first();
        $this->assertModelExists($playerSession1);

        // do the delegated unlocks sync
        $scoreBefore = $delegatedUser->points_hardcore;
        $casualScoreBefore = $delegatedUser->points;

        $params = [
            'u' => $integrationUser->username,
            't' => $integrationUser->connect_token,
            'r' => 'awardachievements',
            'k' => $delegatedUser->username,
        ];
        $payload = [
            // Note that 1 is already unlocked, thus it will not be in the "SuccessfulIDs" list.
            'a' => '1,2,18',
            'h' => 1,
            'v' => AwardAchievementsTestHelpers::buildValidationHash('1,2,18', $delegatedUser, 1),
        ];

        $requestUrl = sprintf('dorequest.php?%s', http_build_query($params));
        $this->post($requestUrl, $payload)
            ->assertStatus(200)
            ->assertExactJson([
                "Success" => true,
                "Score" => $scoreBefore + $achievement2->points + $subsetAchievement1->points,
                "SoftcoreScore" => $casualScoreBefore,
                "ExistingIDs" => [
                    $achievement1->id,
                ],
                "SuccessfulIDs" => [
                    $achievement2->id,
                    $subsetAchievement1->id,
                ],
                "AchievementsRemaining" => 4, // from base set only
            ]);
        $delegatedUser->refresh();

        // player session resumed
        $playerSession2 = PlayerSession::where([
            'user_id' => $delegatedUser->id,
            'game_id' => $achievement2->game_id,
        ])->orderByDesc('id')->first();
        $this->assertModelExists($playerSession2);

        // player session resumed
        $playerSession3 = PlayerSession::where([
            'user_id' => $delegatedUser->id,
            'game_id' => $subsetAchievement1->game_id,
        ])->orderByDesc('id')->first();
        $this->assertModelExists($playerSession3);

        // game attached
        $playerGame = PlayerGame::where([
            'user_id' => $delegatedUser->id,
            'game_id' => $achievement2->game_id,
        ])->first();
        $this->assertModelExists($playerGame);
        $this->assertNotNull($playerGame->last_played_at);

        // one base set achievements unlocked
        $playerAchievement1 = PlayerAchievement::where([
            'user_id' => $delegatedUser->id,
            'achievement_id' => $achievement2->id,
        ])->first();
        $this->assertModelExists($playerAchievement1);
        $this->assertNotNull($playerAchievement1->unlocked_at);
        $this->assertNotNull($playerAchievement1->unlocked_hardcore_at);
        $this->assertEquals($playerAchievement1->player_session_id, $playerSession2->id);

        // one subset achievement unlocked
        $playerAchievement2 = PlayerAchievement::where([
            'user_id' => $delegatedUser->id,
            'achievement_id' => $subsetAchievement1->id,
        ])->first();
        $this->assertModelExists($playerAchievement2);
        $this->assertNotNull($playerAchievement2->unlocked_at);
        $this->assertNotNull($playerAchievement2->unlocked_hardcore_at);
        $this->assertEquals($playerAchievement2->player_session_id, $playerSession3->id);

        // player score should have increased
        $user1 = User::whereName($delegatedUser->username)->first();
        $this->assertEquals(
            $scoreBefore + $achievement2->points + $subsetAchievement1->points,
            $user1->points_hardcore
        );
        $this->assertEquals($casualScoreBefore, $user1->points);
    });

    test('achievements from two subsets of same game hardcore', function () {
        $data = AwardAchievementsTestHelpers::createStandaloneGame();
        $game = $data['game'];
        $achievement1 = $data['achievements'][0];
        $achievement2 = $data['achievements'][1];
        $delegatedUser = $data['delegatedUser'];
        $integrationUser = $data['integrationUser'];

        $subset1Data = AwardAchievementsTestHelpers::createSubset($game, $integrationUser);
        $subset1Game = $subset1Data['game'];
        $subset1Achievement1 = $subset1Data['achievements'][0];

        $subset2Data = AwardAchievementsTestHelpers::createSubset($game, $integrationUser, firstId: 24, title: 'Bonus2');
        $subset2Game = $subset2Data['game'];
        $subset2Achievement1 = $subset2Data['achievements'][0];

        $now = Carbon::now();
        $unlock1Date = $now->clone()->subMinutes(65);
        $this->addHardcoreUnlock($delegatedUser, $achievement1, $unlock1Date);

        $playerSession1 = PlayerSession::where([
            'user_id' => $delegatedUser->id,
            'game_id' => $achievement1->game_id,
        ])->orderByDesc('id')->first();
        $this->assertModelExists($playerSession1);

        // do the delegated unlocks sync
        $scoreBefore = $delegatedUser->points_hardcore;
        $casualScoreBefore = $delegatedUser->points;

        $params = [
            'u' => $integrationUser->username,
            't' => $integrationUser->connect_token,
            'r' => 'awardachievements',
            'k' => $delegatedUser->username,
        ];
        $payload = [
            // Note that 1 is already unlocked, thus it will not be in the "SuccessfulIDs" list.
            'a' => '18,24',
            'h' => 1,
            'v' => AwardAchievementsTestHelpers::buildValidationHash('18,24', $delegatedUser, 1),
        ];

        $requestUrl = sprintf('dorequest.php?%s', http_build_query($params));
        $this->post($requestUrl, $payload)
            ->assertStatus(200)
            ->assertExactJson([
                "Success" => true,
                "Score" => $scoreBefore + $subset1Achievement1->points + $subset2Achievement1->points,
                "SoftcoreScore" => $casualScoreBefore,
                "ExistingIDs" => [],
                "SuccessfulIDs" => [
                    $subset1Achievement1->id,
                    $subset2Achievement1->id,
                ],
                "AchievementsRemaining" => 5, // from base set only
            ]);
        $delegatedUser->refresh();

        // first subset achievement unlocked
        $playerAchievement1 = PlayerAchievement::where([
            'user_id' => $delegatedUser->id,
            'achievement_id' => $subset1Achievement1->id,
        ])->first();
        $this->assertModelExists($playerAchievement1);
        $this->assertNotNull($playerAchievement1->unlocked_at);
        $this->assertNotNull($playerAchievement1->unlocked_hardcore_at);

        // second subset achievement unlocked
        $playerAchievement2 = PlayerAchievement::where([
            'user_id' => $delegatedUser->id,
            'achievement_id' => $subset2Achievement1->id,
        ])->first();
        $this->assertModelExists($playerAchievement2);
        $this->assertNotNull($playerAchievement2->unlocked_at);
        $this->assertNotNull($playerAchievement2->unlocked_hardcore_at);

        // player score should have increased
        $user1 = User::whereName($delegatedUser->username)->first();
        $this->assertEquals(
            $scoreBefore + $subset1Achievement1->points + $subset2Achievement1->points,
            $user1->points_hardcore
        );
        $this->assertEquals($casualScoreBefore, $user1->points);
    });

    test('multiple achievements from distinct games', function () {
        $data = AwardAchievementsTestHelpers::createStandaloneGame();
        $game = $data['game'];
        $achievement1 = $data['achievements'][0];
        $achievement2 = $data['achievements'][1];
        $achievement3 = $data['achievements'][2];
        $achievement4 = $data['achievements'][3];
        $delegatedUser = $data['delegatedUser'];
        $integrationUser = $data['integrationUser'];
        $now = Carbon::now();

        $game2 = Game::factory()->create(['system_id' => $game->system_id]);
        $achievement3->game_id = $game2->id;
        $achievement3->save();

        // do the delegated unlocks sync
        $scoreBefore = $delegatedUser->points_hardcore;
        $casualScoreBefore = $delegatedUser->points;

        $params = [
            'u' => $integrationUser->username,
            't' => $integrationUser->connect_token,
            'r' => 'awardachievements',
            'k' => $delegatedUser->username,
        ];
        $payload = [
            // Achievement 3 is from a different base game than 1, 2, and 4, but has the same
            // author. We allow this for things like Final Fantasy XI, which has a separate base
            // game for each expansion so players can earn badges for each expansion.
            // The "integration user is not author" test below will ensure achievements from
            // unrelated games cannot also be unlocked.
            'a' => "1,2,3,4",
            'h' => 1,
            'v' => AwardAchievementsTestHelpers::buildValidationHash('1,2,3,4', $delegatedUser, 1),
        ];

        $requestUrl = sprintf('dorequest.php?%s', http_build_query($params));
        $this->post($requestUrl, $payload)
            ->assertStatus(200)
            ->assertExactJson([
                "Success" => true,
                "Score" => $scoreBefore + $achievement1->points + $achievement2->points + $achievement3->points + $achievement4->points,
                "SoftcoreScore" => $casualScoreBefore,
                "ExistingIDs" => [],
                "SuccessfulIDs" => [
                    $achievement1->id,
                    $achievement2->id,
                    $achievement3->id,
                    $achievement4->id,
                ],
                "AchievementsRemaining" => 2, // from first game
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
        $this->assertHasHardcoreUnlock($delegatedUser, $achievement1);
        $this->assertHasHardcoreUnlock($delegatedUser, $achievement2);
        $this->assertHasHardcoreUnlock($delegatedUser, $achievement3);
        $this->assertHasHardcoreUnlock($delegatedUser, $achievement4);

        // player score should have increased
        $user1 = User::whereName($delegatedUser->username)->first();
        $this->assertEquals(
            $scoreBefore + $achievement1->points + $achievement2->points + $achievement3->points + $achievement4->points,
            $user1->points_hardcore
        );
        $this->assertEquals($casualScoreBefore, $user1->points);

        // make sure the unlock cache was updated
        $unlocks = getUserAchievementUnlocksForGame($delegatedUser->username, $game->id);
        $this->assertEqualsCanonicalizing(
            [
                $achievement1->id,
                $achievement2->id,
                $achievement4->id,
            ],
            array_keys($unlocks)
        );
        $unlocks = getUserAchievementUnlocksForGame($delegatedUser->username, $game2->id);
        $this->assertEqualsCanonicalizing(
            [
                $achievement3->id,
            ],
            array_keys($unlocks)
        );
    });
});

describe('validation', function () {
    test('non standalone system', function () {
        $game = Game::factory()->create();
        $achievement1 = Achievement::factory()->promoted()->create(['id' => 1, 'game_id' => $game->id]);
        $delegatedUser = User::factory()->create(['username' => 'Username', 'Permissions' => Permissions::Registered]);

        $scoreBefore = $delegatedUser->points_hardcore;
        $casualScoreBefore = $delegatedUser->points;

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
        $this->assertEquals($casualScoreBefore, $delegatedUser->points);

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
        $casualScoreBefore = $delegatedUser->points;

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
        $this->assertEquals($casualScoreBefore, $delegatedUser->points);

        // A session shouldn't have been created.
        $this->assertDatabaseMissing((new PlayerSession())->getTable(), [
            'user_id' => $delegatedUser->id,
            'game_id' => $game->id,
        ]);
    });

    test('unknown achievement', function () {
        $data = AwardAchievementsTestHelpers::createStandaloneGame();
        $game = $data['game'];
        $achievement1 = $data['achievements'][0];
        $delegatedUser = $data['delegatedUser'];
        $integrationUser = $data['integrationUser'];

        $scoreBefore = $delegatedUser->points_hardcore;
        $casualScoreBefore = $delegatedUser->points;

        $params = [
            'u' => $integrationUser->username,
            't' => $integrationUser->connect_token,
            'r' => 'awardachievements',
            'k' => $delegatedUser->username,
        ];
        $payload = [
            'a' => 999999,
            'h' => 1,
            'v' => md5('999999' . $delegatedUser->username . '1'),
        ];

        $requestUrl = sprintf('dorequest.php?%s', http_build_query($params));
        $this->post($requestUrl, $payload)
            ->assertStatus(404)
            ->assertExactJson([
                'Success' => false,
                'Status' => 404,
                'Code' => 'not_found',
                'Error' => 'Unknown achievement.',
            ]);
        $delegatedUser->refresh();

        // Points shouldn't change.
        $this->assertEquals($scoreBefore, $delegatedUser->points_hardcore);
        $this->assertEquals($casualScoreBefore, $delegatedUser->points);

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
        $casualScoreBefore = $delegatedUser->points;

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
        $this->assertEquals($casualScoreBefore, $delegatedUser->points);

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
        $casualScoreBefore = $delegatedUser->points;

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
        $casualScoreBefore = $delegatedUser->points;

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
            ->assertStatus(409)
            ->assertExactJson([
                'Success' => false,
                'Status' => 409,
                'Code' => 'invalid_state',
                'Error' => 'Unpromoted achievements cannot be unlocked.',
            ]);
        $delegatedUser->refresh();

        // Points shouldn't change.
        $this->assertEquals($scoreBefore, $delegatedUser->points_hardcore);
        $this->assertEquals($casualScoreBefore, $delegatedUser->points);

        // A session shouldn't have been created.
        $this->assertDatabaseMissing((new PlayerSession())->getTable(), [
            'user_id' => $delegatedUser->id,
            'game_id' => $game->id,
        ]);
    });

    test('empty delegate target is rejected', function () {
        $system = System::factory()->create(['id' => 1]);
        $game = Game::factory()->create(['system_id' => $system->id]);
        $achievement1 = Achievement::factory()->promoted()->create(['game_id' => $game->id]);

        $scoreBefore = $this->user->points_hardcore;
        $casualScoreBefore = $this->user->points;

        $params = [
            'u' => $this->user->username,
            't' => $this->user->connect_token,
            'r' => 'awardachievements',
            'k' => '',
        ];
        $payload = [
            'a' => $achievement1->id,
            'h' => 1,
            'v' => md5($achievement1->id . '1'),
        ];

        $requestUrl = sprintf('dorequest.php?%s', http_build_query($params));
        $this->post($requestUrl, $payload)
            ->assertStatus(404)
            ->assertExactJson([
                'Success' => false,
                'Status' => 404,
                'Code' => 'not_found',
                'Error' => 'Unknown target user.',
            ]);
        $this->user->refresh();

        // points shouldn't change
        $this->assertEquals($scoreBefore, $this->user->points_hardcore);
        $this->assertEquals($casualScoreBefore, $this->user->points);

        // no achievement unlock should have been recorded
        $this->assertDatabaseMissing((new PlayerAchievement())->getTable(), [
            'user_id' => $this->user->id,
            'achievement_id' => $achievement1->id,
        ]);

        // no session should have been created
        $this->assertDatabaseMissing((new PlayerSession())->getTable(), [
            'user_id' => $this->user->id,
            'game_id' => $game->id,
        ]);
    });
});
