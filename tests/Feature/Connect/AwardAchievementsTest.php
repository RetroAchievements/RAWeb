<?php

declare(strict_types=1);

namespace Tests\Feature\Connect;

use App\Platform\Models\Achievement;
use App\Platform\Models\Game;
use App\Platform\Models\PlayerAchievement;
use App\Platform\Models\PlayerGame;
use App\Platform\Models\PlayerSession;
use App\Platform\Models\System;
use App\Site\Enums\Permissions;
use App\Site\Models\User;
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
        $delegatedUser = User::factory()->create(['Permissions' => Permissions::Registered, 'appToken' => Str::random(16)]);

        $delegatedUser->LastGameID = $game->id;
        $delegatedUser->save();

        $achievements = Achievement::factory()->published()->count(4)->create(['GameID' => $game->ID, 'Author' => $integrationUser->User]);

        $unlock1Date = $now->clone()->subMinutes(65);
        $this->addHardcoreUnlock($delegatedUser, $achievements->get(0), $unlock1Date);

        $playerSession1 = PlayerSession::where([
            'user_id' => $delegatedUser->id,
            'game_id' => $achievements->get(0)->game_id,
        ])->orderByDesc('id')->first();
        $this->assertModelExists($playerSession1);

        // cache the unlocks for the game - verify multiple unlocks captured
        $unlocks = getUserAchievementUnlocksForGame($delegatedUser->User, $game->ID);
        $this->assertEquals([$achievements->get(0)->ID], array_keys($unlocks));

        // do the delegated unlocks sync
        $scoreBefore = $delegatedUser->RAPoints;
        $softcoreScoreBefore = $delegatedUser->RASoftcorePoints;

        $params = [
            'u' => $integrationUser->User,
            't' => $integrationUser->appToken,
            'r' => 'awardachievements',
            'k' => $delegatedUser->User,
            'h' => 1,
            // Note that #0 is already unlocked, thus it will not be in the "SuccessfulIDs" list.
            'a' => "{$achievements->get(0)->ID},{$achievements->get(1)->ID},{$achievements->get(2)->ID},{$achievements->get(3)->ID}",
        ];

        $requestUrl = sprintf('dorequest.php?%s', http_build_query($params));
        $this->post($requestUrl)
            ->assertExactJson([
                "Success" => true,
                "Score" => $scoreBefore + $achievements->get(1)->Points + $achievements->get(2)->Points + $achievements->get(3)->Points,
                "SoftcoreScore" => $softcoreScoreBefore,
                "ExistingIDs" => [
                    $achievements->get(0)->id,
                ],
                "SuccessfulIDs" => [
                    $achievements->get(1)->id,
                    $achievements->get(2)->id,
                    $achievements->get(3)->id,
                ],
            ]);
        $delegatedUser->refresh();

        // player session resumed
        $playerSession2 = PlayerSession::where([
            'user_id' => $delegatedUser->id,
            'game_id' => $achievements->get(1)->game_id,
        ])->orderByDesc('id')->first();
        $this->assertModelExists($playerSession2);

        // game attached
        $playerGame = PlayerGame::where([
            'user_id' => $delegatedUser->id,
            'game_id' => $achievements->get(1)->game_id,
        ])->first();
        $this->assertModelExists($playerGame);
        $this->assertNotNull($playerGame->last_played_at);

        // three achievements unlocked
        $playerAchievement1 = PlayerAchievement::where([
            'user_id' => $delegatedUser->id,
            'achievement_id' => $achievements->get(1)->id,
        ])->first();
        $this->assertModelExists($playerAchievement1);
        $this->assertNotNull($playerAchievement1->unlocked_at);
        $this->assertNotNull($playerAchievement1->unlocked_hardcore_at);
        $this->assertEquals($playerAchievement1->player_session_id, $playerSession2->id);

        $playerAchievement2 = PlayerAchievement::where([
            'user_id' => $delegatedUser->id,
            'achievement_id' => $achievements->get(2)->id,
        ])->first();
        $this->assertModelExists($playerAchievement2);
        $this->assertNotNull($playerAchievement2->unlocked_at);
        $this->assertNotNull($playerAchievement2->unlocked_hardcore_at);
        $this->assertEquals($playerAchievement2->player_session_id, $playerSession2->id);

        $playerAchievement3 = PlayerAchievement::where([
            'user_id' => $delegatedUser->id,
            'achievement_id' => $achievements->get(3)->id,
        ])->first();
        $this->assertModelExists($playerAchievement3);
        $this->assertNotNull($playerAchievement3->unlocked_at);
        $this->assertNotNull($playerAchievement3->unlocked_hardcore_at);
        $this->assertEquals($playerAchievement3->player_session_id, $playerSession2->id);

        // player score should have increased
        $user1 = User::firstWhere('User', $delegatedUser->User);
        $this->assertEquals(
            $scoreBefore + $achievements->get(1)->Points + $achievements->get(2)->Points + $achievements->get(3)->Points,
            $user1->RAPoints
        );
        $this->assertEquals($softcoreScoreBefore, $user1->RASoftcorePoints);

        // make sure the unlock cache was updated
        $unlocks = getUserAchievementUnlocksForGame($delegatedUser->User, $game->ID);
        $this->assertEqualsCanonicalizing(
            [
                $achievements->get(0)->ID,
                $achievements->get(1)->ID,
                $achievements->get(2)->ID,
                $achievements->get(3)->ID,
            ],
            array_keys($unlocks)
        );
        $this->assertEquals($now, $unlocks[$achievements->get(1)->id]['DateEarnedHardcore']);
        $this->assertEquals($now, $unlocks[$achievements->get(1)->id]['DateEarned']);
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
        $delegatedUser = User::factory()->create(['Permissions' => Permissions::Registered, 'appToken' => Str::random(16)]);

        $achievements = Achievement::factory()->published()->count(4)->create(['GameID' => $game->ID, 'Author' => $integrationUser->User]);

        $scoreBefore = $delegatedUser->RAPoints;
        $softcoreScoreBefore = $delegatedUser->RASoftcorePoints;

        $params = [
            'u' => $integrationUser->User,
            't' => $integrationUser->appToken,
            'r' => 'awardachievements',
            'k' => $delegatedUser->User,
            'h' => 1,
            'a' => $achievements->get(0)->ID,
        ];

        $requestUrl = sprintf('dorequest.php?%s', http_build_query($params));
        $this->post($requestUrl)
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

    public function testNotAuthor(): void
    {
        /** @var System $standalonesSystem */
        $standalonesSystem = System::factory()->create(['ID' => 102]);
        /** @var Game $game */
        $game = Game::factory()->create(['ConsoleID' => $standalonesSystem->ID]);

        /** @var User $integrationUser */
        $integrationUser = User::factory()->create(['Permissions' => Permissions::Registered, 'appToken' => Str::random(16)]);
        /** @var User $delegatedUser */
        $delegatedUser = User::factory()->create(['Permissions' => Permissions::Registered, 'appToken' => Str::random(16)]);

        $achievements = Achievement::factory()->published()->count(4)->create(['GameID' => $game->ID]);

        $scoreBefore = $delegatedUser->RAPoints;
        $softcoreScoreBefore = $delegatedUser->RASoftcorePoints;

        $params = [
            'u' => $integrationUser->User,
            't' => $integrationUser->appToken,
            'r' => 'awardachievements',
            'k' => $delegatedUser->User,
            'h' => 1,
            'a' => $achievements->get(0)->ID,
        ];

        $requestUrl = sprintf('dorequest.php?%s', http_build_query($params));
        $this->post($requestUrl)
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

        $achievements = Achievement::factory()->published()->count(4)->create(['GameID' => $game->ID]);

        $params = [
            'u' => $integrationUser->User,
            't' => $integrationUser->appToken,
            'r' => 'awardachievements',
            'h' => 1,
            'a' => $achievements->get(0)->ID,
        ];

        $requestUrl = sprintf('dorequest.php?%s', http_build_query($params));
        $this->post($requestUrl)
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

        $achievements = Achievement::factory()->published()->count(4)->create(['GameID' => $game->ID]);

        $params = [
            'u' => $integrationUser->User,
            't' => $integrationUser->appToken,
            'r' => 'awardachievements',
            'h' => 1,
            'a' => $achievements->get(0)->ID,
            'k' => 'Some Guy',
        ];

        $requestUrl = sprintf('dorequest.php?%s', http_build_query($params));
        $this->post($requestUrl)
            ->assertStatus(400)
            ->assertExactJson([
                "Success" => false,
                "Error" => "The target user couldn't be found.",
                "Status" => 400,
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
        $delegatedUser = User::factory()->create(['Permissions' => Permissions::Registered, 'appToken' => Str::random(16)]);

        $achievements = Achievement::factory()->published()->count(850)->create(['GameID' => $game->ID]);
        $achievementIds = $achievements->pluck('ID')->implode(',');

        $params = [
            'u' => $integrationUser->User,
            't' => $integrationUser->appToken,
            'r' => 'awardachievements',
            'h' => 1,
            'a' => $achievementIds,
            'k' => $delegatedUser->User,
        ];

        $requestUrl = sprintf('dorequest.php?%s', http_build_query($params));
        $this->get($requestUrl)
            ->assertStatus(403)
            ->assertJson([
                "Success" => false,
                "Error" => "Access denied.",
                "Status" => 403,
            ]);
    }
}
