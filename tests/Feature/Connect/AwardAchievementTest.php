<?php

declare(strict_types=1);

namespace Tests\Feature\Connect;

use App\Community\Enums\AwardType;
use App\Enums\Permissions;
use App\Models\Achievement;
use App\Models\EventAchievement;
use App\Models\Game;
use App\Models\GameHash;
use App\Models\PlayerAchievement;
use App\Models\PlayerBadge;
use App\Models\PlayerGame;
use App\Models\PlayerSession;
use App\Models\System;
use App\Models\User;
use App\Platform\Actions\UnlockPlayerAchievementAction;
use App\Platform\Enums\UnlockMode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\Feature\Concerns\TestsEmulatorUserAgent;
use Tests\Feature\Platform\Concerns\TestsPlayerAchievements;
use Tests\TestCase;

class AwardAchievementTest extends TestCase
{
    use BootstrapsConnect;
    use RefreshDatabase;
    use TestsEmulatorUserAgent;
    use TestsPlayerAchievements;

    private function buildValidationHash(Achievement $achievement, User $user, int $hardcore, int $offset = 0): string
    {
        $data = $achievement->id . $user->username . $hardcore . $achievement->id;
        if ($offset > 0) {
            $data .= $offset;
        }

        return md5($data);
    }

    public function testHardcoreUnlock(): void
    {
        $now = Carbon::now()->clone()->subMinutes(5)->startOfSecond();
        Carbon::setTestNow($now);

        /** @var User $author */
        $author = User::factory()->create();
        $game = $this->seedGame(withHash: false);
        $gameHash = GameHash::factory()->create(['game_id' => $game->id, 'md5' => '0123456789abcdeffedcba9876543210']);
        /** @var Achievement $achievement1 */
        $achievement1 = Achievement::factory()->published()->create(['GameID' => $game->ID, 'user_id' => $author->id]);
        /** @var Achievement $achievement2 */
        $achievement2 = Achievement::factory()->published()->create(['GameID' => $game->ID, 'user_id' => $author->id]);
        /** @var Achievement $achievement3 */
        $achievement3 = Achievement::factory()->published()->create(['GameID' => $game->ID, 'user_id' => $author->id]);
        /** @var Achievement $achievement4 */
        $achievement4 = Achievement::factory()->published()->create(['GameID' => $game->ID, 'user_id' => $author->id]);
        /** @var Achievement $achievement5 */
        $achievement5 = Achievement::factory()->published()->create(['GameID' => $game->ID, 'user_id' => $author->id]);
        /** @var Achievement $achievement6 */
        $achievement6 = Achievement::factory()->published()->create(['GameID' => $game->ID, 'user_id' => $author->id]);

        $unlock1Date = $now->clone()->subMinutes(65);
        $this->addHardcoreUnlock($this->user, $achievement1, $unlock1Date, $gameHash);
        $this->addHardcoreUnlock($this->user, $achievement5, $unlock1Date, $gameHash);
        $this->addHardcoreUnlock($this->user, $achievement6, $unlock1Date, $gameHash);

        $playerSession1 = PlayerSession::where([
            'user_id' => $this->user->id,
            'game_id' => $achievement3->game_id,
            'game_hash_id' => $gameHash->id,
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

        $this->get($this->apiUrl('awardachievement', ['a' => $achievement3->ID, 'h' => 1, 'm' => $gameHash->md5, 'v' => $validationHash]))
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
            'game_hash_id' => $gameHash->id,
        ])->orderByDesc('id')->first();
        $this->assertModelExists($playerSession2);

        // game attached
        $playerGame = PlayerGame::where([
            'user_id' => $this->user->id,
            'game_id' => $achievement3->game_id,
            'game_hash_id' => $gameHash->id,
        ])->first();
        $this->assertModelExists($playerGame);
        $this->assertNotNull($playerGame->last_played_at);

        // achievement unlocked
        $playerAchievement = PlayerAchievement::where([
            'user_id' => $this->user->id,
            'achievement_id' => $achievement3->id,
            'player_session_id' => $playerSession2->id,
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

        $this->get($this->apiUrl('awardachievement', ['a' => $achievement3->ID, 'h' => 1, 'm' => $gameHash->md5, 'v' => $validationHash]))
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
        $this->get($this->apiUrl('awardachievement', ['a' => $achievement2->ID, 'h' => 1, 'm' => $gameHash->md5, 'v' => $validationHash]))
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement2->ID,
                'AchievementsRemaining' => 1,
                'Score' => $scoreBefore + $achievement2->Points,
                'SoftcoreScore' => $softcoreScoreBefore,
            ]);

        $validationHash = $this->buildValidationHash($achievement4, $this->user, 1);
        $this->get($this->apiUrl('awardachievement', ['a' => $achievement4->ID, 'h' => 1, 'm' => $gameHash->md5, 'v' => $validationHash]))
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement4->ID,
                'AchievementsRemaining' => 0,
                'Score' => $scoreBefore + $achievement2->Points + $achievement4->Points,
                'SoftcoreScore' => $softcoreScoreBefore,
            ]);

        // verify badge was awarded
        $this->assertNotNull(PlayerBadge::where('user_id', $this->user->id)
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
        /** @var GameHash $gameHash */
        $gameHash = GameHash::factory()->create(['game_id' => $game->id, 'md5' => '0123456789abcdeffedcba9876543210']);
        /** @var Achievement $achievement1 */
        $achievement1 = Achievement::factory()->published()->create(['GameID' => $game->ID, 'user_id' => $author->id]);
        /** @var Achievement $achievement2 */
        $achievement2 = Achievement::factory()->published()->create(['GameID' => $game->ID, 'user_id' => $author->id]);
        /** @var Achievement $achievement3 */
        $achievement3 = Achievement::factory()->published()->create(['GameID' => $game->ID, 'user_id' => $author->id]);
        /** @var Achievement $achievement4 */
        $achievement4 = Achievement::factory()->published()->create(['GameID' => $game->ID, 'user_id' => $author->id]);
        /** @var Achievement $achievement5 */
        $achievement5 = Achievement::factory()->published()->create(['GameID' => $game->ID, 'user_id' => $author->id]);
        /** @var Achievement $achievement6 */
        $achievement6 = Achievement::factory()->published()->create(['GameID' => $game->ID, 'user_id' => $author->id]);

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

        $this->get($this->apiUrl('awardachievement', ['a' => $achievement3->ID, 'h' => 0, 'm' => $gameHash->md5, 'v' => $validationHash]))
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

        $this->get($this->apiUrl('awardachievement', ['a' => $achievement3->ID, 'h' => 0, 'm' => $gameHash->md5, 'v' => $validationHash]))
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

        $this->get($this->apiUrl('awardachievement', ['a' => $achievement3->ID, 'h' => 1, 'm' => $gameHash->md5, 'v' => $validationHash]))
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
        $this->get($this->apiUrl('awardachievement', ['a' => $achievement2->ID, 'h' => 0, 'm' => $gameHash->md5, 'v' => $validationHash]))
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement2->ID,
                'AchievementsRemaining' => 1,
                'Score' => $scoreBefore,
                'SoftcoreScore' => $softcoreScoreBefore + $achievement2->Points,
            ]);

        $validationHash = $this->buildValidationHash($achievement4, $this->user, 0);
        $this->get($this->apiUrl('awardachievement', ['a' => $achievement4->ID, 'h' => 0, 'm' => $gameHash->md5, 'v' => $validationHash]))
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement4->ID,
                'AchievementsRemaining' => 0,
                'Score' => $scoreBefore,
                'SoftcoreScore' => $softcoreScoreBefore + $achievement2->Points + $achievement4->Points,
            ]);

        // verify badge was awarded
        $this->assertNotNull(PlayerBadge::where('user_id', $this->user->id)
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
        $achievement1 = Achievement::factory()->published()->create(['ID' => 1, 'GameID' => $gameOne->ID, 'user_id' => $integrationUser->id]);
        /** @var Achievement $achievement2 */
        $achievement2 = Achievement::factory()->published()->create(['ID' => 2, 'GameID' => $gameOne->ID, 'user_id' => $integrationUser->id]);
        /** @var Achievement $achievement3 */
        $achievement3 = Achievement::factory()->published()->create(['ID' => 3, 'GameID' => $gameOne->ID, 'user_id' => $integrationUser->id]);
        /** @var Achievement $achievement4 */
        $achievement4 = Achievement::factory()->published()->create(['ID' => 4, 'GameID' => $gameOne->ID, 'user_id' => 9999999]);
        /** @var Achievement $achievement5 */
        $achievement5 = Achievement::factory()->published()->create(['ID' => 5, 'GameID' => $gameOne->ID, 'user_id' => $integrationUser->id]);
        /** @var Achievement $achievement6 */
        $achievement6 = Achievement::factory()->published()->create(['ID' => 6, 'GameID' => $gameOne->ID, 'user_id' => $integrationUser->id]);

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

        $achievements = Achievement::factory()->published()->count(6)->create(['GameID' => $gameTwo->id, 'user_id' => $integrationUser->id]);

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

    public function testBackdatedUnlock(): void
    {
        $now = Carbon::now()->clone()->subMinutes(5)->startOfSecond();
        Carbon::setTestNow($now);

        /** @var User $author */
        $author = User::factory()->create();
        $game = $this->seedGame(withHash: false);
        $gameHash = GameHash::factory()->create(['game_id' => $game->id, 'md5' => '0123456789abcdeffedcba9876543210']);
        /** @var Achievement $achievement1 */
        $achievement1 = Achievement::factory()->published()->create(['GameID' => $game->ID, 'user_id' => $author->id]);
        /** @var Achievement $achievement2 */
        $achievement2 = Achievement::factory()->published()->create(['GameID' => $game->ID, 'user_id' => $author->id]);
        /** @var Achievement $achievement3 */
        $achievement3 = Achievement::factory()->published()->create(['GameID' => $game->ID, 'user_id' => $author->id]);
        /** @var Achievement $achievement4 */
        $achievement4 = Achievement::factory()->published()->create(['GameID' => $game->ID, 'user_id' => $author->id]);
        /** @var Achievement $achievement5 */
        $achievement5 = Achievement::factory()->published()->create(['GameID' => $game->ID, 'user_id' => $author->id]);
        /** @var Achievement $achievement6 */
        $achievement6 = Achievement::factory()->published()->create(['GameID' => $game->ID, 'user_id' => $author->id]);

        // for responses to include updated scores, a session must exist
        $unlock1Date = $now->clone()->subMinutes(65);
        $this->addHardcoreUnlock($this->user, $achievement1, $unlock1Date, $gameHash);

        // do the hardcore unlock
        $offset = 30;
        $validationHash = $this->buildValidationHash($achievement3, $this->user, 1, $offset);
        $scoreBefore = $this->user->RAPoints;
        $softcoreScoreBefore = $this->user->RASoftcorePoints;
        $truePointsBefore = $this->user->TrueRAPoints;

        $this->get($this->apiUrl('awardachievement', ['a' => $achievement3->ID, 'h' => 1, 'm' => $gameHash->md5, 'o' => $offset, 'v' => $validationHash]))
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement3->ID,
                'AchievementsRemaining' => 4,
                'Score' => $scoreBefore + $achievement3->Points,
                'SoftcoreScore' => $softcoreScoreBefore,
            ]);
        $scoreBefore += $achievement3->Points;

        // player session created
        $playerSession = PlayerSession::where([
            'user_id' => $this->user->id,
            'game_id' => $achievement3->game_id,
            'game_hash_id' => $gameHash->id,
        ])->orderByDesc('id')->first();
        $this->assertModelExists($playerSession);

        // game attached
        $playerGame = PlayerGame::where([
            'user_id' => $this->user->id,
            'game_id' => $achievement3->game_id,
            'game_hash_id' => $gameHash->id,
        ])->first();
        $this->assertModelExists($playerGame);
        $this->assertNotNull($playerGame->last_played_at);

        // achievement unlocked
        $playerAchievement = PlayerAchievement::where([
            'user_id' => $this->user->id,
            'achievement_id' => $achievement3->id,
            'player_session_id' => $playerSession->id,
        ])->first();
        $this->assertModelExists($playerAchievement);
        $unlockDate = $now->clone()->subSeconds($offset);
        $this->assertEquals($unlockDate, $playerAchievement->unlocked_at);
        $this->assertEquals($unlockDate, $playerAchievement->unlocked_hardcore_at);
        $this->assertEquals($playerAchievement->player_session_id, $playerSession->id);

        // try to unlock another achievement with incorrect validation hash - should succeed, but not be backdated
        $this->get($this->apiUrl('awardachievement', ['a' => $achievement2->ID, 'h' => 1, 'm' => $gameHash->md5, 'o' => $offset, 'v' => 'XXXXXXXXX']))
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement2->ID,
                'AchievementsRemaining' => 3,
                'Score' => $scoreBefore + $achievement2->Points,
                'SoftcoreScore' => $softcoreScoreBefore,
            ]);
        $scoreBefore += $achievement2->Points;

        $playerAchievement2 = PlayerAchievement::where([
            'user_id' => $this->user->id,
            'achievement_id' => $achievement2->id,
            'player_session_id' => $playerSession->id,
        ])->first();
        $this->assertModelExists($playerAchievement2);
        $this->assertEquals($now, $playerAchievement2->unlocked_at);
        $this->assertEquals($now, $playerAchievement2->unlocked_hardcore_at);
        $this->assertEquals($playerAchievement2->player_session_id, $playerSession->id);

        // negative offset is ignored
        $offset = -100;
        $validationHash = $this->buildValidationHash($achievement3, $this->user, 1, $offset);

        $this->get($this->apiUrl('awardachievement', ['a' => $achievement4->ID, 'h' => 1, 'm' => $gameHash->md5, 'o' => $offset, 'v' => $validationHash]))
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement4->ID,
                'AchievementsRemaining' => 2,
                'Score' => $scoreBefore + $achievement4->Points,
                'SoftcoreScore' => $softcoreScoreBefore,
            ]);
        $scoreBefore += $achievement4->Points;

        $playerAchievement3 = PlayerAchievement::where([
            'user_id' => $this->user->id,
            'achievement_id' => $achievement4->id,
            'player_session_id' => $playerSession->id,
        ])->first();
        $this->assertModelExists($playerAchievement3);
        $this->assertEquals($now, $playerAchievement3->unlocked_at);
        $this->assertEquals($now, $playerAchievement3->unlocked_hardcore_at);
        $this->assertEquals($playerAchievement3->player_session_id, $playerSession->id);

        // very large offset (30 days) is ignored
        $offset = 30 * 25 * 60 * 60;
        $validationHash = $this->buildValidationHash($achievement5, $this->user, 1, $offset);

        $this->get($this->apiUrl('awardachievement', ['a' => $achievement5->ID, 'h' => 1, 'm' => $gameHash->md5, 'o' => $offset, 'v' => $validationHash]))
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement5->ID,
                'AchievementsRemaining' => 1,
                'Score' => $scoreBefore + $achievement5->Points,
                'SoftcoreScore' => $softcoreScoreBefore,
            ]);
        $scoreBefore += $achievement5->Points;

        $playerAchievement4 = PlayerAchievement::where([
            'user_id' => $this->user->id,
            'achievement_id' => $achievement5->id,
            'player_session_id' => $playerSession->id,
        ])->first();
        $this->assertModelExists($playerAchievement4);
        $this->assertEquals($now, $playerAchievement4->unlocked_at);
        $this->assertEquals($now, $playerAchievement4->unlocked_hardcore_at);
        $this->assertEquals($playerAchievement4->player_session_id, $playerSession->id);
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
        /** @var GameHash $gameHash */
        $gameHash = GameHash::factory()->create(['game_id' => $game->id, 'md5' => '0123456789abcdeffedcba9876543210']);
        /** @var Achievement $achievement1 */
        $achievement1 = Achievement::factory()->published()->create(['GameID' => $game->ID, 'user_id' => $author->id]);
        /** @var Achievement $achievement2 */
        $achievement2 = Achievement::factory()->published()->create(['GameID' => $game->ID, 'user_id' => $author->id]);
        /** @var Achievement $achievement3 */
        $achievement3 = Achievement::factory()->published()->create(['GameID' => $game->ID, 'user_id' => $author->id]);
        /** @var Achievement $achievement4 */
        $achievement4 = Achievement::factory()->create(['GameID' => $game->ID, 'user_id' => $author->id]);

        $unlock1Date = $now->clone()->subMinutes(65);
        $this->addHardcoreUnlock($this->user, $achievement1, $unlock1Date);

        $scoreBefore = $this->user->RAPoints;
        $softcoreScoreBefore = $this->user->RASoftcorePoints;

        $validationHash = md5('999999' . $this->user->User . '1');
        $this->get($this->apiUrl('awardachievement', ['a' => 999999, 'h' => 1, 'm' => $gameHash->md5, 'v' => $validationHash]))
            ->assertExactJson([
                'Success' => false,
                'Error' => 'Data not found for achievement 999999',
                'AchievementID' => 999999,
                'Score' => $scoreBefore,
                'SoftcoreScore' => $softcoreScoreBefore,
            ]);

        $validationHash = $this->buildValidationHash($achievement4, $this->user, 1);
        $this->get($this->apiUrl('awardachievement', ['a' => $achievement4->ID, 'h' => 1, 'm' => $gameHash->md5, 'v' => $validationHash]))
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

    public function testHardcoreEventUnlock(): void
    {
        $now = Carbon::now()->clone()->subMinutes(5)->startOfSecond();
        Carbon::setTestNow($now);

        /** @var User $author */
        $author = User::factory()->create();
        $game = $this->seedGame(withHash: false);
        $gameHash = GameHash::factory()->create(['game_id' => $game->id, 'md5' => '0123456789abcdeffedcba9876543210']);
        /** @var Achievement $achievement1 */
        $achievement1 = Achievement::factory()->published()->create(['GameID' => $game->ID, 'user_id' => $author->id]);
        /** @var Achievement $achievement2 */
        $achievement2 = Achievement::factory()->published()->create(['GameID' => $game->ID, 'user_id' => $author->id]);
        /** @var Achievement $achievement3 */
        $achievement3 = Achievement::factory()->published()->create(['GameID' => $game->ID, 'user_id' => $author->id]);
        /** @var Achievement $achievement4 */
        $achievement4 = Achievement::factory()->published()->create(['GameID' => $game->ID, 'user_id' => $author->id]);
        /** @var Achievement $achievement5 */
        $achievement5 = Achievement::factory()->published()->create(['GameID' => $game->ID, 'user_id' => $author->id]);
        /** @var Achievement $achievement6 */
        $achievement6 = Achievement::factory()->published()->create(['GameID' => $game->ID, 'user_id' => $author->id]);

        $unlock1Date = $now->clone()->subMinutes(65);
        $this->addHardcoreUnlock($this->user, $achievement1, $unlock1Date, $gameHash);
        $this->addHardcoreUnlock($this->user, $achievement5, $unlock1Date, $gameHash);
        $this->addHardcoreUnlock($this->user, $achievement6, $unlock1Date, $gameHash);

        $playerSession1 = PlayerSession::where([
            'user_id' => $this->user->id,
            'game_id' => $achievement3->game_id,
            'game_hash_id' => $gameHash->id,
        ])->orderByDesc('id')->first();
        $this->assertModelExists($playerSession1);

        // not-unlocked event achievement hides hardcore unlock when active
        System::factory()->create(['ID' => System::Events]);
        /** @var Game $eventGame */
        $eventGame = Game::factory()->create(['ConsoleID' => System::Events]);
        /** @var Achievement $eventAchievement1 */
        $eventAchievement1 = Achievement::factory()->published()->create(['GameID' => $eventGame->ID]);
        EventAchievement::create([
            'achievement_id' => $eventAchievement1->ID,
            'source_achievement_id' => $achievement1->ID,
            'active_from' => $now->clone()->subDays(1),
            'active_until' => $now->clone()->addDays(2),
        ]);

        // do the hardcore unlock
        $validationHash = $this->buildValidationHash($achievement1, $this->user, 1);
        $scoreBefore = $this->user->RAPoints;
        $softcoreScoreBefore = $this->user->RASoftcorePoints;
        $truePointsBefore = $this->user->TrueRAPoints;

        $this->get($this->apiUrl('awardachievement', ['a' => $achievement1->ID, 'h' => 1, 'm' => $gameHash->md5, 'v' => $validationHash]))
            ->assertExactJson([
                // client assumes success - it will blindly dispatch the event unlock
                'Success' => true,
                // client detects the "User already has" and does not report this as an error.
                'Error' => 'User already has this achievement unlocked in hardcore mode.',
                'AchievementID' => $achievement1->ID,
                'AchievementsRemaining' => 3,
                'Score' => $scoreBefore,
                'SoftcoreScore' => $softcoreScoreBefore,
            ]);
        $this->user->refresh();

        // achievement unlocked
        $this->assertHasHardcoreUnlock($this->user, $eventAchievement1);

        // player score should not have increased
        $user1 = User::firstWhere('User', $this->user->User);
        $this->assertEquals($scoreBefore, $user1->RAPoints);
        $this->assertEquals($softcoreScoreBefore, $user1->RASoftcorePoints);

        /** @var Achievement $eventAchievement2 */
        $eventAchievement2 = Achievement::factory()->published()->create(['GameID' => $eventGame->ID]);
        EventAchievement::create([
            'achievement_id' => $eventAchievement2->ID,
            'source_achievement_id' => $achievement2->ID,
            'active_from' => $now->clone()->subDays(1),
            'active_until' => $now->clone()->addDays(2),
        ]);

        // softcore unlock (user has neither event achievement nor source achievement unlocked)
        $validationHash = $this->buildValidationHash($achievement2, $this->user, 0);
        $scoreBefore = $this->user->RAPoints;
        $softcoreScoreBefore = $this->user->RASoftcorePoints;
        $truePointsBefore = $this->user->TrueRAPoints;

        $this->get($this->apiUrl('awardachievement', ['a' => $achievement2->ID, 'h' => 0, 'm' => $gameHash->md5, 'v' => $validationHash]))
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement2->ID,
                'AchievementsRemaining' => 2,
                'Score' => $scoreBefore,
                'SoftcoreScore' => $softcoreScoreBefore + $achievement2->Points,
            ]);

        // player score updated
        $this->user->refresh();
        $this->assertEquals($scoreBefore, $this->user->RAPoints);
        $this->assertEquals($softcoreScoreBefore + $achievement2->Points, $this->user->RASoftcorePoints);
        $softcoreScoreBefore = $this->user->RASoftcorePoints;

        // event achievement not unlocked
        $this->assertDoesNotHaveAnyUnlock($this->user, $eventAchievement2);

        // do the hardcore unlock
        $validationHash = $this->buildValidationHash($achievement2, $this->user, 1);

        $this->get($this->apiUrl('awardachievement', ['a' => $achievement2->ID, 'h' => 1, 'm' => $gameHash->md5, 'v' => $validationHash]))
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement2->ID,
                'AchievementsRemaining' => 2,
                'Score' => $scoreBefore + $achievement2->Points,
                'SoftcoreScore' => $softcoreScoreBefore - $achievement2->Points,
            ]);

        // player score updated
        $this->user->refresh();
        $this->assertEquals($scoreBefore + $achievement2->Points, $this->user->RAPoints);
        $this->assertEquals($softcoreScoreBefore - $achievement2->Points, $this->user->RASoftcorePoints);
        $scoreBefore = $this->user->RAPoints;
        $softcoreScoreBefore = $this->user->RASoftcorePoints;

        // achievement unlocked
        $this->assertHasHardcoreUnlock($this->user, $eventAchievement2);

        /** @var Achievement $eventAchievement3 */
        $eventAchievement3 = Achievement::factory()->published()->create(['GameID' => $eventGame->ID]);
        EventAchievement::create([
            'achievement_id' => $eventAchievement3->ID,
            'source_achievement_id' => $achievement3->ID,
            'active_from' => $now->clone()->subDays(1),
            'active_until' => $now->clone()->addDays(2),
        ]);

        // hardcore unlock while untracked doesn't unlock event achievement
        $this->user->unranked_at = Carbon::now();
        $this->user->save();

        // do the hardcore unlock
        $validationHash = $this->buildValidationHash($achievement3, $this->user, 1);

        $this->get($this->apiUrl('awardachievement', ['a' => $achievement3->ID, 'h' => 1, 'm' => $gameHash->md5, 'v' => $validationHash]))
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement3->ID,
                'AchievementsRemaining' => 1,
                'Score' => $scoreBefore + $achievement3->Points,
                'SoftcoreScore' => $softcoreScoreBefore,
            ]);

        // achievement unlocked, but not event achievement
        $this->assertHasHardcoreUnlock($this->user, $achievement3);
        $this->assertDoesNotHaveAnyUnlock($this->user, $eventAchievement3);
    }

    public function testUserAgentHardcore(): void
    {
        $now = Carbon::now()->clone()->subMinutes(5)->startOfSecond();
        Carbon::setTestNow($now);

        $game = $this->seedGame();
        $md5 = $game->hashes()->first()->md5;
        $achievement1 = $this->seedAchievement($game);
        $achievement2 = $this->seedAchievement($game);
        $achievement3 = $this->seedAchievement($game);
        $achievement4 = $this->seedAchievement($game);
        $achievement5 = $this->seedAchievement($game);
        $achievement6 = $this->seedAchievement($game);
        $achievement7 = $this->seedAchievement($game);

        $this->seedEmulatorUserAgents();

        // force an achievement unlock to reconstruct the user state (primarily his points)
        (new UnlockPlayerAchievementAction())->execute($this->user, $achievement6, true);
        $this->user->refresh();
        $scoreBefore = $this->user->RAPoints;

        // no user agent (TODO: will return failure in the future)
        $validationHash = $this->buildValidationHash($achievement1, $this->user, 1);
        $this->get($this->apiUrl('awardachievement', ['a' => $achievement1->ID, 'h' => 1, 'm' => $md5, 'v' => $validationHash]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement1->ID,
                'AchievementsRemaining' => 5,
                'Score' => $scoreBefore + $achievement1->Points,
                'SoftcoreScore' => $this->user->RASoftcorePoints,
            ]);
        $scoreBefore += $achievement1->Points;

        // unknown user agent (TODO: will return failure in the future)
        $validationHash = $this->buildValidationHash($achievement2, $this->user, 1);
        $this->withHeaders(['User-Agent' => $this->userAgentUnknown])
            ->get($this->apiUrl('awardachievement', ['a' => $achievement2->ID, 'h' => 1, 'm' => $md5, 'v' => $validationHash]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement2->ID,
                'AchievementsRemaining' => 4,
                'Score' => $scoreBefore + $achievement2->Points,
                'SoftcoreScore' => $this->user->RASoftcorePoints,
            ]);
        $scoreBefore += $achievement2->Points;

        // outdated user agent (TODO: will return failure in the future)
        $validationHash = $this->buildValidationHash($achievement3, $this->user, 1);
        $this->withHeaders(['User-Agent' => $this->userAgentOutdated])
            ->get($this->apiUrl('awardachievement', ['a' => $achievement3->ID, 'h' => 1, 'm' => $md5, 'v' => $validationHash]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement3->ID,
                'AchievementsRemaining' => 3,
                'Score' => $scoreBefore + $achievement3->Points,
                'SoftcoreScore' => $this->user->RASoftcorePoints,
            ]);
        $scoreBefore += $achievement3->Points;

        // unsupported user agent (TODO: will return failure in the future)
        $validationHash = $this->buildValidationHash($achievement7, $this->user, 1);
        $this->withHeaders(['User-Agent' => $this->userAgentUnsupported])
            ->get($this->apiUrl('awardachievement', ['a' => $achievement7->ID, 'h' => 1, 'm' => $md5, 'v' => $validationHash]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement7->ID,
                'AchievementsRemaining' => 2,
                'Score' => $scoreBefore + $achievement7->Points,
                'SoftcoreScore' => $this->user->RASoftcorePoints,
            ]);
        $scoreBefore += $achievement7->Points;

        // valid user agent
        $validationHash = $this->buildValidationHash($achievement4, $this->user, 1);
        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('awardachievement', ['a' => $achievement4->ID, 'h' => 1, 'm' => $md5, 'v' => $validationHash]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement4->ID,
                'AchievementsRemaining' => 1,
                'Score' => $scoreBefore + $achievement4->Points,
                'SoftcoreScore' => $this->user->RASoftcorePoints,
            ]);
        $scoreBefore += $achievement4->Points;

        // blocked user agent
        $validationHash = $this->buildValidationHash($achievement5, $this->user, 1);
        $this->withHeaders(['User-Agent' => $this->userAgentBlocked])
            ->get($this->apiUrl('awardachievement', ['a' => $achievement5->ID, 'h' => 1, 'm' => $md5, 'v' => $validationHash]))
            ->assertStatus(403)
            ->assertExactJson([
                'Status' => 403,
                'Success' => false,
                'Error' => 'This emulator is not supported',
            ]);
    }

    public function testUserAgentSoftcore(): void
    {
        $now = Carbon::now()->clone()->subMinutes(5)->startOfSecond();
        Carbon::setTestNow($now);

        $game = $this->seedGame();
        $md5 = $game->hashes()->first()->md5;
        $achievement1 = $this->seedAchievement($game);
        $achievement2 = $this->seedAchievement($game);
        $achievement3 = $this->seedAchievement($game);
        $achievement4 = $this->seedAchievement($game);
        $achievement5 = $this->seedAchievement($game);
        $achievement6 = $this->seedAchievement($game);
        $achievement7 = $this->seedAchievement($game);

        $this->seedEmulatorUserAgents();

        // force an achievement unlock to reconstruct the user state (primarily his points)
        (new UnlockPlayerAchievementAction())->execute($this->user, $achievement6, false);
        $this->user->refresh();
        $scoreBefore = $this->user->RASoftcorePoints;

        // no user agent
        $validationHash = $this->buildValidationHash($achievement1, $this->user, 0);
        $this->get($this->apiUrl('awardachievement', ['a' => $achievement1->ID, 'h' => 0, 'm' => $md5, 'v' => $validationHash]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement1->ID,
                'AchievementsRemaining' => 5,
                'Score' => $this->user->RAPoints,
                'SoftcoreScore' => $scoreBefore + $achievement1->Points,
            ]);
        $scoreBefore += $achievement1->Points;

        // unknown user agent
        $validationHash = $this->buildValidationHash($achievement2, $this->user, 0);
        $this->withHeaders(['User-Agent' => $this->userAgentUnknown])
            ->get($this->apiUrl('awardachievement', ['a' => $achievement2->ID, 'h' => 0, 'm' => $md5, 'v' => $validationHash]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement2->ID,
                'AchievementsRemaining' => 4,
                'Score' => $this->user->RAPoints,
                'SoftcoreScore' => $scoreBefore + $achievement2->Points,
            ]);
        $scoreBefore += $achievement2->Points;

        // outdated user agent
        $validationHash = $this->buildValidationHash($achievement3, $this->user, 0);
        $this->withHeaders(['User-Agent' => $this->userAgentOutdated])
            ->get($this->apiUrl('awardachievement', ['a' => $achievement3->ID, 'h' => 0, 'm' => $md5, 'v' => $validationHash]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement3->ID,
                'AchievementsRemaining' => 3,
                'Score' => $this->user->RAPoints,
                'SoftcoreScore' => $scoreBefore + $achievement3->Points,
            ]);
        $scoreBefore += $achievement3->Points;

        // unsupported user agent
        $validationHash = $this->buildValidationHash($achievement7, $this->user, 1);
        $this->withHeaders(['User-Agent' => $this->userAgentUnsupported])
            ->get($this->apiUrl('awardachievement', ['a' => $achievement7->ID, 'h' => 0, 'm' => $md5, 'v' => $validationHash]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement7->ID,
                'AchievementsRemaining' => 2,
                'Score' => $this->user->RAPoints,
                'SoftcoreScore' => $scoreBefore + $achievement7->Points,
            ]);
        $scoreBefore += $achievement7->Points;

        // valid user agent
        $validationHash = $this->buildValidationHash($achievement4, $this->user, 0);
        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('awardachievement', ['a' => $achievement4->ID, 'h' => 0, 'm' => $md5, 'v' => $validationHash]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement4->ID,
                'AchievementsRemaining' => 1,
                'Score' => $this->user->RAPoints,
                'SoftcoreScore' => $scoreBefore + $achievement4->Points,
            ]);
        $scoreBefore += $achievement4->Points;

        // blocked user agent
        $validationHash = $this->buildValidationHash($achievement5, $this->user, 0);
        $this->withHeaders(['User-Agent' => $this->userAgentBlocked])
            ->get($this->apiUrl('awardachievement', ['a' => $achievement5->ID, 'h' => 0, 'm' => $md5, 'v' => $validationHash]))
            ->assertStatus(403)
            ->assertExactJson([
                'Status' => 403,
                'Success' => false,
                'Error' => 'This emulator is not supported',
            ]);
    }

    public function testUnlockWarningAchievement(): void
    {
        // requesting an unlock for the warning achievement should return success without actually unlocking it
        $this->get($this->apiUrl('awardachievement', ['a' => Achievement::CLIENT_WARNING_ID, 'h' => 1]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => Achievement::CLIENT_WARNING_ID,
                'AchievementsRemaining' => 9999,
                'Score' => $this->user->RAPoints,
                'SoftcoreScore' => $this->user->RASoftcorePoints,
            ]);

        $this->assertFalse(
            $this->user->playerAchievements()->where('achievement_id', Achievement::CLIENT_WARNING_ID)->exists(),
            'Found unlock for warning achievement'
        );
    }
}
