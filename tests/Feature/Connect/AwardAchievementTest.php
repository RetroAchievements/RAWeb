<?php

declare(strict_types=1);

namespace Tests\Feature\Connect;

use App\Community\Enums\AwardType;
use App\Enums\Permissions;
use App\Models\Achievement;
use App\Models\ConnectWarning;
use App\Models\Emulator;
use App\Models\EmulatorUserAgent;
use App\Models\EventAchievement;
use App\Models\Game;
use App\Models\GameHash;
use App\Models\PlayerAchievement;
use App\Models\PlayerBadge;
use App\Models\PlayerGame;
use App\Models\PlayerSession;
use App\Models\System;
use App\Models\User;
use App\Platform\Enums\UnlockMode;
use App\Support\Alerts\Jobs\SendAlertWebhookJob;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\Feature\Concerns\TestsEmulatorUserAgent;
use Tests\Feature\Platform\Concerns\TestsPlayerAchievements;

uses(LazilyRefreshDatabase::class);
uses(TestsConnect::class);
uses(TestsEmulatorUserAgent::class);
uses(TestsPlayerAchievements::class);

class AwardAchievementTestHelpers
{
    public static function buildValidationHash(Achievement $achievement, User $user, int $hardcore, int $offset = 0): string
    {
        $data = $achievement->id . $user->username . $hardcore;
        if ($offset > 0) {
            $data .= "{$achievement->id}{$offset}";
        }

        return md5($data);
    }

    public static function createGame(): array
    {
        /** @var User $author */
        $author = User::factory()->create();
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);
        $gameHash = GameHash::factory()->create(['game_id' => $game->id, 'md5' => '0123456789abcdeffedcba9876543210']);
        /** @var Achievement $achievement1 */
        $achievement1 = Achievement::factory()->promoted()->create(['game_id' => $game->id, 'user_id' => $author->id]);
        /** @var Achievement $achievement2 */
        $achievement2 = Achievement::factory()->promoted()->create(['game_id' => $game->id, 'user_id' => $author->id]);
        /** @var Achievement $achievement3 */
        $achievement3 = Achievement::factory()->promoted()->create(['game_id' => $game->id, 'user_id' => $author->id]);
        /** @var Achievement $achievement4 */
        $achievement4 = Achievement::factory()->promoted()->create(['game_id' => $game->id, 'user_id' => $author->id]);
        /** @var Achievement $achievement5 */
        $achievement5 = Achievement::factory()->promoted()->create(['game_id' => $game->id, 'user_id' => $author->id]);
        /** @var Achievement $achievement6 */
        $achievement6 = Achievement::factory()->promoted()->create(['game_id' => $game->id, 'user_id' => $author->id]);

        return [
            'author' => $author,
            'game' => $game,
            'gameHash' => $gameHash,
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

    public static function createEventAchievement(Achievement $sourceAchievement, ?Carbon $now = null): Achievement
    {
        if ($now === null) {
            $now = Carbon::now();
        }

        System::factory()->create(['id' => System::Events]);
        /** @var Game $eventGame */
        $eventGame = Game::factory()->create(['system_id' => System::Events]);
        /** @var Achievement $eventAchievement */
        $eventAchievement = Achievement::factory()->promoted()->create(['game_id' => $eventGame->id]);
        EventAchievement::create([
            'achievement_id' => $eventAchievement->id,
            'source_achievement_id' => $sourceAchievement->id,
            'active_from' => $now->clone()->subDays(1),
            'active_until' => $now->clone()->addDays(2),
        ]);

        return $eventAchievement;
    }

    public static function getWarning(Achievement $achievement): ConnectWarning
    {
        return ConnectWarning::query()
            ->where('method', 'awardachievement')
            ->where('related_type', 'achievement')
            ->where('related_id', $achievement->id)
            ->whereNull('extra')
            ->first();
    }

    public static function initializeFakeQueue(): void
    {
        Queue::fake([SendAlertWebhookJob::class]);
        config(['services.discord.alerts_webhook.suspicious_connect_warning' => 'https://discord.com/api/webhooks/test']);
    }
}

beforeEach(function () {
    Carbon::setTestNow(Carbon::now()->startOfSecond());

    $this->seedEmulatorUserAgents();
    $this->createConnectUser();
});

describe('normal unlock', function () {
    test('new hardcore unlock', function () {
        $data = AwardAchievementTestHelpers::createGame();
        $game = $data['game'];
        $achievement1 = $data['achievements'][0];
        $achievement3 = $data['achievements'][2];
        $achievement5 = $data['achievements'][4];
        $achievement6 = $data['achievements'][5];
        $gameHash = $data['gameHash'];
        $now = Carbon::now();

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

        // cache the unlocks for the game
        $unlocks = getUserAchievementUnlocksForGame($this->user->username, $game->id);
        $this->assertEquals([$achievement1->id, $achievement5->id, $achievement6->id], array_keys($unlocks));

        // do the hardcore unlock
        $validationHash = AwardAchievementTestHelpers::buildValidationHash($achievement3, $this->user, 1);
        $scoreBefore = $this->user->points_hardcore;
        $casualScoreBefore = $this->user->points;
        $truePointsBefore = $this->user->points_weighted;

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('awardachievement', [
                'a' => $achievement3->id,
                'h' => 1,
                'm' => $gameHash->md5,
                'v' => $validationHash,
            ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement3->id,
                'AchievementsRemaining' => 2,
                'Score' => $scoreBefore + $achievement3->points,
                'SoftcoreScore' => $casualScoreBefore,
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
        $user1 = User::whereName($this->user->username)->first();
        $this->assertEquals($scoreBefore + $achievement3->points, $user1->points_hardcore);
        $this->assertEquals($casualScoreBefore, $user1->points);

        // make sure the unlock cache was updated
        $unlocks = getUserAchievementUnlocksForGame($this->user->username, $game->id);
        $this->assertEqualsCanonicalizing([$achievement1->id, $achievement5->id, $achievement6->id, $achievement3->id], array_keys($unlocks));
        $this->assertEquals($now, $unlocks[$achievement3->id]['DateEarnedHardcore']);
        $this->assertEquals($now, $unlocks[$achievement3->id]['DateEarned']);

        $this->assertEquals(0, ConnectWarning::count());
    });

    test('repeated hardcore unlock', function () {
        $data = AwardAchievementTestHelpers::createGame();
        $game = $data['game'];
        $achievement1 = $data['achievements'][0];
        $achievement5 = $data['achievements'][4];
        $achievement6 = $data['achievements'][5];
        $gameHash = $data['gameHash'];
        $now = Carbon::now();

        $unlock1Date = $now->clone()->subMinutes(65);
        $this->addHardcoreUnlock($this->user, $achievement1, $unlock1Date, $gameHash);
        $this->addHardcoreUnlock($this->user, $achievement5, $unlock1Date, $gameHash);
        $this->addHardcoreUnlock($this->user, $achievement6, $unlock1Date, $gameHash);

        $playerSession1 = PlayerSession::where([
            'user_id' => $this->user->id,
            'game_id' => $achievement1->game_id,
            'game_hash_id' => $gameHash->id,
        ])->orderByDesc('id')->first();
        $this->assertModelExists($playerSession1);

        // cache the unlocks for the game
        $unlocks = getUserAchievementUnlocksForGame($this->user->username, $game->id);
        $this->assertEquals([$achievement1->id, $achievement5->id, $achievement6->id], array_keys($unlocks));

        $newNow = $now->clone()->addMinutes(5);
        Carbon::setTestNow($newNow);
        $this->assertNotEquals($now, Carbon::now());

        // do the hardcore unlock
        $validationHash = AwardAchievementTestHelpers::buildValidationHash($achievement1, $this->user, 1);
        $scoreBefore = $this->user->points_hardcore;
        $casualScoreBefore = $this->user->points;
        $truePointsBefore = $this->user->points_weighted;

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('awardachievement', [
                'a' => $achievement1->id,
                'h' => 1,
                'm' => $gameHash->md5,
                'v' => $validationHash,
            ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => false,
                // client detects the "User already has" and does not report this as an error.
                'Error' => 'User already has this achievement unlocked in hardcore mode.',
                'AchievementID' => $achievement1->id,
                'AchievementsRemaining' => 3,
                'Score' => $scoreBefore,
                'SoftcoreScore' => $casualScoreBefore,
            ]);

        // player score should not have increased
        $user2 = User::whereName($this->user->username)->first();
        $this->assertEquals($scoreBefore, $user2->points_hardcore);
        $this->assertEquals($casualScoreBefore, $user2->points);
        $this->assertEquals($truePointsBefore, $user2->points_weighted);

        // make sure the unlock time didn't change
        $unlockTime = $this->getUnlockTime($user2, $achievement1, UnlockMode::Hardcore);
        $this->assertEquals($unlock1Date, $unlockTime);
        $unlockTime = $this->getUnlockTime($user2, $achievement1, UnlockMode::Casual);
        $this->assertEquals($unlock1Date, $unlockTime);

        $this->assertEquals(0, ConnectWarning::count());
    });

    test('final hardcore unlock awards badge', function () {
        $data = AwardAchievementTestHelpers::createGame();
        $game = $data['game'];
        $achievement1 = $data['achievements'][0];
        $achievement2 = $data['achievements'][1];
        $achievement3 = $data['achievements'][2];
        $achievement4 = $data['achievements'][3];
        $achievement5 = $data['achievements'][4];
        $achievement6 = $data['achievements'][5];
        $gameHash = $data['gameHash'];
        $now = Carbon::now();

        $unlock1Date = $now->clone()->subMinutes(65);
        $this->addHardcoreUnlock($this->user, $achievement1, $unlock1Date, $gameHash);
        $this->addHardcoreUnlock($this->user, $achievement3, $unlock1Date, $gameHash);
        $this->addHardcoreUnlock($this->user, $achievement4, $unlock1Date, $gameHash);
        $this->addHardcoreUnlock($this->user, $achievement5, $unlock1Date, $gameHash);
        $this->addHardcoreUnlock($this->user, $achievement6, $unlock1Date, $gameHash);

        $playerSession1 = PlayerSession::where([
            'user_id' => $this->user->id,
            'game_id' => $achievement4->game_id,
            'game_hash_id' => $gameHash->id,
        ])->orderByDesc('id')->first();
        $this->assertModelExists($playerSession1);

        // cache the unlocks for the game
        $unlocks = getUserAchievementUnlocksForGame($this->user->username, $game->id);
        $this->assertEquals([$achievement1->id, $achievement3->id, $achievement4->id, $achievement5->id, $achievement6->id], array_keys($unlocks));

        $newNow = $now->clone()->addMinutes(5);
        Carbon::setTestNow($newNow);
        $this->assertNotEquals($now, Carbon::now());

        // do the hardcore unlock
        $validationHash = AwardAchievementTestHelpers::buildValidationHash($achievement2, $this->user, 1);
        $scoreBefore = $this->user->points_hardcore;
        $casualScoreBefore = $this->user->points;
        $truePointsBefore = $this->user->points_weighted;

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('awardachievement', [
                'a' => $achievement2->id,
                'h' => 1,
                'm' => $gameHash->md5,
                'v' => $validationHash,
            ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement2->id,
                'AchievementsRemaining' => 0,
                'Score' => $scoreBefore + $achievement2->points,
                'SoftcoreScore' => $casualScoreBefore,
            ]);

        // player score should have increased
        $user1 = User::whereName($this->user->username)->first();
        $this->assertEquals($scoreBefore + $achievement2->points, $user1->points_hardcore);
        $this->assertEquals($casualScoreBefore, $user1->points);

        // make sure the unlock cache was updated
        $unlocks = getUserAchievementUnlocksForGame($this->user->username, $game->id);
        $this->assertEqualsCanonicalizing([$achievement1->id, $achievement3->id, $achievement4->id, $achievement5->id, $achievement6->id, $achievement2->id], array_keys($unlocks));
        $this->assertEquals($newNow, $unlocks[$achievement2->id]['DateEarnedHardcore']);
        $this->assertEquals($newNow, $unlocks[$achievement2->id]['DateEarned']);

        // verify badge was awarded
        $this->assertNotNull(PlayerBadge::where('user_id', $this->user->id)
            ->where('award_type', AwardType::Mastery)
            ->where('award_key', $game->id)
            ->where('award_tier', UnlockMode::Hardcore)
            ->where('awarded_at', $newNow)
            ->first()
        );

        $this->assertEquals(0, ConnectWarning::count());
    });

    test('new backdated hardcore unlock', function () {
        $data = AwardAchievementTestHelpers::createGame();
        $game = $data['game'];
        $achievement1 = $data['achievements'][0];
        $achievement3 = $data['achievements'][2];
        $achievement5 = $data['achievements'][4];
        $achievement6 = $data['achievements'][5];
        $gameHash = $data['gameHash'];
        $now = Carbon::now();

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

        // cache the unlocks for the game
        $unlocks = getUserAchievementUnlocksForGame($this->user->username, $game->id);
        $this->assertEquals([$achievement1->id, $achievement5->id, $achievement6->id], array_keys($unlocks));

        // do the hardcore unlock
        $offset = 30;
        $validationHash = AwardAchievementTestHelpers::buildValidationHash($achievement3, $this->user, 1, $offset);
        $scoreBefore = $this->user->points_hardcore;
        $casualScoreBefore = $this->user->points;
        $truePointsBefore = $this->user->points_weighted;

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('awardachievement', [
                'a' => $achievement3->id,
                'h' => 1,
                'm' => $gameHash->md5,
                'o' => $offset,
                'v' => $validationHash,
            ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement3->id,
                'AchievementsRemaining' => 2,
                'Score' => $scoreBefore + $achievement3->points,
                'SoftcoreScore' => $casualScoreBefore,
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
        ])->first();
        $this->assertModelExists($playerGame);
        $this->assertNotNull($playerGame->last_played_at);

        // achievement unlocked
        $playerAchievement = PlayerAchievement::where([
            'user_id' => $this->user->id,
            'achievement_id' => $achievement3->id,
            'player_session_id' => $playerSession2->id,
        ])->first();
        $unlockDate = $now->clone()->subSeconds($offset);
        $this->assertModelExists($playerAchievement);
        $this->assertEquals($unlockDate, $playerAchievement->unlocked_at);
        $this->assertEquals($unlockDate, $playerAchievement->unlocked_hardcore_at);
        $this->assertEquals($playerAchievement->player_session_id, $playerSession2->id);

        // player score should have increased
        $user1 = User::whereName($this->user->username)->first();
        $this->assertEquals($scoreBefore + $achievement3->points, $user1->points_hardcore);
        $this->assertEquals($casualScoreBefore, $user1->points);

        // make sure the unlock cache was updated
        $unlocks = getUserAchievementUnlocksForGame($this->user->username, $game->id);
        $this->assertEqualsCanonicalizing([$achievement1->id, $achievement5->id, $achievement6->id, $achievement3->id], array_keys($unlocks));
        $this->assertEquals($unlockDate, $unlocks[$achievement3->id]['DateEarnedHardcore']);
        $this->assertEquals($unlockDate, $unlocks[$achievement3->id]['DateEarned']);

        $this->assertEquals(0, ConnectWarning::count());
    });

    test('backdated hardcore unlock with invalid validation hash is not backdated', function () {
        $data = AwardAchievementTestHelpers::createGame();
        $game = $data['game'];
        $achievement1 = $data['achievements'][0];
        $achievement3 = $data['achievements'][2];
        $achievement5 = $data['achievements'][4];
        $achievement6 = $data['achievements'][5];
        $gameHash = $data['gameHash'];
        $now = Carbon::now();

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

        // cache the unlocks for the game
        $unlocks = getUserAchievementUnlocksForGame($this->user->username, $game->id);
        $this->assertEquals([$achievement1->id, $achievement5->id, $achievement6->id], array_keys($unlocks));

        // do the hardcore unlock
        $offset = 30;
        $validationHash = AwardAchievementTestHelpers::buildValidationHash($achievement3, $this->user, 1);
        $scoreBefore = $this->user->points_hardcore;
        $casualScoreBefore = $this->user->points;
        $truePointsBefore = $this->user->points_weighted;

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('awardachievement', [
                'a' => $achievement3->id,
                'h' => 1,
                'm' => $gameHash->md5,
                'o' => $offset,
                'v' => $validationHash,
            ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement3->id,
                'AchievementsRemaining' => 2,
                'Score' => $scoreBefore + $achievement3->points,
                'SoftcoreScore' => $casualScoreBefore,
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
        ])->first();
        $this->assertModelExists($playerGame);
        $this->assertNotNull($playerGame->last_played_at);

        // achievement unlocked
        $playerAchievement = PlayerAchievement::where([
            'user_id' => $this->user->id,
            'achievement_id' => $achievement3->id,
            'player_session_id' => $playerSession2->id,
        ])->first();
        $unlockDate = $now; // not backdated
        $this->assertModelExists($playerAchievement);
        $this->assertEquals($unlockDate, $playerAchievement->unlocked_at);
        $this->assertEquals($unlockDate, $playerAchievement->unlocked_hardcore_at);
        $this->assertEquals($playerAchievement->player_session_id, $playerSession2->id);

        // player score should have increased
        $user1 = User::whereName($this->user->username)->first();
        $this->assertEquals($scoreBefore + $achievement3->points, $user1->points_hardcore);
        $this->assertEquals($casualScoreBefore, $user1->points);

        // make sure the unlock cache was updated
        $unlocks = getUserAchievementUnlocksForGame($this->user->username, $game->id);
        $this->assertEqualsCanonicalizing([$achievement1->id, $achievement5->id, $achievement6->id, $achievement3->id], array_keys($unlocks));
        $this->assertEquals($unlockDate, $unlocks[$achievement3->id]['DateEarnedHardcore']);
        $this->assertEquals($unlockDate, $unlocks[$achievement3->id]['DateEarned']);

        $warning = AwardAchievementTestHelpers::getWarning($achievement3);
        $this->assertEquals($this->user->username, $warning->username);
        $this->assertEquals($offset, $warning->offset);
        $this->assertEquals($validationHash, $warning->validation_hash);
        $this->assertEquals('bad_validation', $warning->smells);
    });

    test('backdated hardcore unlock with no validation hash is not backdated', function () {
        $data = AwardAchievementTestHelpers::createGame();
        $game = $data['game'];
        $achievement1 = $data['achievements'][0];
        $achievement3 = $data['achievements'][2];
        $achievement5 = $data['achievements'][4];
        $achievement6 = $data['achievements'][5];
        $gameHash = $data['gameHash'];
        $now = Carbon::now();

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

        // cache the unlocks for the game
        $unlocks = getUserAchievementUnlocksForGame($this->user->username, $game->id);
        $this->assertEquals([$achievement1->id, $achievement5->id, $achievement6->id], array_keys($unlocks));

        // do the hardcore unlock
        $offset = 30;
        $validationHash = AwardAchievementTestHelpers::buildValidationHash($achievement3, $this->user, 1);
        $scoreBefore = $this->user->points_hardcore;
        $casualScoreBefore = $this->user->points;
        $truePointsBefore = $this->user->points_weighted;

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('awardachievement', [
                'a' => $achievement3->id,
                'h' => 1,
                'm' => $gameHash->md5,
                'o' => $offset,
            ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement3->id,
                'AchievementsRemaining' => 2,
                'Score' => $scoreBefore + $achievement3->points,
                'SoftcoreScore' => $casualScoreBefore,
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
        ])->first();
        $this->assertModelExists($playerGame);
        $this->assertNotNull($playerGame->last_played_at);

        // achievement unlocked
        $playerAchievement = PlayerAchievement::where([
            'user_id' => $this->user->id,
            'achievement_id' => $achievement3->id,
            'player_session_id' => $playerSession2->id,
        ])->first();
        $unlockDate = $now; // not backdated
        $this->assertModelExists($playerAchievement);
        $this->assertEquals($unlockDate, $playerAchievement->unlocked_at);
        $this->assertEquals($unlockDate, $playerAchievement->unlocked_hardcore_at);
        $this->assertEquals($playerAchievement->player_session_id, $playerSession2->id);

        // player score should have increased
        $user1 = User::whereName($this->user->username)->first();
        $this->assertEquals($scoreBefore + $achievement3->points, $user1->points_hardcore);
        $this->assertEquals($casualScoreBefore, $user1->points);

        // make sure the unlock cache was updated
        $unlocks = getUserAchievementUnlocksForGame($this->user->username, $game->id);
        $this->assertEqualsCanonicalizing([$achievement1->id, $achievement5->id, $achievement6->id, $achievement3->id], array_keys($unlocks));
        $this->assertEquals($unlockDate, $unlocks[$achievement3->id]['DateEarnedHardcore']);
        $this->assertEquals($unlockDate, $unlocks[$achievement3->id]['DateEarned']);

        $warning = AwardAchievementTestHelpers::getWarning($achievement3);
        $this->assertEquals($this->user->username, $warning->username);
        $this->assertEquals($offset, $warning->offset);
        $this->assertEquals('', $warning->validation_hash);
        $this->assertEquals('no_validation', $warning->smells);
    });

    test('backdated hardcore unlock with negative offset is not forward-dated', function () {
        $data = AwardAchievementTestHelpers::createGame();
        $game = $data['game'];
        $achievement1 = $data['achievements'][0];
        $achievement3 = $data['achievements'][2];
        $achievement5 = $data['achievements'][4];
        $achievement6 = $data['achievements'][5];
        $gameHash = $data['gameHash'];
        $now = Carbon::now();

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

        // cache the unlocks for the game
        $unlocks = getUserAchievementUnlocksForGame($this->user->username, $game->id);
        $this->assertEquals([$achievement1->id, $achievement5->id, $achievement6->id], array_keys($unlocks));

        // do the hardcore unlock
        $offset = -30;
        $validationHash = AwardAchievementTestHelpers::buildValidationHash($achievement3, $this->user, 1, $offset);
        $scoreBefore = $this->user->points_hardcore;
        $casualScoreBefore = $this->user->points;
        $truePointsBefore = $this->user->points_weighted;

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('awardachievement', [
                'a' => $achievement3->id,
                'h' => 1,
                'm' => $gameHash->md5,
                'o' => $offset,
                'v' => $validationHash,
            ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement3->id,
                'AchievementsRemaining' => 2,
                'Score' => $scoreBefore + $achievement3->points,
                'SoftcoreScore' => $casualScoreBefore,
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
        ])->first();
        $this->assertModelExists($playerGame);
        $this->assertNotNull($playerGame->last_played_at);

        // achievement unlocked
        $playerAchievement = PlayerAchievement::where([
            'user_id' => $this->user->id,
            'achievement_id' => $achievement3->id,
            'player_session_id' => $playerSession2->id,
        ])->first();
        $unlockDate = $now; // offset not applied
        $this->assertModelExists($playerAchievement);
        $this->assertEquals($unlockDate, $playerAchievement->unlocked_at);
        $this->assertEquals($unlockDate, $playerAchievement->unlocked_hardcore_at);
        $this->assertEquals($playerAchievement->player_session_id, $playerSession2->id);

        // player score should have increased
        $user1 = User::whereName($this->user->username)->first();
        $this->assertEquals($scoreBefore + $achievement3->points, $user1->points_hardcore);
        $this->assertEquals($casualScoreBefore, $user1->points);

        // make sure the unlock cache was updated
        $unlocks = getUserAchievementUnlocksForGame($this->user->username, $game->id);
        $this->assertEqualsCanonicalizing([$achievement1->id, $achievement5->id, $achievement6->id, $achievement3->id], array_keys($unlocks));
        $this->assertEquals($unlockDate, $unlocks[$achievement3->id]['DateEarnedHardcore']);
        $this->assertEquals($unlockDate, $unlocks[$achievement3->id]['DateEarned']);

        $this->assertEquals(0, ConnectWarning::count());
    });

    test('backdated hardcore unlock with large offset is not backdated', function () {
        $data = AwardAchievementTestHelpers::createGame();
        $game = $data['game'];
        $achievement1 = $data['achievements'][0];
        $achievement3 = $data['achievements'][2];
        $achievement5 = $data['achievements'][4];
        $achievement6 = $data['achievements'][5];
        $gameHash = $data['gameHash'];
        $now = Carbon::now();

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

        // cache the unlocks for the game
        $unlocks = getUserAchievementUnlocksForGame($this->user->username, $game->id);
        $this->assertEquals([$achievement1->id, $achievement5->id, $achievement6->id], array_keys($unlocks));

        // do the hardcore unlock
        $offset = 30 * 24 * 60 * 60; // 300 days
        $validationHash = AwardAchievementTestHelpers::buildValidationHash($achievement3, $this->user, 1, $offset);
        $scoreBefore = $this->user->points_hardcore;
        $casualScoreBefore = $this->user->points;
        $truePointsBefore = $this->user->points_weighted;

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('awardachievement', [
                'a' => $achievement3->id,
                'h' => 1,
                'm' => $gameHash->md5,
                'o' => $offset,
                'v' => $validationHash,
            ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement3->id,
                'AchievementsRemaining' => 2,
                'Score' => $scoreBefore + $achievement3->points,
                'SoftcoreScore' => $casualScoreBefore,
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
        ])->first();
        $this->assertModelExists($playerGame);
        $this->assertNotNull($playerGame->last_played_at);

        // achievement unlocked
        $playerAchievement = PlayerAchievement::where([
            'user_id' => $this->user->id,
            'achievement_id' => $achievement3->id,
            'player_session_id' => $playerSession2->id,
        ])->first();
        $unlockDate = $now; // offset not applied
        $this->assertModelExists($playerAchievement);
        $this->assertEquals($unlockDate, $playerAchievement->unlocked_at);
        $this->assertEquals($unlockDate, $playerAchievement->unlocked_hardcore_at);
        $this->assertEquals($playerAchievement->player_session_id, $playerSession2->id);

        // player score should have increased
        $user1 = User::whereName($this->user->username)->first();
        $this->assertEquals($scoreBefore + $achievement3->points, $user1->points_hardcore);
        $this->assertEquals($casualScoreBefore, $user1->points);

        // make sure the unlock cache was updated
        $unlocks = getUserAchievementUnlocksForGame($this->user->username, $game->id);
        $this->assertEqualsCanonicalizing([$achievement1->id, $achievement5->id, $achievement6->id, $achievement3->id], array_keys($unlocks));
        $this->assertEquals($unlockDate, $unlocks[$achievement3->id]['DateEarnedHardcore']);
        $this->assertEquals($unlockDate, $unlocks[$achievement3->id]['DateEarned']);

        $warning = AwardAchievementTestHelpers::getWarning($achievement3);
        $this->assertEquals($this->user->username, $warning->username);
        $this->assertEquals($offset, $warning->offset);
        $this->assertEquals($validationHash, $warning->validation_hash);
        $this->assertEquals('bad_validation', $warning->smells);
    });

    test('new casual unlock', function () {
        $data = AwardAchievementTestHelpers::createGame();
        $game = $data['game'];
        $achievement1 = $data['achievements'][0];
        $achievement3 = $data['achievements'][2];
        $achievement5 = $data['achievements'][4];
        $achievement6 = $data['achievements'][5];
        $gameHash = $data['gameHash'];
        $now = Carbon::now();

        $unlock1Date = $now->clone()->subMinutes(65);
        $this->addCasualUnlock($this->user, $achievement1, $unlock1Date, $gameHash);
        $this->addCasualUnlock($this->user, $achievement5, $unlock1Date, $gameHash);
        $this->addCasualUnlock($this->user, $achievement6, $unlock1Date, $gameHash);

        $playerSession1 = PlayerSession::where([
            'user_id' => $this->user->id,
            'game_id' => $achievement3->game_id,
            'game_hash_id' => $gameHash->id,
        ])->orderByDesc('id')->first();
        $this->assertModelExists($playerSession1);

        // cache the unlocks for the game
        $unlocks = getUserAchievementUnlocksForGame($this->user->username, $game->id);
        $this->assertEquals([$achievement1->id, $achievement5->id, $achievement6->id], array_keys($unlocks));

        // do the hardcore unlock
        $validationHash = AwardAchievementTestHelpers::buildValidationHash($achievement3, $this->user, 0);
        $scoreBefore = $this->user->points_hardcore;
        $casualScoreBefore = $this->user->points;
        $truePointsBefore = $this->user->points_weighted;

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('awardachievement', [
                'a' => $achievement3->id,
                'h' => 0,
                'm' => $gameHash->md5,
                'v' => $validationHash,
            ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement3->id,
                'AchievementsRemaining' => 2,
                'Score' => $scoreBefore,
                'SoftcoreScore' => $casualScoreBefore + $achievement3->points,
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
        $this->assertNull($playerAchievement->unlocked_hardcore_at);
        $this->assertEquals($playerAchievement->player_session_id, $playerSession2->id);

        // player score should have increased
        $user1 = User::whereName($this->user->username)->first();
        $this->assertEquals($scoreBefore, $user1->points_hardcore);
        $this->assertEquals($casualScoreBefore + $achievement3->points, $user1->points);

        // make sure the unlock cache was updated
        $unlocks = getUserAchievementUnlocksForGame($this->user->username, $game->id);
        $this->assertEqualsCanonicalizing([$achievement1->id, $achievement5->id, $achievement6->id, $achievement3->id], array_keys($unlocks));
        $this->assertFalse(array_key_exists('DateEarnedHardcore', $unlocks[$achievement3->id]));
        $this->assertEquals($now, $unlocks[$achievement3->id]['DateEarned']);

        $this->assertEquals(0, ConnectWarning::count());
    });

    test('offline submission client marker downgrades hardcore unlock and is captured on player session', function () {
        $data = AwardAchievementTestHelpers::createGame();
        $game = $data['game'];
        $achievement1 = $data['achievements'][0];
        $achievement3 = $data['achievements'][2];
        $gameHash = $data['gameHash'];
        $now = Carbon::now();
        $offlineSubmissionUserAgent = $this->userAgentValid . ' RAOfflineProxy/1.0.0-alpha1';

        $unlock1Date = $now->clone()->subMinutes(65);
        $this->addHardcoreUnlock($this->user, $achievement1, $unlock1Date, $gameHash);

        $validationHash = AwardAchievementTestHelpers::buildValidationHash($achievement3, $this->user, 1);
        $scoreBefore = $this->user->points_hardcore;
        $casualScoreBefore = $this->user->points;

        $this->withHeaders(['User-Agent' => $offlineSubmissionUserAgent])
            ->get($this->apiUrl('awardachievement', [
                'a' => $achievement3->id,
                'h' => 1,
                'm' => $gameHash->md5,
                'v' => $validationHash,
            ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement3->id,
                'AchievementsRemaining' => 4,
                'Score' => $scoreBefore,
                'SoftcoreScore' => $casualScoreBefore + $achievement3->points,
            ]);
        $this->user->refresh();

        $playerAchievement = PlayerAchievement::where([
            'user_id' => $this->user->id,
            'achievement_id' => $achievement3->id,
        ])->first();
        $this->assertModelExists($playerAchievement);
        $this->assertNotNull($playerAchievement->unlocked_at);
        $this->assertNull($playerAchievement->unlocked_hardcore_at);

        $playerSession = PlayerSession::find($playerAchievement->player_session_id);
        $this->assertModelExists($playerSession);
        $this->assertEquals($offlineSubmissionUserAgent, $playerSession->user_agent);

        $user1 = User::whereName($this->user->username)->first();
        $this->assertEquals($scoreBefore, $user1->points_hardcore);
        $this->assertEquals($casualScoreBefore + $achievement3->points, $user1->points);

        $this->assertEquals(0, ConnectWarning::count());
    });

    test('repeated casual unlock', function () {
        $data = AwardAchievementTestHelpers::createGame();
        $game = $data['game'];
        $achievement1 = $data['achievements'][0];
        $achievement5 = $data['achievements'][4];
        $achievement6 = $data['achievements'][5];
        $gameHash = $data['gameHash'];
        $now = Carbon::now();

        $unlock1Date = $now->clone()->subMinutes(65);
        $this->addCasualUnlock($this->user, $achievement1, $unlock1Date, $gameHash);
        $this->addCasualUnlock($this->user, $achievement5, $unlock1Date, $gameHash);
        $this->addCasualUnlock($this->user, $achievement6, $unlock1Date, $gameHash);

        $playerSession1 = PlayerSession::where([
            'user_id' => $this->user->id,
            'game_id' => $achievement1->game_id,
            'game_hash_id' => $gameHash->id,
        ])->orderByDesc('id')->first();
        $this->assertModelExists($playerSession1);

        // cache the unlocks for the game
        $unlocks = getUserAchievementUnlocksForGame($this->user->username, $game->id);
        $this->assertEquals([$achievement1->id, $achievement5->id, $achievement6->id], array_keys($unlocks));

        $newNow = $now->clone()->addMinutes(5);
        Carbon::setTestNow($newNow);
        $this->assertNotEquals($now, Carbon::now());

        // do the hardcore unlock
        $validationHash = AwardAchievementTestHelpers::buildValidationHash($achievement1, $this->user, 0);
        $scoreBefore = $this->user->points_hardcore;
        $casualScoreBefore = $this->user->points;
        $truePointsBefore = $this->user->points_weighted;

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('awardachievement', [
                'a' => $achievement1->id,
                'h' => 0,
                'm' => $gameHash->md5,
                'v' => $validationHash,
            ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => false,
                // client detects the "User already has" and does not report this as an error.
                'Error' => 'User already has this achievement unlocked.',
                'AchievementID' => $achievement1->id,
                'AchievementsRemaining' => 3,
                'Score' => $scoreBefore,
                'SoftcoreScore' => $casualScoreBefore,
            ]);

        // player score should not have increased
        $user2 = User::whereName($this->user->username)->first();
        $this->assertEquals($scoreBefore, $user2->points_hardcore);
        $this->assertEquals($casualScoreBefore, $user2->points);
        $this->assertEquals($truePointsBefore, $user2->points_weighted);

        // make sure the unlock time didn't change
        $unlockTime = $this->getUnlockTime($user2, $achievement1, UnlockMode::Hardcore);
        $this->assertNull($unlockTime);
        $unlockTime = $this->getUnlockTime($user2, $achievement1, UnlockMode::Casual);
        $this->assertEquals($unlock1Date, $unlockTime);

        $this->assertEquals(0, ConnectWarning::count());
    });

    test('repeated casual unlock for previous hardcore unlock', function () {
        $data = AwardAchievementTestHelpers::createGame();
        $game = $data['game'];
        $achievement1 = $data['achievements'][0];
        $achievement5 = $data['achievements'][4];
        $achievement6 = $data['achievements'][5];
        $gameHash = $data['gameHash'];
        $now = Carbon::now();

        $unlock1Date = $now->clone()->subMinutes(65);
        $this->addHardcoreUnlock($this->user, $achievement1, $unlock1Date, $gameHash);
        $this->addHardcoreUnlock($this->user, $achievement5, $unlock1Date, $gameHash);
        $this->addHardcoreUnlock($this->user, $achievement6, $unlock1Date, $gameHash);

        $playerSession1 = PlayerSession::where([
            'user_id' => $this->user->id,
            'game_id' => $achievement1->game_id,
            'game_hash_id' => $gameHash->id,
        ])->orderByDesc('id')->first();
        $this->assertModelExists($playerSession1);

        // cache the unlocks for the game
        $unlocks = getUserAchievementUnlocksForGame($this->user->username, $game->id);
        $this->assertEquals([$achievement1->id, $achievement5->id, $achievement6->id], array_keys($unlocks));

        $newNow = $now->clone()->addMinutes(5);
        Carbon::setTestNow($newNow);
        $this->assertNotEquals($now, Carbon::now());

        // do the hardcore unlock
        $validationHash = AwardAchievementTestHelpers::buildValidationHash($achievement1, $this->user, 0);
        $scoreBefore = $this->user->points_hardcore;
        $casualScoreBefore = $this->user->points;
        $truePointsBefore = $this->user->points_weighted;

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('awardachievement', [
                'a' => $achievement1->id,
                'h' => 0,
                'm' => $gameHash->md5,
                'v' => $validationHash,
            ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => false,
                // client detects the "User already has" and does not report this as an error.
                'Error' => 'User already has this achievement unlocked.',
                'AchievementID' => $achievement1->id,
                'AchievementsRemaining' => 3,
                'Score' => $scoreBefore,
                'SoftcoreScore' => $casualScoreBefore,
            ]);

        // player score should not have increased
        $user2 = User::whereName($this->user->username)->first();
        $this->assertEquals($scoreBefore, $user2->points_hardcore);
        $this->assertEquals($casualScoreBefore, $user2->points);
        $this->assertEquals($truePointsBefore, $user2->points_weighted);

        // make sure the unlock time didn't change
        $unlockTime = $this->getUnlockTime($user2, $achievement1, UnlockMode::Hardcore);
        $this->assertEquals($unlock1Date, $unlockTime);
        $unlockTime = $this->getUnlockTime($user2, $achievement1, UnlockMode::Casual);
        $this->assertEquals($unlock1Date, $unlockTime);

        $this->assertEquals(0, ConnectWarning::count());
    });

    test('final casual unlock awards badge', function () {
        $data = AwardAchievementTestHelpers::createGame();
        $game = $data['game'];
        $achievement1 = $data['achievements'][0];
        $achievement2 = $data['achievements'][1];
        $achievement3 = $data['achievements'][2];
        $achievement4 = $data['achievements'][3];
        $achievement5 = $data['achievements'][4];
        $achievement6 = $data['achievements'][5];
        $gameHash = $data['gameHash'];
        $now = Carbon::now();

        $unlock1Date = $now->clone()->subMinutes(65);
        $this->addCasualUnlock($this->user, $achievement1, $unlock1Date, $gameHash);
        $this->addCasualUnlock($this->user, $achievement3, $unlock1Date, $gameHash);
        $this->addCasualUnlock($this->user, $achievement4, $unlock1Date, $gameHash);
        $this->addCasualUnlock($this->user, $achievement5, $unlock1Date, $gameHash);
        $this->addCasualUnlock($this->user, $achievement6, $unlock1Date, $gameHash);

        $playerSession1 = PlayerSession::where([
            'user_id' => $this->user->id,
            'game_id' => $achievement4->game_id,
            'game_hash_id' => $gameHash->id,
        ])->orderByDesc('id')->first();
        $this->assertModelExists($playerSession1);

        // cache the unlocks for the game
        $unlocks = getUserAchievementUnlocksForGame($this->user->username, $game->id);
        $this->assertEquals([$achievement1->id, $achievement3->id, $achievement4->id, $achievement5->id, $achievement6->id], array_keys($unlocks));

        $newNow = $now->clone()->addMinutes(5);
        Carbon::setTestNow($newNow);
        $this->assertNotEquals($now, Carbon::now());

        // do the hardcore unlock
        $validationHash = AwardAchievementTestHelpers::buildValidationHash($achievement2, $this->user, 0);
        $scoreBefore = $this->user->points_hardcore;
        $casualScoreBefore = $this->user->points;
        $truePointsBefore = $this->user->points_weighted;

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('awardachievement', [
                'a' => $achievement2->id,
                'h' => 0,
                'm' => $gameHash->md5,
                'v' => $validationHash,
            ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement2->id,
                'AchievementsRemaining' => 0,
                'Score' => $scoreBefore,
                'SoftcoreScore' => $casualScoreBefore + $achievement2->points,
            ]);

        // player score should have increased
        $user1 = User::whereName($this->user->username)->first();
        $this->assertEquals($scoreBefore, $user1->points_hardcore);
        $this->assertEquals($casualScoreBefore + $achievement2->points, $user1->points);

        // make sure the unlock cache was updated
        $unlocks = getUserAchievementUnlocksForGame($this->user->username, $game->id);
        $this->assertEqualsCanonicalizing([$achievement1->id, $achievement3->id, $achievement4->id, $achievement5->id, $achievement6->id, $achievement2->id], array_keys($unlocks));
        $this->assertFalse(array_key_exists('DateEarnedHardcore', $unlocks[$achievement2->id]));
        $this->assertEquals($newNow, $unlocks[$achievement2->id]['DateEarned']);

        // verify badge was awarded
        $this->assertNotNull(PlayerBadge::where('user_id', $this->user->id)
            ->where('award_type', AwardType::Mastery)
            ->where('award_key', $game->id)
            ->where('award_tier', UnlockMode::Casual)
            ->where('awarded_at', $newNow)
            ->first()
        );

        $this->assertEquals(0, ConnectWarning::count());
    });

    test('hardcore unlock upgrades casual unlock', function () {
        $data = AwardAchievementTestHelpers::createGame();
        $game = $data['game'];
        $achievement1 = $data['achievements'][0];
        $achievement5 = $data['achievements'][4];
        $achievement6 = $data['achievements'][5];
        $gameHash = $data['gameHash'];
        $now = Carbon::now();

        $unlock1Date = $now->clone()->subMinutes(65);
        $this->addCasualUnlock($this->user, $achievement1, $unlock1Date, $gameHash);
        $this->addCasualUnlock($this->user, $achievement5, $unlock1Date, $gameHash);
        $this->addCasualUnlock($this->user, $achievement6, $unlock1Date, $gameHash);

        $playerSession1 = PlayerSession::where([
            'user_id' => $this->user->id,
            'game_id' => $achievement1->game_id,
            'game_hash_id' => $gameHash->id,
        ])->orderByDesc('id')->first();
        $this->assertModelExists($playerSession1);

        // cache the unlocks for the game
        $unlocks = getUserAchievementUnlocksForGame($this->user->username, $game->id);
        $this->assertEquals([$achievement1->id, $achievement5->id, $achievement6->id], array_keys($unlocks));

        $newNow = $now->clone()->addMinutes(5);
        Carbon::setTestNow($newNow);
        $this->assertNotEquals($now, Carbon::now());

        // do the hardcore unlock
        $validationHash = AwardAchievementTestHelpers::buildValidationHash($achievement1, $this->user, 1);
        $scoreBefore = $this->user->points_hardcore;
        $casualScoreBefore = $this->user->points;
        $truePointsBefore = $this->user->points_weighted;

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('awardachievement', [
                'a' => $achievement1->id,
                'h' => 1,
                'm' => $gameHash->md5,
                'v' => $validationHash,
            ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement1->id,
                'AchievementsRemaining' => 5, // only 1 unlocked in hardcore
                'Score' => $scoreBefore + $achievement1->points,
                'SoftcoreScore' => $casualScoreBefore - $achievement1->points,
            ]);

        // player score should have adjusted
        $user2 = User::whereName($this->user->username)->first();
        $this->assertEquals($scoreBefore + $achievement1->points, $user2->points_hardcore);
        $this->assertEquals($casualScoreBefore - $achievement1->points, $user2->points);

        // make sure the softcore unlock time didn't change
        $unlockTime = $this->getUnlockTime($user2, $achievement1, UnlockMode::Hardcore);
        $this->assertEquals($newNow, $unlockTime);
        $unlockTime = $this->getUnlockTime($user2, $achievement1, UnlockMode::Casual);
        $this->assertEquals($unlock1Date, $unlockTime);

        $this->assertEquals(0, ConnectWarning::count());
    });

    test('final hardcore unlock upgrades casual badge', function () {
        $data = AwardAchievementTestHelpers::createGame();
        $game = $data['game'];
        $achievement1 = $data['achievements'][0];
        $achievement2 = $data['achievements'][1];
        $achievement3 = $data['achievements'][2];
        $achievement4 = $data['achievements'][3];
        $achievement5 = $data['achievements'][4];
        $achievement6 = $data['achievements'][5];
        $gameHash = $data['gameHash'];
        $now = Carbon::now();

        $unlock1Date = $now->clone()->subMinutes(65);
        $this->addHardcoreUnlock($this->user, $achievement1, $unlock1Date, $gameHash);
        $this->addHardcoreUnlock($this->user, $achievement3, $unlock1Date, $gameHash);
        $this->addHardcoreUnlock($this->user, $achievement4, $unlock1Date, $gameHash);
        $this->addHardcoreUnlock($this->user, $achievement5, $unlock1Date, $gameHash);
        $this->addHardcoreUnlock($this->user, $achievement6, $unlock1Date, $gameHash);
        $this->addCasualUnlock($this->user, $achievement2, $unlock1Date, $gameHash);

        $playerSession1 = PlayerSession::where([
            'user_id' => $this->user->id,
            'game_id' => $achievement1->game_id,
            'game_hash_id' => $gameHash->id,
        ])->orderByDesc('id')->first();
        $this->assertModelExists($playerSession1);

        // cache the unlocks for the game
        $unlocks = getUserAchievementUnlocksForGame($this->user->username, $game->id);
        $this->assertEquals([$achievement1->id, $achievement2->id, $achievement3->id, $achievement4->id, $achievement5->id, $achievement6->id], array_keys($unlocks));

        $newNow = $now->clone()->addMinutes(5);
        Carbon::setTestNow($newNow);
        $this->assertNotEquals($now, Carbon::now());

        // do the hardcore unlock
        $validationHash = AwardAchievementTestHelpers::buildValidationHash($achievement2, $this->user, 1);
        $scoreBefore = $this->user->points_hardcore;
        $casualScoreBefore = $this->user->points;
        $truePointsBefore = $this->user->points_weighted;

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('awardachievement', [
                'a' => $achievement2->id,
                'h' => 1,
                'm' => $gameHash->md5,
                'v' => $validationHash,
            ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement2->id,
                'AchievementsRemaining' => 0,
                'Score' => $scoreBefore + $achievement2->points,
                'SoftcoreScore' => $casualScoreBefore - $achievement2->points,
            ]);

        // player score should have adjusted
        $user2 = User::whereName($this->user->username)->first();
        $this->assertEquals($scoreBefore + $achievement2->points, $user2->points_hardcore);
        $this->assertEquals($casualScoreBefore - $achievement2->points, $user2->points);

        // make sure the softcore unlock time didn't change
        $unlockTime = $this->getUnlockTime($user2, $achievement2, UnlockMode::Hardcore);
        $this->assertEquals($newNow, $unlockTime);
        $unlockTime = $this->getUnlockTime($user2, $achievement2, UnlockMode::Casual);
        $this->assertEquals($unlock1Date, $unlockTime);

        // verify badge was awarded
        $this->assertNotNull(PlayerBadge::where('user_id', $this->user->id)
            ->where('award_type', AwardType::Mastery)
            ->where('award_key', $game->id)
            ->where('award_tier', UnlockMode::Hardcore)
            ->where('awarded_at', $newNow)
            ->first()
        );

        // verify softcore badge no longer exists
        $this->assertNull(PlayerBadge::where('user_id', $this->user->id)
            ->where('award_type', AwardType::Mastery)
            ->where('award_key', $game->id)
            ->where('award_tier', UnlockMode::Casual)
            ->first()
        );

        $this->assertEquals(0, ConnectWarning::count());
    });

    test('warning achievement is ignored', function () {
        // requesting an unlock for the warning achievement should return success without actually unlocking it
        $validationHash = md5(Achievement::CLIENT_WARNING_ID . $this->user->display_name . '1');
        $scoreBefore = $this->user->points_hardcore;
        $casualScoreBefore = $this->user->points;
        $truePointsBefore = $this->user->points_weighted;

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('awardachievement', [
                'a' => Achievement::CLIENT_WARNING_ID,
                'h' => 1,
                'm' => Str::random(32),
                'v' => $validationHash,
            ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => Achievement::CLIENT_WARNING_ID,
                'AchievementsRemaining' => 9999,
                'Score' => $scoreBefore,
                'SoftcoreScore' => $casualScoreBefore,
            ]);

        // player score should not have increased
        $user2 = User::whereName($this->user->username)->first();
        $this->assertEquals($scoreBefore, $user2->points_hardcore);
        $this->assertEquals($casualScoreBefore, $user2->points);
        $this->assertEquals($truePointsBefore, $user2->points_weighted);

        // make sure the unlock didn't occur
        $this->assertEquals(0, PlayerAchievement::count());

        $this->assertEquals(0, ConnectWarning::count());
    });
});

describe('delegated unlock', function () {
    test('by name', function () {
        $data = AwardAchievementTestHelpers::createStandaloneGame();
        $integrationUser = $data['integrationUser'];
        $delegatedUser = $data['delegatedUser'];
        $game = $data['game'];
        $achievement1 = $data['achievements'][0];
        $achievement3 = $data['achievements'][2];
        $achievement5 = $data['achievements'][4];
        $achievement6 = $data['achievements'][5];
        $now = Carbon::now();

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
        $unlocks = getUserAchievementUnlocksForGame($delegatedUser->username, $game->id);
        $this->assertEquals([$achievement1->id, $achievement5->id, $achievement6->id], array_keys($unlocks));

        // do the delegated hardcore unlock
        $validationHash = md5($achievement3->id . $delegatedUser->username . '1' . $achievement3->id);
        $scoreBefore = $delegatedUser->points_hardcore;
        $casualScoreBefore = $delegatedUser->points;

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->post('dorequest.php?r=awardachievement', [
                'u' => $integrationUser->username,
                't' => $integrationUser->connect_token,
                'k' => $delegatedUser->username,
                'h' => 1,
                'a' => $achievement3->id,
                'v' => $validationHash,
            ])
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement3->id,
                'AchievementsRemaining' => 2,
                'Score' => $scoreBefore + $achievement3->points,
                'SoftcoreScore' => $casualScoreBefore,
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
        $user1 = User::whereName($delegatedUser->username)->first();
        $this->assertEquals($scoreBefore + $achievement3->points, $user1->points_hardcore);
        $this->assertEquals($casualScoreBefore, $user1->points);

        // make sure the unlock cache was updated
        $unlocks = getUserAchievementUnlocksForGame($delegatedUser->username, $game->id);
        $this->assertEqualsCanonicalizing([$achievement1->id, $achievement5->id, $achievement6->id, $achievement3->id], array_keys($unlocks));
        $this->assertEquals($now, $unlocks[$achievement3->id]['DateEarnedHardcore']);
        $this->assertEquals($now, $unlocks[$achievement3->id]['DateEarned']);

        $this->assertEquals(0, ConnectWarning::count());
    });

    test('by ulid', function () {
        $data = AwardAchievementTestHelpers::createStandaloneGame();
        $integrationUser = $data['integrationUser'];
        $delegatedUser = $data['delegatedUser'];
        $game = $data['game'];
        $achievement1 = $data['achievements'][0];
        $achievement3 = $data['achievements'][2];
        $achievement5 = $data['achievements'][4];
        $achievement6 = $data['achievements'][5];
        $now = Carbon::now();

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
        $unlocks = getUserAchievementUnlocksForGame($delegatedUser->username, $game->id);
        $this->assertEquals([$achievement1->id, $achievement5->id, $achievement6->id], array_keys($unlocks));

        // do the delegated hardcore unlock
        $validationHash = md5($achievement3->id . $delegatedUser->ulid . '1' . $achievement3->id);
        $scoreBefore = $delegatedUser->points_hardcore;
        $casualScoreBefore = $delegatedUser->points;

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->post('dorequest.php?r=awardachievement', [
                'u' => $integrationUser->username,
                't' => $integrationUser->connect_token,
                'k' => $delegatedUser->ulid,
                'h' => 1,
                'a' => $achievement3->id,
                'v' => $validationHash,
            ])
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement3->id,
                'AchievementsRemaining' => 2,
                'Score' => $scoreBefore + $achievement3->points,
                'SoftcoreScore' => $casualScoreBefore,
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
        $user1 = User::whereName($delegatedUser->username)->first();
        $this->assertEquals($scoreBefore + $achievement3->points, $user1->points_hardcore);
        $this->assertEquals($casualScoreBefore, $user1->points);

        // make sure the unlock cache was updated
        $unlocks = getUserAchievementUnlocksForGame($delegatedUser->username, $game->id);
        $this->assertEqualsCanonicalizing([$achievement1->id, $achievement5->id, $achievement6->id, $achievement3->id], array_keys($unlocks));
        $this->assertEquals($now, $unlocks[$achievement3->id]['DateEarnedHardcore']);
        $this->assertEquals($now, $unlocks[$achievement3->id]['DateEarned']);

        $this->assertEquals(0, ConnectWarning::count());
    });

    test('not allowed for user other than author', function () {
        $data = AwardAchievementTestHelpers::createStandaloneGame();
        $game = $data['game'];
        $achievement1 = $data['achievements'][0];
        $delegatedUser = $data['delegatedUser'];
        $now = Carbon::now();

        // do the hardcore unlock
        $validationHash = AwardAchievementTestHelpers::buildValidationHash($achievement1, $delegatedUser, 1);
        $scoreBefore = $this->user->points_hardcore;
        $casualScoreBefore = $this->user->points;
        $truePointsBefore = $this->user->points_weighted;

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->post('dorequest.php?r=awardachievement', [
                'u' => $this->user->username,
                't' => $this->user->connect_token,
                'k' => $delegatedUser->username,
                'h' => 1,
                'a' => $achievement1->id,
                'v' => $validationHash,
            ])
            ->assertStatus(403)
            ->assertExactJson([
                'Success' => false,
                'Code' => 'access_denied',
                'Error' => 'Access denied.',
                'Status' => 403,
            ]);

        // player score should not have increased
        $user2 = User::whereName($this->user->username)->first();
        $this->assertEquals($scoreBefore, $user2->points_hardcore);
        $this->assertEquals($casualScoreBefore, $user2->points);
        $this->assertEquals($truePointsBefore, $user2->points_weighted);

        // make sure the unlock didn't happen
        $unlockTime = $this->getUnlockTime($delegatedUser, $achievement1, UnlockMode::Hardcore);
        $this->assertNull($unlockTime);
        $unlockTime = $this->getUnlockTime($delegatedUser, $achievement1, UnlockMode::Casual);
        $this->assertNull($unlockTime);
        $unlockTime = $this->getUnlockTime($this->user, $achievement1, UnlockMode::Hardcore);
        $this->assertNull($unlockTime);
        $unlockTime = $this->getUnlockTime($this->user, $achievement1, UnlockMode::Casual);
        $this->assertNull($unlockTime);

        $this->assertEquals(0, ConnectWarning::count());
    });

    test('not allowed for non-standalone game', function () {
        $data = AwardAchievementTestHelpers::createGame();
        $game = $data['game'];
        $achievement1 = $data['achievements'][0];
        $author = $data['author'];
        $author->connect_token = Str::random(16);
        $author->save();
        $delegatedUser = User::factory()->create();
        $now = Carbon::now();

        // do the hardcore unlock
        $validationHash = AwardAchievementTestHelpers::buildValidationHash($achievement1, $delegatedUser, 1);
        $scoreBefore = $this->user->points_hardcore;
        $casualScoreBefore = $this->user->points;
        $truePointsBefore = $this->user->points_weighted;

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->post('dorequest.php?r=awardachievement', [
                'u' => $author->username, // even the achievement author is not allowed to delegate unlocks for a non-standalone game
                't' => $author->connect_token,
                'k' => $delegatedUser->username,
                'h' => 1,
                'a' => $achievement1->id,
                'v' => $validationHash,
            ])
            ->assertStatus(403)
            ->assertExactJson([
                'Success' => false,
                'Code' => 'access_denied',
                'Error' => 'Access denied.',
                'Status' => 403,
            ]);

        // player score should not have increased
        $user2 = User::whereName($this->user->username)->first();
        $this->assertEquals($scoreBefore, $user2->points_hardcore);
        $this->assertEquals($casualScoreBefore, $user2->points);
        $this->assertEquals($truePointsBefore, $user2->points_weighted);

        // make sure the unlock didn't happen
        $unlockTime = $this->getUnlockTime($delegatedUser, $achievement1, UnlockMode::Hardcore);
        $this->assertNull($unlockTime);
        $unlockTime = $this->getUnlockTime($delegatedUser, $achievement1, UnlockMode::Casual);
        $this->assertNull($unlockTime);
        $unlockTime = $this->getUnlockTime($author, $achievement1, UnlockMode::Hardcore);
        $this->assertNull($unlockTime);
        $unlockTime = $this->getUnlockTime($author, $achievement1, UnlockMode::Casual);
        $this->assertNull($unlockTime);

        $this->assertEquals(0, ConnectWarning::count());
    });

    test('not allowed without validation hash', function () {
        $data = AwardAchievementTestHelpers::createStandaloneGame();
        $game = $data['game'];
        $achievement1 = $data['achievements'][0];
        $integrationUser = $data['integrationUser'];
        $delegatedUser = $data['delegatedUser'];
        $now = Carbon::now();

        // do the hardcore unlock
        $scoreBefore = $this->user->points_hardcore;
        $casualScoreBefore = $this->user->points;
        $truePointsBefore = $this->user->points_weighted;

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->post('dorequest.php?r=awardachievement', [
                'u' => $integrationUser->username,
                't' => $integrationUser->connect_token,
                'k' => $delegatedUser->username,
                'h' => 1,
                'a' => $achievement1->id,
            ])
            ->assertStatus(403)
            ->assertExactJson([
                'Success' => false,
                'Code' => 'access_denied',
                'Error' => 'Access denied.',
                'Status' => 403,
            ]);

        // player score should not have increased
        $user2 = User::whereName($this->user->username)->first();
        $this->assertEquals($scoreBefore, $user2->points_hardcore);
        $this->assertEquals($casualScoreBefore, $user2->points);
        $this->assertEquals($truePointsBefore, $user2->points_weighted);

        // make sure the unlock didn't happen
        $unlockTime = $this->getUnlockTime($delegatedUser, $achievement1, UnlockMode::Hardcore);
        $this->assertNull($unlockTime);
        $unlockTime = $this->getUnlockTime($delegatedUser, $achievement1, UnlockMode::Casual);
        $this->assertNull($unlockTime);
        $unlockTime = $this->getUnlockTime($this->user, $achievement1, UnlockMode::Hardcore);
        $this->assertNull($unlockTime);
        $unlockTime = $this->getUnlockTime($this->user, $achievement1, UnlockMode::Casual);
        $this->assertNull($unlockTime);

        $this->assertEquals(0, ConnectWarning::count());
    });

    test('not allowed without correct validation hash', function () {
        $data = AwardAchievementTestHelpers::createStandaloneGame();
        $game = $data['game'];
        $achievement1 = $data['achievements'][0];
        $integrationUser = $data['integrationUser'];
        $delegatedUser = $data['delegatedUser'];
        $now = Carbon::now();

        // do the hardcore unlock
        $validationHash = md5('This isn\'t the correct validation hash');
        $scoreBefore = $this->user->points_hardcore;
        $casualScoreBefore = $this->user->points;
        $truePointsBefore = $this->user->points_weighted;

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->post('dorequest.php?r=awardachievement', [
                'u' => $integrationUser->username,
                't' => $integrationUser->connect_token,
                'k' => $delegatedUser->username,
                'h' => 1,
                'a' => $achievement1->id,
                'v' => $validationHash,
            ])
            ->assertStatus(403)
            ->assertExactJson([
                'Success' => false,
                'Code' => 'access_denied',
                'Error' => 'Access denied.',
                'Status' => 403,
            ]);

        // player score should not have increased
        $user2 = User::whereName($this->user->username)->first();
        $this->assertEquals($scoreBefore, $user2->points_hardcore);
        $this->assertEquals($casualScoreBefore, $user2->points);
        $this->assertEquals($truePointsBefore, $user2->points_weighted);

        // make sure the unlock didn't happen
        $unlockTime = $this->getUnlockTime($delegatedUser, $achievement1, UnlockMode::Hardcore);
        $this->assertNull($unlockTime);
        $unlockTime = $this->getUnlockTime($delegatedUser, $achievement1, UnlockMode::Casual);
        $this->assertNull($unlockTime);
        $unlockTime = $this->getUnlockTime($this->user, $achievement1, UnlockMode::Hardcore);
        $this->assertNull($unlockTime);
        $unlockTime = $this->getUnlockTime($this->user, $achievement1, UnlockMode::Casual);
        $this->assertNull($unlockTime);

        $this->assertEquals(0, ConnectWarning::count());
    });
});

describe('event unlocks', function () {
    test('new hardcore unlock unlocks event achievement', function () {
        $data = AwardAchievementTestHelpers::createGame();
        $game = $data['game'];
        $achievement1 = $data['achievements'][0];
        $achievement2 = $data['achievements'][1];
        $eventAchievement = AwardAchievementTestHelpers::createEventAchievement($achievement1);
        $gameHash = $data['gameHash'];
        $now = Carbon::now();
        $this->addHardcoreUnlock($this->user, $achievement2); // ensures PlayerGame record exists and player score is accurate

        // do the hardcore unlock
        $validationHash = AwardAchievementTestHelpers::buildValidationHash($achievement1, $this->user, 1);
        $scoreBefore = $this->user->points_hardcore;
        $casualScoreBefore = $this->user->points;
        $truePointsBefore = $this->user->points_weighted;

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('awardachievement', [
                'a' => $achievement1->id,
                'h' => 1,
                'm' => $gameHash->md5,
                'v' => $validationHash,
            ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement1->id,
                'AchievementsRemaining' => 4,
                'Score' => $scoreBefore + $achievement1->points,
                'SoftcoreScore' => $casualScoreBefore,
            ]);
        $this->user->refresh();

        // achievement unlocked
        $this->assertHasHardcoreUnlock($this->user, $achievement1);
        $this->assertHasHardcoreUnlock($this->user, $eventAchievement);

        // player score should have increased
        $this->assertEquals($scoreBefore + $achievement1->points, $this->user->points_hardcore);
        $this->assertEquals($casualScoreBefore, $this->user->points);

        $this->assertEquals(0, ConnectWarning::count());
    });

    test('repeated hardcore unlock still unlocks event achievement', function () {
        $data = AwardAchievementTestHelpers::createGame();
        $game = $data['game'];
        $achievement1 = $data['achievements'][0];
        $eventAchievement = AwardAchievementTestHelpers::createEventAchievement($achievement1);
        $gameHash = $data['gameHash'];
        $now = Carbon::now();

        $unlock1Date = $now->clone()->subDays(5); // must be before event achievement active (-1 days)
        $this->addHardcoreUnlock($this->user, $achievement1, $unlock1Date, $gameHash);

        // do the hardcore unlock
        $validationHash = AwardAchievementTestHelpers::buildValidationHash($achievement1, $this->user, 1);
        $scoreBefore = $this->user->points_hardcore;
        $casualScoreBefore = $this->user->points;
        $truePointsBefore = $this->user->points_weighted;

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('awardachievement', [
                'a' => $achievement1->id,
                'h' => 1,
                'm' => $gameHash->md5,
                'v' => $validationHash,
            ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                // client detects the "User already has" and does not report this as an error.
                'Error' => 'User already has this achievement unlocked in hardcore mode.',
                'AchievementID' => $achievement1->id,
                'AchievementsRemaining' => 5,
                'Score' => $scoreBefore,
                'SoftcoreScore' => $casualScoreBefore,
            ]);
        $this->user->refresh();

        // achievement unlocked
        $this->assertHasHardcoreUnlock($this->user, $eventAchievement);

        // player score should not have changed
        $user1 = User::whereName($this->user->username)->first();
        $this->assertEquals($scoreBefore, $user1->points_hardcore);
        $this->assertEquals($casualScoreBefore, $user1->points);

        $this->assertEquals(0, ConnectWarning::count());
    });

    test('new casual unlock does not unlock event achievement', function () {
        $data = AwardAchievementTestHelpers::createGame();
        $game = $data['game'];
        $achievement1 = $data['achievements'][0];
        $achievement2 = $data['achievements'][1];
        $eventAchievement = AwardAchievementTestHelpers::createEventAchievement($achievement1);
        $gameHash = $data['gameHash'];
        $now = Carbon::now();
        $this->addCasualUnlock($this->user, $achievement2); // ensures PlayerGame record exists and player score is accurate

        // do the hardcore unlock
        $validationHash = AwardAchievementTestHelpers::buildValidationHash($achievement1, $this->user, 0);
        $scoreBefore = $this->user->points_hardcore;
        $casualScoreBefore = $this->user->points;
        $truePointsBefore = $this->user->points_weighted;

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('awardachievement', [
                'a' => $achievement1->id,
                'h' => 0,
                'm' => $gameHash->md5,
                'v' => $validationHash,
            ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement1->id,
                'AchievementsRemaining' => 4,
                'Score' => $scoreBefore,
                'SoftcoreScore' => $casualScoreBefore + $achievement1->points,
            ]);
        $this->user->refresh();

        // achievement unlocked
        $this->assertHasCasualUnlock($this->user, $achievement1);
        $this->assertDoesNotHaveCasualUnlock($this->user, $eventAchievement);
        $this->assertDoesNotHaveHardcoreUnlock($this->user, $achievement1);
        $this->assertDoesNotHaveHardcoreUnlock($this->user, $eventAchievement);

        // player score should have increased
        $this->assertEquals($scoreBefore, $this->user->points_hardcore);
        $this->assertEquals($casualScoreBefore + $achievement1->points, $this->user->points);

        $this->assertEquals(0, ConnectWarning::count());
    });

    test('new hardcore unlock does not unlock event achievement for unranked user', function () {
        $data = AwardAchievementTestHelpers::createGame();
        $game = $data['game'];
        $achievement1 = $data['achievements'][0];
        $achievement2 = $data['achievements'][1];
        $eventAchievement = AwardAchievementTestHelpers::createEventAchievement($achievement1);
        $gameHash = $data['gameHash'];
        $now = Carbon::now();
        $this->addHardcoreUnlock($this->user, $achievement2); // ensures PlayerGame record exists and player score is accurate
        $this->user->unranked_at = $now->clone()->subWeeks(1);
        $this->user->save();

        // do the hardcore unlock
        $validationHash = AwardAchievementTestHelpers::buildValidationHash($achievement1, $this->user, 1);
        $scoreBefore = $this->user->points_hardcore;
        $casualScoreBefore = $this->user->points;
        $truePointsBefore = $this->user->points_weighted;

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('awardachievement', [
                'a' => $achievement1->id,
                'h' => 1,
                'm' => $gameHash->md5,
                'v' => $validationHash,
            ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement1->id,
                'AchievementsRemaining' => 4,
                'Score' => $scoreBefore + $achievement1->points,
                'SoftcoreScore' => $casualScoreBefore,
            ]);
        $this->user->refresh();

        // achievement unlocked
        $this->assertHasHardcoreUnlock($this->user, $achievement1);
        $this->assertDoesNotHaveHardcoreUnlock($this->user, $eventAchievement);

        // player score should have increased
        $this->assertEquals($scoreBefore + $achievement1->points, $this->user->points_hardcore);
        $this->assertEquals($casualScoreBefore, $this->user->points);

        $this->assertEquals(0, ConnectWarning::count());
    });

    test('new hardcore unlock unlocks event achievement directly', function () {
        $data = AwardAchievementTestHelpers::createGame();
        System::factory()->create(['id' => System::Events, 'name' => 'Events']);
        $game = $data['game'];
        $game->system_id = System::Events;
        $game->save();
        $achievement1 = $data['achievements'][0];
        $achievement2 = $data['achievements'][1];
        $gameHash = $data['gameHash'];
        $this->addHardcoreUnlock($this->user, $achievement2); // ensures PlayerGame record exists and player score is accurate

        // do the hardcore unlock
        $validationHash = AwardAchievementTestHelpers::buildValidationHash($achievement1, $this->user, 1);
        $scoreBefore = $this->user->points_hardcore;
        $casualScoreBefore = $this->user->points;

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('awardachievement', [
                'a' => $achievement1->id,
                'h' => 1,
                'm' => $gameHash->md5,
                'v' => $validationHash,
            ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement1->id,
                'AchievementsRemaining' => 4,
                'Score' => $scoreBefore,
                'SoftcoreScore' => $casualScoreBefore,
            ]);
        $this->user->refresh();

        // achievement unlocked
        $this->assertHasHardcoreUnlock($this->user, $achievement1);

        // player score should not have increased
        $this->assertEquals($scoreBefore, $this->user->points_hardcore);
        $this->assertEquals($casualScoreBefore, $this->user->points);

        $this->assertEquals(0, ConnectWarning::count());
    });

    test('new casual unlock does not unlock event achievement directly', function () {
        $data = AwardAchievementTestHelpers::createGame();
        System::factory()->create(['id' => System::Events, 'name' => 'Events']);
        $game = $data['game'];
        $game->system_id = System::Events;
        $game->save();
        $achievement1 = $data['achievements'][0];
        $achievement2 = $data['achievements'][1];
        $gameHash = $data['gameHash'];
        $this->addHardcoreUnlock($this->user, $achievement2); // ensures PlayerGame record exists and player score is accurate

        // do the casual unlock
        $validationHash = AwardAchievementTestHelpers::buildValidationHash($achievement1, $this->user, 0);
        $scoreBefore = $this->user->points_hardcore;
        $casualScoreBefore = $this->user->points;

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('awardachievement', [
                'a' => $achievement1->id,
                'h' => 0,
                'm' => $gameHash->md5,
                'v' => $validationHash,
            ]))
            ->assertStatus(422)
            ->assertExactJson([
                'Success' => false,
                'Code' => 'invalid_parameter',
                'Error' => 'Event achievements can only be unlocked in hardcore.',
                'Status' => 422,
            ]);
        $this->user->refresh();

        // achievement not unlocked
        $this->assertDoesNotHaveCasualUnlock($this->user, $achievement1);

        // player score should not have increased
        $this->assertEquals($scoreBefore, $this->user->points_hardcore);
        $this->assertEquals($casualScoreBefore, $this->user->points);

        $this->assertEquals(0, ConnectWarning::count());
    });
});

describe('validation', function () {
    test('unknown achievement cannot be unlocked', function () {
        $data = AwardAchievementTestHelpers::createGame();
        $gameHash = $data['gameHash'];
        $now = Carbon::now();

        // do the hardcore unlock
        $validationHash = md5('999999' . $this->user->username . '1');
        $scoreBefore = $this->user->points_hardcore;
        $casualScoreBefore = $this->user->points;
        $truePointsBefore = $this->user->points_weighted;

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('awardachievement', [
                'a' => 999999,
                'h' => 1,
                'm' => $gameHash->md5,
                'v' => $validationHash,
            ]))
            ->assertStatus(404)
            ->assertExactJson([
                'Success' => false,
                'Code' => 'not_found',
                'Error' => 'Unknown achievement.',
                'Status' => 404,
            ]);

        // player score should not have increased
        $user2 = User::whereName($this->user->username)->first();
        $this->assertEquals($scoreBefore, $user2->points_hardcore);
        $this->assertEquals($casualScoreBefore, $user2->points);
        $this->assertEquals($truePointsBefore, $user2->points_weighted);

        // make sure the unlock didn't happen
        $this->assertEquals(0, PlayerAchievement::count());

        $this->assertEquals(0, ConnectWarning::count());
    });

    test('unpromoted achievement cannot be unlocked', function () {
        $data = AwardAchievementTestHelpers::createGame();
        $achievement1 = $data['achievements'][0];
        $achievement1->is_promoted = false;
        $achievement1->save();
        $gameHash = $data['gameHash'];
        $now = Carbon::now();

        // do the hardcore unlock
        $validationHash = AwardAchievementTestHelpers::buildValidationHash($achievement1, $this->user, 1);
        $scoreBefore = $this->user->points_hardcore;
        $casualScoreBefore = $this->user->points;
        $truePointsBefore = $this->user->points_weighted;

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('awardachievement', [
                'a' => $achievement1->id,
                'h' => 1,
                'm' => $gameHash->md5,
                'v' => $validationHash,
            ]))
            ->assertStatus(409)
            ->assertExactJson([
                'Success' => false,
                'Code' => 'invalid_state',
                'Error' => 'Unpromoted achievements cannot be unlocked.',
                'Status' => 409,
            ]);

        // player score should not have increased
        $user2 = User::whereName($this->user->username)->first();
        $this->assertEquals($scoreBefore, $user2->points_hardcore);
        $this->assertEquals($casualScoreBefore, $user2->points);
        $this->assertEquals($truePointsBefore, $user2->points_weighted);

        // make sure the unlock didn't happen
        $this->assertEquals(0, PlayerAchievement::count());

        $this->assertEquals(0, ConnectWarning::count());
    });

    test('missing user agent header records warning without erroring', function () {
        $data = AwardAchievementTestHelpers::createGame();
        $achievement1 = $data['achievements'][0];
        $achievement3 = $data['achievements'][2];
        $gameHash = $data['gameHash'];
        $now = Carbon::now();

        $unlock1Date = $now->clone()->subMinutes(65);
        $this->addCasualUnlock($this->user, $achievement1, $unlock1Date, $gameHash);

        $validationHash = AwardAchievementTestHelpers::buildValidationHash($achievement3, $this->user, 0);
        $scoreBefore = $this->user->points_hardcore;
        $casualScoreBefore = $this->user->points;

        $this->withHeaders(['User-Agent' => null])
            ->get($this->apiUrl('awardachievement', [
                'a' => $achievement3->id,
                'h' => 0,
                'm' => $gameHash->md5,
                'v' => $validationHash,
            ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement3->id,
                'AchievementsRemaining' => 4,
                'Score' => $scoreBefore,
                'SoftcoreScore' => $casualScoreBefore + $achievement3->points,
            ]);

        $warning = AwardAchievementTestHelpers::getWarning($achievement3);
        $this->assertEquals($this->user->username, $warning->username);
        $this->assertEquals('unknown_client', $warning->smells);
        $this->assertEquals('', $warning->user_agent);
    });

    test('missing user agent header demotes hardcore unlock to casual', function () {
        $data = AwardAchievementTestHelpers::createGame();
        $achievement1 = $data['achievements'][0];
        $achievement3 = $data['achievements'][2];
        $gameHash = $data['gameHash'];
        $now = Carbon::now();

        $unlock1Date = $now->clone()->subMinutes(65);
        $this->addCasualUnlock($this->user, $achievement1, $unlock1Date, $gameHash);

        $validationHash = AwardAchievementTestHelpers::buildValidationHash($achievement3, $this->user, 1);
        $scoreBefore = $this->user->points_hardcore;
        $casualScoreBefore = $this->user->points;

        $this->withHeaders(['User-Agent' => null])
            ->get($this->apiUrl('awardachievement', [
                'a' => $achievement3->id,
                'h' => 1,
                'm' => $gameHash->md5,
                'v' => $validationHash,
            ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement3->id,
                'AchievementsRemaining' => 4,
                'Score' => $scoreBefore,
                'SoftcoreScore' => $casualScoreBefore + $achievement3->points,
            ]);

        // achievement unlocked
        $this->assertHasCasualUnlock($this->user, $achievement3);
        $this->assertDoesNotHaveHardcoreUnlock($this->user, $achievement3);

        // player score should have increased
        $user1 = User::whereName($this->user->username)->first();
        $this->assertEquals($scoreBefore, $user1->points_hardcore);
        $this->assertEquals($casualScoreBefore + $achievement3->points, $user1->points);
    });

    test('unknown user agent demotes hardcore unlock to casual', function () {
        $data = AwardAchievementTestHelpers::createGame();
        $game = $data['game'];
        $achievement1 = $data['achievements'][0];
        $achievement3 = $data['achievements'][2];
        $gameHash = $data['gameHash'];
        $now = Carbon::now();

        $unlock1Date = $now->clone()->subMinutes(65);
        $this->addHardcoreUnlock($this->user, $achievement1, $unlock1Date, $gameHash);

        // do the hardcore unlock
        $validationHash = AwardAchievementTestHelpers::buildValidationHash($achievement3, $this->user, 1);
        $scoreBefore = $this->user->points_hardcore;
        $casualScoreBefore = $this->user->points;
        $truePointsBefore = $this->user->points_weighted;

        $this->withHeaders(['User-Agent' => $this->userAgentUnknown])
            ->get($this->apiUrl('awardachievement', [
                'a' => $achievement3->id,
                'h' => 1,
                'm' => $gameHash->md5,
                'v' => $validationHash,
            ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement3->id,
                'AchievementsRemaining' => 4,
                'Score' => $scoreBefore,
                'SoftcoreScore' => $casualScoreBefore + $achievement3->points,
            ]);
        $this->user->refresh();

        // achievement unlocked
        $this->assertHasCasualUnlock($this->user, $achievement3);
        $this->assertDoesNotHaveHardcoreUnlock($this->user, $achievement3);

        // player score should have increased
        $user1 = User::whereName($this->user->username)->first();
        $this->assertEquals($scoreBefore, $user1->points_hardcore);
        $this->assertEquals($casualScoreBefore + $achievement3->points, $user1->points);

        $warning = AwardAchievementTestHelpers::getWarning($achievement3);
        $this->assertEquals($this->user->username, $warning->username);
        $this->assertEquals(0, $warning->offset);
        $this->assertEquals($validationHash, $warning->validation_hash);
        $this->assertEquals('unknown_client', $warning->smells);
        $this->assertEquals($this->userAgentUnknown, $warning->user_agent);
    });

    test('unknown user agent allowed in casual', function () {
        $data = AwardAchievementTestHelpers::createGame();
        $game = $data['game'];
        $achievement1 = $data['achievements'][0];
        $achievement3 = $data['achievements'][2];
        $gameHash = $data['gameHash'];
        $now = Carbon::now();

        $unlock1Date = $now->clone()->subMinutes(65);
        $this->addCasualUnlock($this->user, $achievement1, $unlock1Date, $gameHash);

        // do the unlock
        $validationHash = AwardAchievementTestHelpers::buildValidationHash($achievement3, $this->user, 0);
        $scoreBefore = $this->user->points_hardcore;
        $casualScoreBefore = $this->user->points;
        $truePointsBefore = $this->user->points_weighted;

        $this->withHeaders(['User-Agent' => $this->userAgentUnknown])
            ->get($this->apiUrl('awardachievement', [
                'a' => $achievement3->id,
                'h' => 0,
                'm' => $gameHash->md5,
                'v' => $validationHash,
            ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement3->id,
                'AchievementsRemaining' => 4,
                'Score' => $scoreBefore,
                'SoftcoreScore' => $casualScoreBefore + $achievement3->points,
            ]);
        $this->user->refresh();

        // achievement unlocked
        $this->assertHasCasualUnlock($this->user, $achievement3);
        $this->assertDoesNotHaveHardcoreUnlock($this->user, $achievement3);

        // player score should have increased
        $user1 = User::whereName($this->user->username)->first();
        $this->assertEquals($scoreBefore, $user1->points_hardcore);
        $this->assertEquals($casualScoreBefore + $achievement3->points, $user1->points);

        $warning = AwardAchievementTestHelpers::getWarning($achievement3);
        $this->assertEquals($this->user->username, $warning->username);
        $this->assertEquals(0, $warning->offset);
        $this->assertEquals($validationHash, $warning->validation_hash);
        $this->assertEquals('unknown_client', $warning->smells);
        $this->assertEquals($this->userAgentUnknown, $warning->user_agent);
    });

    test('unsupported user agent demotes hardcore unlock to casual', function () {
        $data = AwardAchievementTestHelpers::createGame();
        $game = $data['game'];
        $achievement1 = $data['achievements'][0];
        $achievement3 = $data['achievements'][2];
        $gameHash = $data['gameHash'];
        $now = Carbon::now();

        $unlock1Date = $now->clone()->subMinutes(65);
        $this->addHardcoreUnlock($this->user, $achievement1, $unlock1Date, $gameHash);

        // do the hardcore unlock
        $validationHash = AwardAchievementTestHelpers::buildValidationHash($achievement3, $this->user, 1);
        $scoreBefore = $this->user->points_hardcore;
        $casualScoreBefore = $this->user->points;
        $truePointsBefore = $this->user->points_weighted;

        $this->withHeaders(['User-Agent' => $this->userAgentUnsupported])
            ->get($this->apiUrl('awardachievement', [
                'a' => $achievement3->id,
                'h' => 1,
                'm' => $gameHash->md5,
                'v' => $validationHash,
            ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement3->id,
                'AchievementsRemaining' => 4,
                'Score' => $scoreBefore,
                'SoftcoreScore' => $casualScoreBefore + $achievement3->points,
            ]);
        $this->user->refresh();

        // achievement unlocked
        $this->assertHasCasualUnlock($this->user, $achievement3);
        $this->assertDoesNotHaveHardcoreUnlock($this->user, $achievement3);

        // player score should have increased
        $user1 = User::whereName($this->user->username)->first();
        $this->assertEquals($scoreBefore, $user1->points_hardcore);
        $this->assertEquals($casualScoreBefore + $achievement3->points, $user1->points);

        $this->assertEquals(0, ConnectWarning::count());
    });

    test('unsupported user agent allowed in casual', function () {
        $data = AwardAchievementTestHelpers::createGame();
        $game = $data['game'];
        $achievement1 = $data['achievements'][0];
        $achievement3 = $data['achievements'][2];
        $gameHash = $data['gameHash'];
        $now = Carbon::now();

        $unlock1Date = $now->clone()->subMinutes(65);
        $this->addCasualUnlock($this->user, $achievement1, $unlock1Date, $gameHash);

        // do the unlock
        $validationHash = AwardAchievementTestHelpers::buildValidationHash($achievement3, $this->user, 0);
        $scoreBefore = $this->user->points_hardcore;
        $casualScoreBefore = $this->user->points;
        $truePointsBefore = $this->user->points_weighted;

        $this->withHeaders(['User-Agent' => $this->userAgentUnsupported])
            ->get($this->apiUrl('awardachievement', [
                'a' => $achievement3->id,
                'h' => 0,
                'm' => $gameHash->md5,
                'v' => $validationHash,
            ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement3->id,
                'AchievementsRemaining' => 4,
                'Score' => $scoreBefore,
                'SoftcoreScore' => $casualScoreBefore + $achievement3->points,
            ]);
        $this->user->refresh();

        // achievement unlocked
        $this->assertHasCasualUnlock($this->user, $achievement3);
        $this->assertDoesNotHaveHardcoreUnlock($this->user, $achievement3);

        // player score should have increased
        $user1 = User::whereName($this->user->username)->first();
        $this->assertEquals($scoreBefore, $user1->points_hardcore);
        $this->assertEquals($casualScoreBefore + $achievement3->points, $user1->points);

        $this->assertEquals(0, ConnectWarning::count());
    });

    test('outdated user agent demotes hardcore unlock to casual', function () {
        $data = AwardAchievementTestHelpers::createGame();
        $game = $data['game'];
        $achievement1 = $data['achievements'][0];
        $achievement3 = $data['achievements'][2];
        $gameHash = $data['gameHash'];
        $now = Carbon::now();

        $unlock1Date = $now->clone()->subMinutes(65);
        $this->addHardcoreUnlock($this->user, $achievement1, $unlock1Date, $gameHash);

        // do the hardcore unlock
        $validationHash = AwardAchievementTestHelpers::buildValidationHash($achievement3, $this->user, 1);
        $scoreBefore = $this->user->points_hardcore;
        $casualScoreBefore = $this->user->points;
        $truePointsBefore = $this->user->points_weighted;

        $this->withHeaders(['User-Agent' => $this->userAgentOutdated])
            ->get($this->apiUrl('awardachievement', [
                'a' => $achievement3->id,
                'h' => 1,
                'm' => $gameHash->md5,
                'v' => $validationHash,
            ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement3->id,
                'AchievementsRemaining' => 4,
                'Score' => $scoreBefore,
                'SoftcoreScore' => $casualScoreBefore + $achievement3->points,
            ]);
        $this->user->refresh();

        // achievement unlocked
        $this->assertHasCasualUnlock($this->user, $achievement3);
        $this->assertDoesNotHaveHardcoreUnlock($this->user, $achievement3);

        // player score should have increased
        $user1 = User::whereName($this->user->username)->first();
        $this->assertEquals($scoreBefore, $user1->points_hardcore);
        $this->assertEquals($casualScoreBefore + $achievement3->points, $user1->points);

        $this->assertEquals(0, ConnectWarning::count());
    });

    test('outdated user agent allowed in casual', function () {
        $data = AwardAchievementTestHelpers::createGame();
        $game = $data['game'];
        $achievement1 = $data['achievements'][0];
        $achievement3 = $data['achievements'][2];
        $gameHash = $data['gameHash'];
        $now = Carbon::now();

        $unlock1Date = $now->clone()->subMinutes(65);
        $this->addCasualUnlock($this->user, $achievement1, $unlock1Date, $gameHash);

        // do the unlock
        $validationHash = AwardAchievementTestHelpers::buildValidationHash($achievement3, $this->user, 0);
        $scoreBefore = $this->user->points_hardcore;
        $casualScoreBefore = $this->user->points;
        $truePointsBefore = $this->user->points_weighted;

        $this->withHeaders(['User-Agent' => $this->userAgentOutdated])
            ->get($this->apiUrl('awardachievement', [
                'a' => $achievement3->id,
                'h' => 0,
                'm' => $gameHash->md5,
                'v' => $validationHash,
            ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement3->id,
                'AchievementsRemaining' => 4,
                'Score' => $scoreBefore,
                'SoftcoreScore' => $casualScoreBefore + $achievement3->points,
            ]);
        $this->user->refresh();

        // achievement unlocked
        $this->assertHasCasualUnlock($this->user, $achievement3);
        $this->assertDoesNotHaveHardcoreUnlock($this->user, $achievement3);

        // player score should have increased
        $user1 = User::whereName($this->user->username)->first();
        $this->assertEquals($scoreBefore, $user1->points_hardcore);
        $this->assertEquals($casualScoreBefore + $achievement3->points, $user1->points);

        $this->assertEquals(0, ConnectWarning::count());
    });

    test('user agent pending change to outdated still unlocks in hardcore', function () {
        $data = AwardAchievementTestHelpers::createGame();
        $game = $data['game'];
        $achievement1 = $data['achievements'][0];
        $achievement3 = $data['achievements'][2];
        $gameHash = $data['gameHash'];
        $now = Carbon::now();

        $emulatorUserAgent = EmulatorUserAgent::where('client', 'MyClient')->first();
        $emulatorUserAgent->pending_minimum_hardcore_version = '1.6';
        $emulatorUserAgent->pending_minimum_hardcore_version_at = Carbon::now()->addDays(5);
        $emulatorUserAgent->save();

        $unlock1Date = $now->clone()->subMinutes(65);
        $this->addHardcoreUnlock($this->user, $achievement1, $unlock1Date, $gameHash);

        // do the hardcore unlock
        $validationHash = AwardAchievementTestHelpers::buildValidationHash($achievement3, $this->user, 1);
        $scoreBefore = $this->user->points_hardcore;
        $casualScoreBefore = $this->user->points;
        $truePointsBefore = $this->user->points_weighted;

        $this->withHeaders(['User-Agent' => 'MyClient/1.5'])
            ->get($this->apiUrl('awardachievement', [
                'a' => $achievement3->id,
                'h' => 1,
                'm' => $gameHash->md5,
                'v' => $validationHash,
            ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement3->id,
                'AchievementsRemaining' => 4,
                'Score' => $scoreBefore + $achievement3->points,
                'SoftcoreScore' => $casualScoreBefore,
            ]);
        $this->user->refresh();

        // achievement unlocked
        $this->assertHasCasualUnlock($this->user, $achievement3);
        $this->assertHasHardcoreUnlock($this->user, $achievement3);

        // player score should have increased
        $user1 = User::whereName($this->user->username)->first();
        $this->assertEquals($scoreBefore + $achievement3->points, $user1->points_hardcore);
        $this->assertEquals($casualScoreBefore, $user1->points);

        $this->assertEquals(0, ConnectWarning::count());
    });

    test('blocked user agent cannot unlock hardcore achievements', function () {
        $data = AwardAchievementTestHelpers::createGame();
        $achievement1 = $data['achievements'][0];
        $achievement1->is_promoted = false;
        $achievement1->save();
        $gameHash = $data['gameHash'];
        $now = Carbon::now();

        // do the hardcore unlock
        $validationHash = AwardAchievementTestHelpers::buildValidationHash($achievement1, $this->user, 1);
        $scoreBefore = $this->user->points_hardcore;
        $casualScoreBefore = $this->user->points;
        $truePointsBefore = $this->user->points_weighted;

        $this->withHeaders(['User-Agent' => $this->userAgentBlocked])
            ->get($this->apiUrl('awardachievement', [
                'a' => $achievement1->id,
                'h' => 1,
                'm' => $gameHash->md5,
                'v' => $validationHash,
            ]))
            ->assertStatus(403)
            ->assertExactJson([
                'Success' => false,
                'Code' => 'unsupported_client',
                'Error' => 'This client is not supported.',
                'Status' => 403,
            ]);

        // player score should not have increased
        $user2 = User::whereName($this->user->username)->first();
        $this->assertEquals($scoreBefore, $user2->points_hardcore);
        $this->assertEquals($casualScoreBefore, $user2->points);
        $this->assertEquals($truePointsBefore, $user2->points_weighted);

        // make sure the unlock didn't happen
        $this->assertEquals(0, PlayerAchievement::count());

        $warning = AwardAchievementTestHelpers::getWarning($achievement1);
        $this->assertEquals($this->user->username, $warning->username);
        $this->assertEquals(0, $warning->offset);
        $this->assertEquals($validationHash, $warning->validation_hash);
        $this->assertEquals('blocked_client', $warning->smells);
        $this->assertEquals($this->userAgentBlocked, $warning->user_agent);
    });

    test('blocked user agent cannot unlock casual achievements', function () {
        $data = AwardAchievementTestHelpers::createGame();
        $achievement1 = $data['achievements'][0];
        $achievement1->is_promoted = false;
        $achievement1->save();
        $gameHash = $data['gameHash'];
        $now = Carbon::now();

        // do the unlock
        $validationHash = AwardAchievementTestHelpers::buildValidationHash($achievement1, $this->user, 0);
        $scoreBefore = $this->user->points_hardcore;
        $casualScoreBefore = $this->user->points;
        $truePointsBefore = $this->user->points_weighted;

        $this->withHeaders(['User-Agent' => $this->userAgentBlocked])
            ->get($this->apiUrl('awardachievement', [
                'a' => $achievement1->id,
                'h' => 0,
                'm' => $gameHash->md5,
                'v' => $validationHash,
            ]))
            ->assertStatus(403)
            ->assertExactJson([
                'Success' => false,
                'Code' => 'unsupported_client',
                'Error' => 'This client is not supported.',
                'Status' => 403,
            ]);

        // player score should not have increased
        $user2 = User::whereName($this->user->username)->first();
        $this->assertEquals($scoreBefore, $user2->points_hardcore);
        $this->assertEquals($casualScoreBefore, $user2->points);
        $this->assertEquals($truePointsBefore, $user2->points_weighted);

        // make sure the unlock didn't happen
        $this->assertEquals(0, PlayerAchievement::count());

        $warning = AwardAchievementTestHelpers::getWarning($achievement1);
        $this->assertEquals($this->user->username, $warning->username);
        $this->assertEquals(0, $warning->offset);
        $this->assertEquals($validationHash, $warning->validation_hash);
        $this->assertEquals('blocked_client', $warning->smells);
        $this->assertEquals($this->userAgentBlocked, $warning->user_agent);
    });

    test('user agent for incorrect system still unlocks hardcore achievements', function () {
        $data = AwardAchievementTestHelpers::createGame();
        $game = $data['game'];
        $achievement1 = $data['achievements'][0];
        $achievement3 = $data['achievements'][2];
        $gameHash = $data['gameHash'];
        $now = Carbon::now();

        $unlock1Date = $now->clone()->subMinutes(65);
        $this->addHardcoreUnlock($this->user, $achievement1, $unlock1Date, $gameHash);

        AwardAchievementTestHelpers::initializeFakeQueue();

        // $userAgentValid is associated to the "Test Client". Attach a differing system to that.
        $otherSystem = System::factory()->create();
        Emulator::where('name', 'Test Client')->first()->systems()->attach($otherSystem->id);

        // do the hardcore unlock
        $validationHash = AwardAchievementTestHelpers::buildValidationHash($achievement3, $this->user, 1);
        $scoreBefore = $this->user->points_hardcore;
        $casualScoreBefore = $this->user->points;
        $truePointsBefore = $this->user->points_weighted;

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('awardachievement', [
                'a' => $achievement3->id,
                'h' => 1,
                'm' => $gameHash->md5,
                'v' => $validationHash,
            ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement3->id,
                'AchievementsRemaining' => 4,
                'Score' => $scoreBefore + $achievement3->points,
                'SoftcoreScore' => $casualScoreBefore,
            ]);
        $this->user->refresh();

        // achievement unlock should go through as hardcore
        $this->assertHasCasualUnlock($this->user, $achievement3);
        $this->assertHasHardcoreUnlock($this->user, $achievement3);

        // player score should have increased
        $user1 = User::whereName($this->user->username)->first();
        $this->assertEquals($scoreBefore + $achievement3->points, $user1->points_hardcore);
        $this->assertEquals($casualScoreBefore, $user1->points);

        // smell should be captured
        $warning = AwardAchievementTestHelpers::getWarning($achievement3);
        $this->assertEquals($this->user->username, $warning->username);
        $this->assertEquals(0, $warning->offset);
        $this->assertEquals($validationHash, $warning->validation_hash);
        $this->assertEquals('wrong_client', $warning->smells);
        $this->assertEquals($this->userAgentValid, $warning->user_agent);

        // notification should be sent
        Queue::assertPushedOn('alerts', SendAlertWebhookJob::class, function ($job) {
            return $job->webhookUrl === 'https://discord.com/api/webhooks/test';
        });
    });

    test('user agent for incorrect system for event game does not generate warning', function () {
        $data = AwardAchievementTestHelpers::createGame();
        System::factory()->create(['id' => System::Events]);
        $game = $data['game'];
        $game->system_id = System::Events;
        $game->save();
        $achievement1 = $data['achievements'][0];
        $achievement3 = $data['achievements'][2];
        $gameHash = $data['gameHash'];
        $now = Carbon::now();

        $unlock1Date = $now->clone()->subMinutes(65);
        $this->addHardcoreUnlock($this->user, $achievement1, $unlock1Date, $gameHash);

        // $userAgentValid is associated to the "Test Client". Attach a differing system to that.
        $otherSystem = System::factory()->create();
        Emulator::where('name', 'Test Client')->first()->systems()->attach($otherSystem->id);

        // do the hardcore unlock
        $validationHash = AwardAchievementTestHelpers::buildValidationHash($achievement3, $this->user, 1);
        $scoreBefore = $this->user->points_hardcore;
        $casualScoreBefore = $this->user->points;
        $truePointsBefore = $this->user->points_weighted;

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('awardachievement', [
                'a' => $achievement3->id,
                'h' => 1,
                'm' => $gameHash->md5,
                'v' => $validationHash,
            ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement3->id,
                'AchievementsRemaining' => 4,
                'Score' => $scoreBefore, // event achievement does not award points
                'SoftcoreScore' => $casualScoreBefore,
            ]);
        $this->user->refresh();

        // achievement unlock should go through as hardcore
        $this->assertHasCasualUnlock($this->user, $achievement3);
        $this->assertHasHardcoreUnlock($this->user, $achievement3);

        // player score should not have increased
        $user1 = User::whereName($this->user->username)->first();
        $this->assertEquals($scoreBefore, $user1->points_hardcore);
        $this->assertEquals($casualScoreBefore, $user1->points);

        // smell should not be captured
        $this->assertEquals(0, ConnectWarning::count());
    });

    test('user agent for incorrect system on multi-system client does not generate warning', function () {
        $data = AwardAchievementTestHelpers::createGame();
        $game = $data['game'];
        $achievement1 = $data['achievements'][0];
        $achievement3 = $data['achievements'][2];
        $gameHash = $data['gameHash'];
        $now = Carbon::now();

        $unlock1Date = $now->clone()->subMinutes(65);
        $this->addHardcoreUnlock($this->user, $achievement1, $unlock1Date, $gameHash);

        // $userAgentValid is associated to the "Test Client". Attach differing systems to that.
        $emulator = Emulator::where('name', 'Test Client')->first();
        for ($i = 0; $i < 5; $i++) {
            $otherSystem = System::factory()->create();
            $emulator->systems()->attach($otherSystem->id);
        }

        // do the hardcore unlock
        $validationHash = AwardAchievementTestHelpers::buildValidationHash($achievement3, $this->user, 1);
        $scoreBefore = $this->user->points_hardcore;
        $casualScoreBefore = $this->user->points;
        $truePointsBefore = $this->user->points_weighted;

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('awardachievement', [
                'a' => $achievement3->id,
                'h' => 1,
                'm' => $gameHash->md5,
                'v' => $validationHash,
            ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement3->id,
                'AchievementsRemaining' => 4,
                'Score' => $scoreBefore + $achievement3->points,
                'SoftcoreScore' => $casualScoreBefore,
            ]);
        $this->user->refresh();

        // achievement unlock should go through as hardcore
        $this->assertHasCasualUnlock($this->user, $achievement3);
        $this->assertHasHardcoreUnlock($this->user, $achievement3);

        // player score should have increased
        $user1 = User::whereName($this->user->username)->first();
        $this->assertEquals($scoreBefore + $achievement3->points, $user1->points_hardcore);
        $this->assertEquals($casualScoreBefore, $user1->points);

        // smell should not be captured
        $this->assertEquals(0, ConnectWarning::count());
    });

    test('no validation hash still unlocks hardcore achievements', function () {
        $data = AwardAchievementTestHelpers::createGame();
        $game = $data['game'];
        $achievement1 = $data['achievements'][0];
        $achievement3 = $data['achievements'][2];
        $gameHash = $data['gameHash'];
        $now = Carbon::now();

        $unlock1Date = $now->clone()->subMinutes(65);
        $this->addHardcoreUnlock($this->user, $achievement1, $unlock1Date, $gameHash);

        // do the hardcore unlock
        $scoreBefore = $this->user->points_hardcore;
        $casualScoreBefore = $this->user->points;
        $truePointsBefore = $this->user->points_weighted;

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('awardachievement', [
                'a' => $achievement3->id,
                'h' => 1,
                'm' => $gameHash->md5,
            ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement3->id,
                'AchievementsRemaining' => 4,
                'Score' => $scoreBefore + $achievement3->points,
                'SoftcoreScore' => $casualScoreBefore,
            ]);
        $this->user->refresh();

        // achievement unlock should go through as hardcore
        $this->assertHasCasualUnlock($this->user, $achievement3);
        $this->assertHasHardcoreUnlock($this->user, $achievement3);

        // player score should have increased
        $user1 = User::whereName($this->user->username)->first();
        $this->assertEquals($scoreBefore + $achievement3->points, $user1->points_hardcore);
        $this->assertEquals($casualScoreBefore, $user1->points);

        // smell should be captured
        $warning = AwardAchievementTestHelpers::getWarning($achievement3);
        $this->assertEquals($this->user->username, $warning->username);
        $this->assertEquals(0, $warning->offset);
        $this->assertEquals('', $warning->validation_hash);
        $this->assertEquals('no_validation', $warning->smells);
        $this->assertEquals($this->userAgentValid, $warning->user_agent);
    });

    test('invalid validation hash still unlocks hardcore achievements', function () {
        $data = AwardAchievementTestHelpers::createGame();
        $game = $data['game'];
        $achievement1 = $data['achievements'][0];
        $achievement3 = $data['achievements'][2];
        $gameHash = $data['gameHash'];
        $now = Carbon::now();

        $unlock1Date = $now->clone()->subMinutes(65);
        $this->addHardcoreUnlock($this->user, $achievement1, $unlock1Date, $gameHash);

        AwardAchievementTestHelpers::initializeFakeQueue();

        // do the hardcore unlock
        $validationHash = md5('This is the wrong validation hash');
        $scoreBefore = $this->user->points_hardcore;
        $casualScoreBefore = $this->user->points;
        $truePointsBefore = $this->user->points_weighted;

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('awardachievement', [
                'a' => $achievement3->id,
                'h' => 1,
                'm' => $gameHash->md5,
                'v' => $validationHash,
            ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement3->id,
                'AchievementsRemaining' => 4,
                'Score' => $scoreBefore + $achievement3->points,
                'SoftcoreScore' => $casualScoreBefore,
            ]);
        $this->user->refresh();

        // achievement unlock should go through as hardcore
        $this->assertHasCasualUnlock($this->user, $achievement3);
        $this->assertHasHardcoreUnlock($this->user, $achievement3);

        // player score should have increased
        $user1 = User::whereName($this->user->username)->first();
        $this->assertEquals($scoreBefore + $achievement3->points, $user1->points_hardcore);
        $this->assertEquals($casualScoreBefore, $user1->points);

        // smell should be captured
        $warning = AwardAchievementTestHelpers::getWarning($achievement3);
        $this->assertEquals($this->user->username, $warning->username);
        $this->assertEquals(0, $warning->offset);
        $this->assertEquals($validationHash, $warning->validation_hash);
        $this->assertEquals('bad_validation', $warning->smells);
        $this->assertEquals($this->userAgentValid, $warning->user_agent);

        // notification should not be sent
        Queue::assertNotPushed(SendAlertWebhookJob::class);
    });

    test('validation hash containing 0 offset still unlocks hardcore achievements', function () {
        $data = AwardAchievementTestHelpers::createGame();
        $game = $data['game'];
        $achievement1 = $data['achievements'][0];
        $achievement3 = $data['achievements'][2];
        $gameHash = $data['gameHash'];
        $now = Carbon::now();

        $unlock1Date = $now->clone()->subMinutes(65);
        $this->addHardcoreUnlock($this->user, $achievement1, $unlock1Date, $gameHash);

        // do the hardcore unlock
        $validationHash = md5($achievement3->id . $this->user->username . '1' . $achievement3->id . '0');
        $scoreBefore = $this->user->points_hardcore;
        $casualScoreBefore = $this->user->points;
        $truePointsBefore = $this->user->points_weighted;

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('awardachievement', [
                'a' => $achievement3->id,
                'h' => 1,
                'm' => $gameHash->md5,
                'v' => $validationHash,
            ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement3->id,
                'AchievementsRemaining' => 4,
                'Score' => $scoreBefore + $achievement3->points,
                'SoftcoreScore' => $casualScoreBefore,
            ]);
        $this->user->refresh();

        // achievement unlock should go through as hardcore
        $this->assertHasCasualUnlock($this->user, $achievement3);
        $this->assertHasHardcoreUnlock($this->user, $achievement3);

        // player score should have increased
        $user1 = User::whereName($this->user->username)->first();
        $this->assertEquals($scoreBefore + $achievement3->points, $user1->points_hardcore);
        $this->assertEquals($casualScoreBefore, $user1->points);

        // smell should not be captured
        $this->assertEquals(0, ConnectWarning::count());
    });

    test('validation hash honors provided casing', function () {
        $data = AwardAchievementTestHelpers::createGame();
        $game = $data['game'];
        $achievement1 = $data['achievements'][0];
        $achievement3 = $data['achievements'][2];
        $gameHash = $data['gameHash'];
        $now = Carbon::now();

        $unlock1Date = $now->clone()->subMinutes(65);
        $this->addHardcoreUnlock($this->user, $achievement1, $unlock1Date, $gameHash);

        // do the hardcore unlock
        $validationHash = md5($achievement3->id . strtoupper($this->user->username) . '1' . $achievement3->id . '0');
        $scoreBefore = $this->user->points_hardcore;
        $casualScoreBefore = $this->user->points;
        $truePointsBefore = $this->user->points_weighted;

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('awardachievement', [
                'u' => strtoupper($this->user->username),
                't' => $this->user->connect_token,
                'a' => $achievement3->id,
                'h' => 1,
                'm' => $gameHash->md5,
                'v' => $validationHash,
            ], credentials: false))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement3->id,
                'AchievementsRemaining' => 4,
                'Score' => $scoreBefore + $achievement3->points,
                'SoftcoreScore' => $casualScoreBefore,
            ]);
        $this->user->refresh();

        // achievement unlock should go through as hardcore
        $this->assertHasCasualUnlock($this->user, $achievement3);
        $this->assertHasHardcoreUnlock($this->user, $achievement3);

        // player score should have increased
        $user1 = User::whereName($this->user->username)->first();
        $this->assertEquals($scoreBefore + $achievement3->points, $user1->points_hardcore);
        $this->assertEquals($casualScoreBefore, $user1->points);

        // smell should not be captured
        $this->assertEquals(0, ConnectWarning::count());
    });

    test('repeated invalid validation hash on different achievements creates warning', function () {
        $data = AwardAchievementTestHelpers::createGame();
        $game = $data['game'];
        $achievement1 = $data['achievements'][0];
        $achievement3 = $data['achievements'][2];
        $gameHash = $data['gameHash'];
        $now = Carbon::now();

        $unlock1Date = $now->clone()->subMinutes(65);
        $this->addHardcoreUnlock($this->user, $achievement1, $unlock1Date, $gameHash);

        AwardAchievementTestHelpers::initializeFakeQueue();

        // do the hardcore unlock
        $validationHash = md5('This is the wrong validation hash');
        $scoreBefore = $this->user->points_hardcore;
        $casualScoreBefore = $this->user->points;
        $truePointsBefore = $this->user->points_weighted;

        ConnectWarning::create([
            'method' => 'awardachievement',
            'username' => $this->user->username,
            'related_type' => 'achievement',
            'related_id' => $achievement1->id,
            'hardcore' => 1,
            'validation_hash' => $validationHash,
            'smells' => 'bad_validation',
            'user_agent' => $this->userAgentValid,
        ]);

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('awardachievement', [
                'a' => $achievement3->id,
                'h' => 1,
                'm' => $gameHash->md5,
                'v' => $validationHash,
            ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement3->id,
                'AchievementsRemaining' => 4,
                'Score' => $scoreBefore + $achievement3->points,
                'SoftcoreScore' => $casualScoreBefore,
            ]);
        $this->user->refresh();

        // achievement unlock should go through as hardcore
        $this->assertHasCasualUnlock($this->user, $achievement3);
        $this->assertHasHardcoreUnlock($this->user, $achievement3);

        // player score should have increased
        $user1 = User::whereName($this->user->username)->first();
        $this->assertEquals($scoreBefore + $achievement3->points, $user1->points_hardcore);
        $this->assertEquals($casualScoreBefore, $user1->points);

        // smell should be captured
        $warning = AwardAchievementTestHelpers::getWarning($achievement3);
        $this->assertEquals($this->user->username, $warning->username);
        $this->assertEquals(0, $warning->offset);
        $this->assertEquals($validationHash, $warning->validation_hash);
        $this->assertEquals('bad_validation,repeated_validation', $warning->smells);
        $this->assertEquals($this->userAgentValid, $warning->user_agent);

        // notification should be sent
        Queue::assertPushedOn('alerts', SendAlertWebhookJob::class, function ($job) {
            return $job->webhookUrl === 'https://discord.com/api/webhooks/test';
        });
    });

    test('only one warning notification per user per day', function () {
        $data = AwardAchievementTestHelpers::createGame();
        $game = $data['game'];
        $achievement1 = $data['achievements'][0];
        $achievement3 = $data['achievements'][2];
        $achievement4 = $data['achievements'][3];
        $gameHash = $data['gameHash'];
        $now = Carbon::now();

        $unlock1Date = $now->clone()->subMinutes(65);
        $this->addHardcoreUnlock($this->user, $achievement1, $unlock1Date, $gameHash);

        AwardAchievementTestHelpers::initializeFakeQueue();

        // do the hardcore unlock
        $validationHash = md5('This is the wrong validation hash');
        $scoreBefore = $this->user->points_hardcore;
        $casualScoreBefore = $this->user->points;
        $truePointsBefore = $this->user->points_weighted;

        ConnectWarning::create([
            'method' => 'awardachievement',
            'username' => $this->user->username,
            'related_type' => 'achievement',
            'related_id' => $achievement1->id,
            'hardcore' => 1,
            'validation_hash' => $validationHash,
            'smells' => 'bad_validation',
            'user_agent' => $this->userAgentValid,
        ]);

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('awardachievement', [
                'a' => $achievement3->id,
                'h' => 1,
                'm' => $gameHash->md5,
                'v' => $validationHash,
            ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement3->id,
                'AchievementsRemaining' => 4,
                'Score' => $scoreBefore + $achievement3->points,
                'SoftcoreScore' => $casualScoreBefore,
            ]);

        // notification should be sent
        Queue::assertPushedOn('alerts', SendAlertWebhookJob::class, function ($job) {
            return $job->webhookUrl === 'https://discord.com/api/webhooks/test';
        });

        AwardAchievementTestHelpers::initializeFakeQueue();

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('awardachievement', [
                'a' => $achievement4->id,
                'h' => 1,
                'm' => $gameHash->md5,
                'v' => $validationHash,
            ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement4->id,
                'AchievementsRemaining' => 3,
                'Score' => $scoreBefore + $achievement3->points + $achievement4->points,
                'SoftcoreScore' => $casualScoreBefore,
            ]);

        // notification should not be sent
        Queue::assertNotPushed(SendAlertWebhookJob::class);
    });
});
