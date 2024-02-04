<?php

declare(strict_types=1);

namespace Tests\Feature\Connect;

use App\Enums\Permissions;
use App\Models\User;
use App\Platform\Models\Achievement;
use App\Platform\Models\Game;
use App\Platform\Models\PlayerAchievement;
use App\Platform\Models\PlayerGame;
use App\Platform\Models\PlayerSession;
use App\Platform\Models\System;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\Feature\Platform\Concerns\TestsPlayerAchievements;
use Tests\TestCase;

class AwardAchievementsTest extends TestCase
{
    use RefreshDatabase;
    use TestsPlayerAchievements;

    public function testAwardMultipleAchievements(): void
    {
        $now = Carbon::now()->clone()->subMinutes(5)->startOfSecond();
        Carbon::setTestNow($now);

        /** @var System $standalonesSystem */
        $standalonesSystem = System::factory()->create(['ID' => 102]);
        /** @var Game $game */
        $game = Game::factory()->create(['ConsoleID' => $standalonesSystem->ID]);

        /** @var User $integrationUser */
        $integrationUser = User::factory()->create(['Permissions' => Permissions::Registered, 'appToken' => Str::random(16)]);
        /** @var User $delegatedUser */
        $delegatedUser = User::factory()->create(['User' => 'Username', 'Permissions' => Permissions::Registered, 'appToken' => Str::random(16)]);

        $delegatedUser->LastGameID = $game->id;
        $delegatedUser->save();

        /** @var Achievement $achievement1 */
        $achievement1 = Achievement::factory()->published()->create(['ID' => 1, 'GameID' => $game->ID, 'Author' => $integrationUser->User]);
        /** @var Achievement $achievement2 */
        $achievement2 = Achievement::factory()->published()->create(['ID' => 2, 'GameID' => $game->ID, 'Author' => $integrationUser->User]);
        /** @var Achievement $achievement3 */
        $achievement3 = Achievement::factory()->published()->create(['ID' => 3, 'GameID' => $game->ID, 'Author' => $integrationUser->User]);
        /** @var Achievement $achievement4 */
        $achievement4 = Achievement::factory()->published()->create(['ID' => 4, 'GameID' => $game->ID, 'Author' => $integrationUser->User]);

        $unlock1Date = $now->clone()->subMinutes(65);
        $this->addHardcoreUnlock($delegatedUser, $achievement1, $unlock1Date);

        $playerSession1 = PlayerSession::where([
            'user_id' => $delegatedUser->id,
            'game_id' => $achievement1->game_id,
        ])->orderByDesc('id')->first();
        $this->assertModelExists($playerSession1);

        // cache the unlocks for the game - verify multiple unlocks captured
        $unlocks = getUserAchievementUnlocksForGame($delegatedUser->User, $game->ID);
        $this->assertEquals([$achievement1->ID], array_keys($unlocks));

        // do the delegated unlocks sync
        $scoreBefore = $delegatedUser->RAPoints;
        $softcoreScoreBefore = $delegatedUser->RASoftcorePoints;

        $params = [
            'u' => $integrationUser->User,
            't' => $integrationUser->appToken,
            'r' => 'awardachievements',
            'k' => $delegatedUser->User,
        ];
        $payload = [
            // Note that #0 is already unlocked, thus it will not be in the "SuccessfulIDs" list.
            'a' => "1,2,3,4",
            'h' => 1,
            'v' => 'ae91e6a962b6ae5ec511108dbaa7c406',
        ];

        $requestUrl = sprintf('dorequest.php?%s', http_build_query($params));
        $this->post($requestUrl, $payload)
            ->assertExactJson([
                "Success" => true,
                "Score" => $scoreBefore + $achievement2->Points + $achievement3->Points + $achievement4->Points,
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
        $user1 = User::firstWhere('User', $delegatedUser->User);
        $this->assertEquals(
            $scoreBefore + $achievement2->Points + $achievement3->Points + $achievement4->Points,
            $user1->RAPoints
        );
        $this->assertEquals($softcoreScoreBefore, $user1->RASoftcorePoints);

        // make sure the unlock cache was updated
        $unlocks = getUserAchievementUnlocksForGame($delegatedUser->User, $game->ID);
        $this->assertEqualsCanonicalizing(
            [
                $achievement1->ID,
                $achievement2->ID,
                $achievement3->ID,
                $achievement4->ID,
            ],
            array_keys($unlocks)
        );
        $this->assertEquals($now, $unlocks[$achievement2->id]['DateEarnedHardcore']);
        $this->assertEquals($now, $unlocks[$achievement2->id]['DateEarned']);
    }

    public function testNotStandaloneSystem(): void
    {
        /** @var System $system */
        $system = System::factory()->create(['ID' => 1]);
        /** @var Game $game */
        $game = Game::factory()->create(['ConsoleID' => $system->ID]);

        /** @var User $integrationUser */
        $integrationUser = User::factory()->create(['Permissions' => Permissions::Registered, 'appToken' => Str::random(16)]);
        /** @var User $delegatedUser */
        $delegatedUser = User::factory()->create(['User' => 'Username', 'Permissions' => Permissions::Registered, 'appToken' => Str::random(16)]);

        /** @var Achievement $achievement1 */
        $achievement1 = Achievement::factory()->published()->create(['ID' => 1, 'GameID' => $game->ID, 'Author' => $integrationUser->User]);

        $scoreBefore = $delegatedUser->RAPoints;
        $softcoreScoreBefore = $delegatedUser->RASoftcorePoints;

        $params = [
            'u' => $integrationUser->User,
            't' => $integrationUser->appToken,
            'r' => 'awardachievements',
            'k' => $delegatedUser->User,
        ];
        $payload = [
            'a' => $achievement1->ID,
            'h' => 1,
            'v' => 'f3a3ef72749787fee6ae6cb933b651b0',
        ];

        $requestUrl = sprintf('dorequest.php?%s', http_build_query($params));
        $this->post($requestUrl, $payload)
            ->assertExactJson([
                "Success" => true,
                "Score" => $scoreBefore,
                "SoftcoreScore" => $softcoreScoreBefore,
                "ExistingIDs" => [],
                "SuccessfulIDs" => [], // empty because the achievement isn't part of a standalone system's game
            ]);
        $delegatedUser->refresh();

        // Points shouldn't change.
        $this->assertEquals($scoreBefore, $delegatedUser->Points);
        $this->assertEquals($softcoreScoreBefore, $delegatedUser->RASoftcorePoints);

        // A session shouldn't have been created.
        $this->assertDatabaseMissing((new PlayerSession())->getTable(), [
            'user_id' => $delegatedUser->id,
            'game_id' => $game->id,
        ]);
    }

    public function testWrongValidationHash(): void
    {
        /** @var System $standalonesSystem */
        $standalonesSystem = System::factory()->create(['ID' => 102]);
        /** @var Game $game */
        $game = Game::factory()->create(['ConsoleID' => $standalonesSystem->ID]);

        /** @var User $integrationUser */
        $integrationUser = User::factory()->create(['Permissions' => Permissions::Registered, 'appToken' => Str::random(16)]);
        /** @var User $delegatedUser */
        $delegatedUser = User::factory()->create(['User' => 'Username', 'Permissions' => Permissions::Registered, 'appToken' => Str::random(16)]);

        /** @var Achievement $achievement1 */
        $achievement1 = Achievement::factory()->published()->create(['ID' => 1, 'GameID' => $game->ID, 'Author' => $integrationUser->User]);

        $scoreBefore = $delegatedUser->RAPoints;
        $softcoreScoreBefore = $delegatedUser->RASoftcorePoints;

        $params = [
            'u' => $integrationUser->User,
            't' => $integrationUser->appToken,
            'r' => 'awardachievements',
            'k' => $delegatedUser->User,
        ];
        $payload = [
            'a' => $achievement1->id,
            'h' => 1,
            'v' => 'f3a3ef72749787fee6ae6cb933b651b1',
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
        $this->assertEquals($scoreBefore, $delegatedUser->Points);
        $this->assertEquals($softcoreScoreBefore, $delegatedUser->RASoftcorePoints);

        // A session shouldn't have been created.
        $this->assertDatabaseMissing((new PlayerSession())->getTable(), [
            'user_id' => $delegatedUser->id,
            'game_id' => $game->id,
        ]);
    }

    public function testNotAuthor(): void
    {
        /** @var System $standalonesSystem */
        $standalonesSystem = System::factory()->create(['ID' => 102]);
        /** @var Game $game */
        $game = Game::factory()->create(['ConsoleID' => $standalonesSystem->ID]);

        /** @var User $integrationUser */
        $integrationUser = User::factory()->create(['Permissions' => Permissions::Registered, 'appToken' => Str::random(16)]);
        /** @var User $delegatedUser */
        $delegatedUser = User::factory()->create(['User' => 'Username', 'Permissions' => Permissions::Registered, 'appToken' => Str::random(16)]);

        /** @var Achievement $achievement1 */
        $achievement1 = Achievement::factory()->published()->create(['ID' => 1, 'GameID' => $game->ID, 'Author' => 'Some Person']);

        $scoreBefore = $delegatedUser->RAPoints;
        $softcoreScoreBefore = $delegatedUser->RASoftcorePoints;

        $params = [
            'u' => $integrationUser->User,
            't' => $integrationUser->appToken,
            'r' => 'awardachievements',
            'k' => $delegatedUser->User,
        ];
        $payload = [
            'a' => $achievement1->id,
            'h' => 1,
            'v' => 'f3a3ef72749787fee6ae6cb933b651b0',
        ];

        $requestUrl = sprintf('dorequest.php?%s', http_build_query($params));
        $this->post($requestUrl, $payload)
            ->assertExactJson([
                "Success" => true,
                "Score" => $scoreBefore,
                "SoftcoreScore" => $softcoreScoreBefore,
                "ExistingIDs" => [],
                "SuccessfulIDs" => [], // empty because the achievement was not authored by the integration user
            ]);
        $delegatedUser->refresh();

        // Points shouldn't change.
        $this->assertEquals($scoreBefore, $delegatedUser->Points);
        $this->assertEquals($softcoreScoreBefore, $delegatedUser->RASoftcorePoints);

        // A session shouldn't have been created.
        $this->assertDatabaseMissing((new PlayerSession())->getTable(), [
            'user_id' => $delegatedUser->id,
            'game_id' => $game->id,
        ]);
    }

    public function testNoDelegatedUser(): void
    {
        /** @var System $standalonesSystem */
        $standalonesSystem = System::factory()->create(['ID' => 102]);
        /** @var Game $game */
        $game = Game::factory()->create(['ConsoleID' => $standalonesSystem->ID]);

        /** @var User $integrationUser */
        $integrationUser = User::factory()->create(['Permissions' => Permissions::Registered, 'appToken' => Str::random(16)]);

        /** @var Achievement $achievement1 */
        $achievement1 = Achievement::factory()->published()->create(['ID' => 1, 'GameID' => $game->ID, 'Author' => $integrationUser->User]);

        $params = [
            'u' => $integrationUser->User,
            't' => $integrationUser->appToken,
            'r' => 'awardachievements',
        ];
        $payload = [
            'a' => $achievement1->id,
            'h' => 1,
            'v' => 'f3a3ef72749787fee6ae6cb933b651b0',
        ];

        $requestUrl = sprintf('dorequest.php?%s', http_build_query($params));
        $this->post($requestUrl, $payload)
            ->assertStatus(400)
            ->assertExactJson([
                "Success" => false,
                "Error" => "You must specify a target user.",
                "Status" => 400,
            ]);
    }

    public function testInvalidDelegatedUser(): void
    {
        /** @var System $standalonesSystem */
        $standalonesSystem = System::factory()->create(['ID' => 102]);
        /** @var Game $game */
        $game = Game::factory()->create(['ConsoleID' => $standalonesSystem->ID]);

        /** @var User $integrationUser */
        $integrationUser = User::factory()->create(['Permissions' => Permissions::Registered, 'appToken' => Str::random(16)]);

        /** @var Achievement $achievement1 */
        $achievement1 = Achievement::factory()->published()->create(['ID' => 1, 'GameID' => $game->ID, 'Author' => $integrationUser->User]);

        $params = [
            'u' => $integrationUser->User,
            't' => $integrationUser->appToken,
            'r' => 'awardachievements',
            'k' => 'Some Guy',
        ];
        $payload = [
            'a' => $achievement1->id,
            'h' => 1,
            'v' => 'ab14ef5e11ab53ee7e9013770490761e',
        ];

        $requestUrl = sprintf('dorequest.php?%s', http_build_query($params));
        $this->post($requestUrl, $payload)
            ->assertStatus(404)
            ->assertExactJson([
                "Success" => false,
                "Error" => "The target user couldn't be found.",
                "Status" => 404,
                "Code" => "not_found",
            ]);
    }

    public function testGetCall(): void
    {
        /** @var System $standalonesSystem */
        $standalonesSystem = System::factory()->create(['ID' => 102]);
        /** @var Game $game */
        $game = Game::factory()->create(['ConsoleID' => $standalonesSystem->ID]);

        /** @var User $integrationUser */
        $integrationUser = User::factory()->create(['Permissions' => Permissions::Registered, 'appToken' => Str::random(16)]);
        /** @var User $delegatedUser */
        $delegatedUser = User::factory()->create(['User' => 'Username', 'Permissions' => Permissions::Registered, 'appToken' => Str::random(16)]);

        /** @var Achievement $achievement1 */
        $achievement1 = Achievement::factory()->published()->create(['ID' => 1, 'GameID' => $game->ID, 'Author' => $integrationUser->User]);

        $params = [
            'u' => $integrationUser->User,
            't' => $integrationUser->appToken,
            'r' => 'awardachievements',
            'h' => 1,
            'a' => $achievement1->id,
            'k' => $delegatedUser->User,
            'v' => 'f3a3ef72749787fee6ae6cb933b651b0',
        ];

        $requestUrl = sprintf('dorequest.php?%s', http_build_query($params));
        $this->get($requestUrl)
            ->assertStatus(405)
            ->assertJson([
                "Success" => false,
                "Error" => "Access denied.",
                "Status" => 405,
            ]);
    }
}
