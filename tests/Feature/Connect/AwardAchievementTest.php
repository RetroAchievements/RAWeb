<?php

declare(strict_types=1);

namespace Tests\Feature\Connect;

use App\Community\Enums\AwardType;
use App\Enums\Permissions;
use App\Models\User;
use App\Platform\Enums\UnlockMode;
use App\Platform\Models\Achievement;
use App\Platform\Models\Game;
use App\Platform\Models\PlayerAchievement;
use App\Platform\Models\PlayerBadge;
use App\Platform\Models\PlayerGame;
use App\Platform\Models\PlayerSession;
use App\Platform\Models\System;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\Feature\Platform\Concerns\TestsPlayerAchievements;
use Tests\TestCase;

class AwardAchievementTest extends TestCase
{
    use BootstrapsConnect;
    use RefreshDatabase;
    use TestsPlayerAchievements;

    private function buildValidationHash(Achievement $achievement, User $user, int $hardcore): string
    {
        return md5(strval($achievement->ID) . $user->User . strval($hardcore));
    }

    public function testHardcoreUnlock(): void
    {
        $now = Carbon::now()->clone()->subMinutes(5)->startOfSecond();
        Carbon::setTestNow($now);

        /** @var User $author */
        $author = User::factory()->create();
        $game = $this->seedGame(withHash: false);
        $gameHash = '0123456789abcdeffedcba9876543210';
        /** @var Achievement $achievement1 */
        $achievement1 = Achievement::factory()->published()->create(['GameID' => $game->ID, 'Author' => $author->User]);
        /** @var Achievement $achievement2 */
        $achievement2 = Achievement::factory()->published()->create(['GameID' => $game->ID, 'Author' => $author->User]);
        /** @var Achievement $achievement3 */
        $achievement3 = Achievement::factory()->published()->create(['GameID' => $game->ID, 'Author' => $author->User]);
        /** @var Achievement $achievement4 */
        $achievement4 = Achievement::factory()->published()->create(['GameID' => $game->ID, 'Author' => $author->User]);
        /** @var Achievement $achievement5 */
        $achievement5 = Achievement::factory()->published()->create(['GameID' => $game->ID, 'Author' => $author->User]);
        /** @var Achievement $achievement6 */
        $achievement6 = Achievement::factory()->published()->create(['GameID' => $game->ID, 'Author' => $author->User]);

        $unlock1Date = $now->clone()->subMinutes(65);
        $this->addHardcoreUnlock($this->user, $achievement1, $unlock1Date);
        $this->addHardcoreUnlock($this->user, $achievement5, $unlock1Date);
        $this->addHardcoreUnlock($this->user, $achievement6, $unlock1Date);

        $playerSession1 = PlayerSession::where([
            'user_id' => $this->user->id,
            'game_id' => $achievement3->game_id,
        ])->orderByDesc('id')->first();
        $this->assertModelExists($playerSession1);

        // cache the unlocks for the game - verify singular unlock captured
        $unlocks = getUserAchievementUnlocksForGame($this->user->User, $game->ID);
        $this->assertEquals([$achievement1->ID, $achievement5->ID, $achievement6->ID], array_keys($unlocks));

        // do the hardcore unlock
        $validationHash = $this->buildValidationHash($achievement3, $this->user, 1);
        $scoreBefore = $this->user->RAPoints;
        $softcoreScoreBefore = $this->user->RASoftcorePoints;
        $truePointsBefore = $this->user->TrueRAPoints;

        $this->get($this->apiUrl('awardachievement', ['a' => $achievement3->ID, 'h' => 1, 'm' => $gameHash, 'v' => $validationHash]))
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement3->ID,
                'AchievementsRemaining' => 2,
                'Score' => $scoreBefore + $achievement3->Points,
                'SoftcoreScore' => $softcoreScoreBefore,
            ]);
        $this->user->refresh();

        // player session resumed
        $playerSession2 = PlayerSession::where([
            'user_id' => $this->user->id,
            'game_id' => $achievement3->game_id,
        ])->orderByDesc('id')->first();
        $this->assertModelExists($playerSession2);

        // game attached
        $playerGame = PlayerGame::where([
            'user_id' => $this->user->id,
            'game_id' => $achievement3->game_id,
        ])->first();
        $this->assertModelExists($playerGame);
        $this->assertNotNull($playerGame->last_played_at);

        // achievement unlocked
        $playerAchievement = PlayerAchievement::where([
            'user_id' => $this->user->id,
            'achievement_id' => $achievement3->id,
        ])->first();
        $this->assertModelExists($playerAchievement);
        $this->assertNotNull($playerAchievement->unlocked_at);
        $this->assertNotNull($playerAchievement->unlocked_hardcore_at);
        $this->assertEquals($playerAchievement->player_session_id, $playerSession2->id);

        // player score should have increased
        $user1 = User::firstWhere('User', $this->user->User);
        $this->assertEquals($scoreBefore + $achievement3->Points, $user1->RAPoints);
        $this->assertEquals($softcoreScoreBefore, $user1->RASoftcorePoints);

        // make sure the unlock cache was updated
        $unlocks = getUserAchievementUnlocksForGame($this->user->User, $game->ID);
        $this->assertEqualsCanonicalizing([$achievement1->ID, $achievement5->ID, $achievement6->ID, $achievement3->ID], array_keys($unlocks));
        $this->assertEquals($now, $unlocks[$achievement3->ID]['DateEarnedHardcore']);
        $this->assertEquals($now, $unlocks[$achievement3->ID]['DateEarned']);

        // repeat the hardcore unlock
        $scoreBefore = $user1->RAPoints;
        $softcoreScoreBefore = $user1->RASoftcorePoints;
        $truePointsBefore = $user1->TrueRAPoints;

        $newNow = $now->clone()->addMinutes(5);
        Carbon::setTestNow($newNow);
        $this->assertNotEquals($now, Carbon::now());

        $this->get($this->apiUrl('awardachievement', ['a' => $achievement3->ID, 'h' => 1, 'm' => $gameHash, 'v' => $validationHash]))
            ->assertExactJson([
                'Success' => false,
                // client detects the "User already has" and does not report this as an error.
                'Error' => 'User already has this achievement unlocked in hardcore mode.',
                'AchievementID' => $achievement3->ID,
                'AchievementsRemaining' => 2,
                'Score' => $scoreBefore,
                'SoftcoreScore' => $softcoreScoreBefore,
            ]);

        // player score should not have increased
        $user2 = User::firstWhere('User', $this->user->User);
        $this->assertEquals($scoreBefore, $user2->RAPoints);
        $this->assertEquals($softcoreScoreBefore, $user2->RASoftcorePoints);
        $this->assertEquals($truePointsBefore, $user2->TrueRAPoints);

        // make sure the unlock time didn't change
        $unlockTime = $this->getUnlockTime($user2, $achievement3, UnlockMode::Hardcore);
        $this->assertEquals($now, $unlockTime);
        $unlockTime = $this->getUnlockTime($user2, $achievement3, UnlockMode::Softcore);
        $this->assertEquals($now, $unlockTime);

        // unlock the rest of the set
        $validationHash = $this->buildValidationHash($achievement2, $this->user, 1);
        $this->get($this->apiUrl('awardachievement', ['a' => $achievement2->ID, 'h' => 1, 'm' => $gameHash, 'v' => $validationHash]))
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement2->ID,
                'AchievementsRemaining' => 1,
                'Score' => $scoreBefore + $achievement2->Points,
                'SoftcoreScore' => $softcoreScoreBefore,
            ]);

        $validationHash = $this->buildValidationHash($achievement4, $this->user, 1);
        $this->get($this->apiUrl('awardachievement', ['a' => $achievement4->ID, 'h' => 1, 'm' => $gameHash, 'v' => $validationHash]))
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement4->ID,
                'AchievementsRemaining' => 0,
                'Score' => $scoreBefore + $achievement2->Points + $achievement4->Points,
                'SoftcoreScore' => $softcoreScoreBefore,
            ]);

        // verify badge was awarded
        $this->assertNotNull(PlayerBadge::where('User', $this->user->User)
            ->where('AwardType', AwardType::Mastery)
            ->where('AwardData', $game->ID)
            ->where('AwardDataExtra', UnlockMode::Hardcore)
            ->where('AwardDate', $newNow)
            ->first()
        );
    }

    public function testSoftcoreUnlockPromotedToHardcore(): void
    {
        $now = Carbon::now()->clone()->subMinutes(5)->startOfSecond();
        Carbon::setTestNow($now);

        /** @var User $author */
        $author = User::factory()->create();

        $game = $this->seedGame(withHash: false);
        $gameHash = '0123456789abcdeffedcba9876543210';
        /** @var Achievement $achievement1 */
        $achievement1 = Achievement::factory()->published()->create(['GameID' => $game->ID, 'Author' => $author->User]);
        /** @var Achievement $achievement2 */
        $achievement2 = Achievement::factory()->published()->create(['GameID' => $game->ID, 'Author' => $author->User]);
        /** @var Achievement $achievement3 */
        $achievement3 = Achievement::factory()->published()->create(['GameID' => $game->ID, 'Author' => $author->User]);
        /** @var Achievement $achievement4 */
        $achievement4 = Achievement::factory()->published()->create(['GameID' => $game->ID, 'Author' => $author->User]);
        /** @var Achievement $achievement5 */
        $achievement5 = Achievement::factory()->published()->create(['GameID' => $game->ID, 'Author' => $author->User]);
        /** @var Achievement $achievement6 */
        $achievement6 = Achievement::factory()->published()->create(['GameID' => $game->ID, 'Author' => $author->User]);

        $unlock1Date = $now->clone()->subMinutes(65);
        $this->addHardcoreUnlock($this->user, $achievement1, $unlock1Date);
        $this->addSoftcoreUnlock($this->user, $achievement5, $unlock1Date);
        $this->addSoftcoreUnlock($this->user, $achievement6, $unlock1Date);

        // cache the unlocks for the game - verify singular unlock captured
        $unlocks = getUserAchievementUnlocksForGame($this->user->User, $game->ID);
        $this->assertEquals([$achievement1->ID, $achievement5->ID, $achievement6->ID], array_keys($unlocks));

        // do the softcore unlock
        $validationHash = $this->buildValidationHash($achievement3, $this->user, 0);
        $scoreBefore = $this->user->RAPoints;
        $softcoreScoreBefore = $this->user->RASoftcorePoints;
        $truePointsBefore = $this->user->TrueRAPoints;

        $this->get($this->apiUrl('awardachievement', ['a' => $achievement3->ID, 'h' => 0, 'm' => $gameHash, 'v' => $validationHash]))
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement3->ID,
                'AchievementsRemaining' => 2,
                'Score' => $scoreBefore,
                'SoftcoreScore' => $softcoreScoreBefore + $achievement3->Points,
            ]);
        $this->user->refresh();

        // player score should have increased
        $user1 = $this->user;
        $this->assertEquals($scoreBefore, $user1->RAPoints);
        $this->assertEquals($softcoreScoreBefore + $achievement3->Points, $user1->RASoftcorePoints);
        $this->assertEquals($truePointsBefore, $user1->TrueRAPoints);

        // make sure the unlock cache was updated
        $unlocks = getUserAchievementUnlocksForGame($this->user->User, $game->ID);
        $this->assertEqualsCanonicalizing([$achievement1->ID, $achievement5->ID, $achievement6->ID, $achievement3->ID], array_keys($unlocks));
        $this->assertEquals($now, $unlocks[$achievement3->ID]['DateEarned']);
        $this->assertArrayNotHasKey('DateEarnedHardcore', $unlocks[$achievement3->ID]);

        // repeat the softcore unlock
        $scoreBefore = $user1->RAPoints;
        $softcoreScoreBefore = $user1->RASoftcorePoints;

        $newNow = $now->clone()->addMinutes(5);
        Carbon::setTestNow($newNow);
        $this->assertNotEquals($now, Carbon::now());

        $this->get($this->apiUrl('awardachievement', ['a' => $achievement3->ID, 'h' => 0, 'm' => $gameHash, 'v' => $validationHash]))
            ->assertExactJson([
                'Success' => false,
                // client detects the "User already has" and does not report this as an error.
                'Error' => 'User already has this achievement unlocked.',
                'AchievementID' => $achievement3->ID,
                'AchievementsRemaining' => 2,
                'Score' => $scoreBefore,
                'SoftcoreScore' => $softcoreScoreBefore,
            ]);

        // player score should not have increased
        $user2 = User::firstWhere('User', $this->user->User);
        $this->assertEquals($scoreBefore, $user2->RAPoints);
        $this->assertEquals($softcoreScoreBefore, $user2->RASoftcorePoints);
        $this->assertEquals($truePointsBefore, $user2->TrueRAPoints);

        // make sure the unlock time didn't change
        $unlockTime = $this->getUnlockTime($user2, $achievement3, UnlockMode::Softcore);
        $this->assertEquals($now, $unlockTime);
        $this->assertNull($this->getUnlockTime($user2, $achievement3, UnlockMode::Hardcore));

        // do the hardcore unlock
        $validationHash = $this->buildValidationHash($achievement3, $this->user, 1);
        $scoreBefore = $user1->RAPoints;
        $softcoreScoreBefore = $user1->RASoftcorePoints;

        $this->get($this->apiUrl('awardachievement', ['a' => $achievement3->ID, 'h' => 1, 'm' => $gameHash, 'v' => $validationHash]))
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement3->ID,
                'AchievementsRemaining' => 4,
                'Score' => $scoreBefore + $achievement3->Points,
                'SoftcoreScore' => $softcoreScoreBefore - $achievement3->Points,
            ]);
        $this->user->refresh();

        // player score should have adjusted
        $user2 = $this->user;
        $this->assertEquals($scoreBefore + $achievement3->Points, $user2->RAPoints);
        $this->assertEquals($softcoreScoreBefore - $achievement3->Points, $user2->RASoftcorePoints);

        // make sure the unlock cache was updated
        $unlocks = getUserAchievementUnlocksForGame($this->user->User, $game->ID);
        $this->assertEqualsCanonicalizing([$achievement1->ID, $achievement5->ID, $achievement6->ID, $achievement3->ID], array_keys($unlocks));
        $this->assertEquals($now, $unlocks[$achievement3->ID]['DateEarned']);
        $this->assertEquals($newNow, $unlocks[$achievement3->ID]['DateEarnedHardcore']);

        // make sure the softcore unlock time didn't change
        $unlockTime = $this->getUnlockTime($user2, $achievement3, UnlockMode::Softcore);
        $this->assertEquals($now, $unlockTime);
        $unlockTime = $this->getUnlockTime($user2, $achievement3, UnlockMode::Hardcore);
        $this->assertEquals($newNow, $unlockTime);

        // unlock the rest of the set
        $scoreBefore = $user2->RAPoints;
        $softcoreScoreBefore = $user2->RASoftcorePoints;

        $validationHash = $this->buildValidationHash($achievement2, $this->user, 0);
        $this->get($this->apiUrl('awardachievement', ['a' => $achievement2->ID, 'h' => 0, 'm' => $gameHash, 'v' => $validationHash]))
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement2->ID,
                'AchievementsRemaining' => 1,
                'Score' => $scoreBefore,
                'SoftcoreScore' => $softcoreScoreBefore + $achievement2->Points,
            ]);

        $validationHash = $this->buildValidationHash($achievement4, $this->user, 0);
        $this->get($this->apiUrl('awardachievement', ['a' => $achievement4->ID, 'h' => 0, 'm' => $gameHash, 'v' => $validationHash]))
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement4->ID,
                'AchievementsRemaining' => 0,
                'Score' => $scoreBefore,
                'SoftcoreScore' => $softcoreScoreBefore + $achievement2->Points + $achievement4->Points,
            ]);

        // verify badge was awarded
        $this->assertNotNull(PlayerBadge::where('User', $this->user->User)
            ->where('AwardType', AwardType::Mastery)
            ->where('AwardData', $game->ID)
            ->where('AwardDataExtra', UnlockMode::Softcore)
            ->where('AwardDate', $newNow)
            ->first()
        );
    }

    public function testDelegatedUnlock(): void
    {
        $now = Carbon::now()->clone()->subMinutes(5)->startOfSecond();
        Carbon::setTestNow($now);

        /** @var System $standalonesSystem */
        $standalonesSystem = System::factory()->create(['ID' => 102]);
        /** @var Game $gameOne */
        $gameOne = $this->seedGame(system: $standalonesSystem, withHash: false);

        /** @var User $integrationUser */
        $integrationUser = User::factory()->create(['Permissions' => Permissions::Registered, 'appToken' => Str::random(16)]);
        /** @var User $delegatedUser */
        $delegatedUser = User::factory()->create(['User' => 'Username', 'Permissions' => Permissions::Registered, 'appToken' => Str::random(16)]);

        $delegatedUser->LastGameID = $gameOne->id;
        $delegatedUser->save();

        /** @var Achievement $achievement1 */
        $achievement1 = Achievement::factory()->published()->create(['ID' => 1, 'GameID' => $gameOne->ID, 'Author' => $integrationUser->User]);
        /** @var Achievement $achievement2 */
        $achievement2 = Achievement::factory()->published()->create(['ID' => 2, 'GameID' => $gameOne->ID, 'Author' => $integrationUser->User]);
        /** @var Achievement $achievement3 */
        $achievement3 = Achievement::factory()->published()->create(['ID' => 3, 'GameID' => $gameOne->ID, 'Author' => $integrationUser->User]);
        /** @var Achievement $achievement4 */
        $achievement4 = Achievement::factory()->published()->create(['ID' => 4, 'GameID' => $gameOne->ID]);
        /** @var Achievement $achievement5 */
        $achievement5 = Achievement::factory()->published()->create(['ID' => 5, 'GameID' => $gameOne->ID, 'Author' => $integrationUser->User]);
        /** @var Achievement $achievement6 */
        $achievement6 = Achievement::factory()->published()->create(['ID' => 6, 'GameID' => $gameOne->ID, 'Author' => $integrationUser->User]);

        $unlock1Date = $now->clone()->subMinutes(65);
        $this->addHardcoreUnlock($delegatedUser, $achievement1, $unlock1Date);
        $this->addHardcoreUnlock($delegatedUser, $achievement5, $unlock1Date);
        $this->addHardcoreUnlock($delegatedUser, $achievement6, $unlock1Date);

        $playerSession1 = PlayerSession::where([
            'user_id' => $delegatedUser->id,
            'game_id' => $achievement3->game_id,
        ])->orderByDesc('id')->first();
        $this->assertModelExists($playerSession1);

        // cache the unlocks for the game - verify singular unlock captured
        $unlocks = getUserAchievementUnlocksForGame($delegatedUser->User, $gameOne->ID);
        $this->assertEquals([$achievement1->ID, $achievement5->ID, $achievement6->ID], array_keys($unlocks));

        // do the delegated hardcore unlock
        $scoreBefore = $delegatedUser->RAPoints;
        $softcoreScoreBefore = $delegatedUser->RASoftcorePoints;

        $params = [
            'u' => $integrationUser->User,
            't' => $integrationUser->appToken,
            'r' => 'awardachievement',
            'k' => $delegatedUser->User,
            'h' => 1,
            'a' => $achievement3->ID,
            'v' => '62c47b9fba313855ff8a09673780bb35',
        ];

        $requestUrl = sprintf('dorequest.php?%s', http_build_query($params));
        $this->post($requestUrl)
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement3->ID,
                'AchievementsRemaining' => 2,
                'Score' => $scoreBefore + $achievement3->Points,
                'SoftcoreScore' => $softcoreScoreBefore,
            ]);
        $delegatedUser->refresh();

        // player session resumed
        $playerSession2 = PlayerSession::where([
            'user_id' => $delegatedUser->id,
            'game_id' => $achievement3->game_id,
        ])->orderByDesc('id')->first();
        $this->assertModelExists($playerSession2);

        // game attached
        $playerGame = PlayerGame::where([
            'user_id' => $delegatedUser->id,
            'game_id' => $achievement3->game_id,
        ])->first();
        $this->assertModelExists($playerGame);
        $this->assertNotNull($playerGame->last_played_at);

        // achievement unlocked
        $playerAchievement = PlayerAchievement::where([
            'user_id' => $delegatedUser->id,
            'achievement_id' => $achievement3->id,
        ])->first();
        $this->assertModelExists($playerAchievement);
        $this->assertNotNull($playerAchievement->unlocked_at);
        $this->assertNotNull($playerAchievement->unlocked_hardcore_at);
        $this->assertEquals($playerAchievement->player_session_id, $playerSession2->id);

        // player score should have increased
        $user1 = User::firstWhere('User', $delegatedUser->User);
        $this->assertEquals($scoreBefore + $achievement3->Points, $user1->RAPoints);
        $this->assertEquals($softcoreScoreBefore, $user1->RASoftcorePoints);

        // make sure the unlock cache was updated
        $unlocks = getUserAchievementUnlocksForGame($delegatedUser->User, $gameOne->ID);
        $this->assertEqualsCanonicalizing([$achievement1->ID, $achievement5->ID, $achievement6->ID, $achievement3->ID], array_keys($unlocks));
        $this->assertEquals($now, $unlocks[$achievement3->ID]['DateEarnedHardcore']);
        $this->assertEquals($now, $unlocks[$achievement3->ID]['DateEarned']);

        // Next, try to award an achievement on a non-standalone game.
        // This is not allowed and should fail, even if the integration user is the achievement author.
        /** @var System $normalSystem */
        $normalSystem = System::factory()->create(['ID' => 1]);
        /** @var Game $gameTwo */
        $gameTwo = Game::factory()->create(['ConsoleID' => $normalSystem->ID]);

        $achievements = Achievement::factory()->published()->count(6)->create(['GameID' => $gameTwo->id, 'Author' => $integrationUser->User]);

        $params['a'] = $achievements->get(0)->id;

        $requestUrl = sprintf('dorequest.php?%s', http_build_query($params));
        $this->post($requestUrl)
            ->assertStatus(403)
            ->assertExactJson([
                "Success" => false,
                "Error" => "You do not have permission to do that.",
                "Code" => "access_denied",
                "Status" => 403,
            ]);

        // Next, try to award an achievement not authored by the integration user on a standalone game.
        // This is not allowed and should fail.
        $params['a'] = $achievement4->id;

        $requestUrl = sprintf('dorequest.php?%s', http_build_query($params));
        $this->post($requestUrl)
            ->assertStatus(403)
            ->assertExactJson([
                "Success" => false,
                "Error" => "You do not have permission to do that.",
                "Code" => "access_denied",
                "Status" => 403,
            ]);

        // Next, try a GET call, which should be blocked.
        $params = [
            'u' => $integrationUser->User,
            't' => $integrationUser->appToken,
            'r' => 'awardachievement',
            'k' => $delegatedUser->User,
            'h' => 1,
            'a' => $achievement3->ID,
        ];

        $requestUrl = sprintf('dorequest.php?%s', http_build_query($params));
        $this->get($requestUrl)
            ->assertStatus(405)
            ->assertJson([
                "Success" => false,
                "Error" => "Access denied.",
                "Status" => 405,
            ]);

        // Next, try a call that doesn't include a validation hash. This should fail.
        $params = [
            'u' => $integrationUser->User,
            't' => $integrationUser->appToken,
            'r' => 'awardachievement',
            'k' => $delegatedUser->User,
            'h' => 1,
            'a' => $achievement3->ID,
        ];

        $requestUrl = sprintf('dorequest.php?%s', http_build_query($params));
        $this->post($requestUrl)
            ->assertStatus(403)
            ->assertJson([
                "Success" => false,
                "Error" => "Access denied.",
                "Status" => 403,
            ]);
    }

    public function testErrors(): void
    {
        $now = Carbon::now()->clone()->subMinutes(5)->startOfSecond();
        Carbon::setTestNow($now);

        /** @var User $author */
        $author = User::factory()->create();
        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create(['ConsoleID' => $system->ID]);
        $gameHash = '0123456789abcdeffedcba9876543210';
        /** @var Achievement $achievement1 */
        $achievement1 = Achievement::factory()->published()->create(['GameID' => $game->ID, 'Author' => $author->User]);
        /** @var Achievement $achievement2 */
        $achievement2 = Achievement::factory()->published()->create(['GameID' => $game->ID, 'Author' => $author->User]);
        /** @var Achievement $achievement3 */
        $achievement3 = Achievement::factory()->published()->create(['GameID' => $game->ID, 'Author' => $author->User]);
        /** @var Achievement $achievement4 */
        $achievement4 = Achievement::factory()->create(['GameID' => $game->ID, 'Author' => $author->User]);

        $unlock1Date = $now->clone()->subMinutes(65);
        $this->addHardcoreUnlock($this->user, $achievement1, $unlock1Date);

        $scoreBefore = $this->user->RAPoints;
        $softcoreScoreBefore = $this->user->RASoftcorePoints;

        $validationHash = md5('999999' . $this->user->User . '1');
        $this->get($this->apiUrl('awardachievement', ['a' => 999999, 'h' => 1, 'm' => $gameHash, 'v' => $validationHash]))
            ->assertExactJson([
                'Success' => false,
                'Error' => 'Data not found for achievement 999999',
                'AchievementID' => 999999,
                'Score' => $scoreBefore,
                'SoftcoreScore' => $softcoreScoreBefore,
            ]);

        $validationHash = $this->buildValidationHash($achievement4, $this->user, 1);
        $this->get($this->apiUrl('awardachievement', ['a' => $achievement4->ID, 'h' => 1, 'm' => $gameHash, 'v' => $validationHash]))
            ->assertExactJson([
                'Success' => false,
                'Error' => 'Unofficial achievements cannot be unlocked',
                'AchievementID' => $achievement4->ID,
                'Score' => $scoreBefore,
                'SoftcoreScore' => $softcoreScoreBefore,
            ]);

       // player score should not have adjusted
       $user1 = User::firstWhere('User', $this->user->User);
       $this->assertEquals($scoreBefore, $user1->RAPoints);
       $this->assertEquals($softcoreScoreBefore, $user1->RASoftcorePoints);
    }
}
