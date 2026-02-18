<?php

declare(strict_types=1);

namespace Tests\Feature\Connect;

use App\Enums\GameHashCompatibility;
use App\Enums\Permissions;
use App\Models\Achievement;
use App\Models\EventAchievement;
use App\Models\Game;
use App\Models\PlayerSession;
use App\Models\System;
use App\Models\User;
use App\Platform\Actions\UpsertGameCoreAchievementSetFromLegacyFlagsAction;
use App\Platform\Enums\AchievementSetType;
use App\Platform\Services\VirtualGameIdService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\Feature\Concerns\TestsEmulatorUserAgent;
use Tests\Feature\Platform\Concerns\TestsPlayerAchievements;

uses(LazilyRefreshDatabase::class);
uses(TestsConnect::class);
uses(TestsEmulatorUserAgent::class);
uses(TestsPlayerAchievements::class);

class StartSessionTestHelpers
{
    public static function createEventAchievement(Achievement $sourceAchievement, ?Carbon $activeFrom = null, ?Carbon $activeUntil = null): Achievement
    {
        if (!System::where('id', System::Events)->exists()) {
            System::factory()->create(['id' => System::Events]);
        }
        $eventGame = Game::factory()->create(['system_id' => System::Events]);
        $eventAchievement = Achievement::factory()->promoted()->create(['game_id' => $eventGame->id]);

        (new UpsertGameCoreAchievementSetFromLegacyFlagsAction())->execute($eventGame);

        $ea = EventAchievement::create([
            'achievement_id' => $eventAchievement->id,
            'source_achievement_id' => $sourceAchievement->id,
            'active_from' => $activeFrom,
            'active_until' => $activeUntil,
        ]);

        return $eventAchievement;
    }
}

beforeEach(function () {
    Carbon::setTestNow(Carbon::now());

    $this->seedEmulatorUserAgents();
    $this->createConnectUser();
});

describe('session', function () {
    test('creates first session', function () {
        $game = $this->seedGame(achievements: 4);
        $gameHash = $game->hashes()->first();

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('startsession', ['g' => $game->id, 'm' => $gameHash->md5]))
            ->assertExactJson([
                'Success' => true,
                'HardcoreUnlocks' => [],
                'Unlocks' => [],
                'ServerNow' => Carbon::now()->timestamp,
            ]);

        // player session created
        $playerSession = PlayerSession::where([
            'user_id' => $this->user->id,
            'game_id' => $game->id,
        ])->first();
        $this->assertModelExists($playerSession);
        $this->assertEquals(1, $playerSession->duration);
        $this->assertEquals('Playing ' . $game->title, $playerSession->rich_presence);
        $this->assertEquals($this->userAgentValid, $playerSession->user_agent);
        $this->assertEquals($gameHash->id, $playerSession->game_hash_id);

        $this->user->refresh();
        $this->assertEquals($game->id, $this->user->rich_presence_game_id);
        $this->assertEquals("Playing " . $game->title, $this->user->rich_presence);
    });

    test('extends recent session', function () {
        $now = Carbon::create(2020, 3, 4, 16, 40, 13); // 4:40:13pm 4 Mar 2020
        $then = $now->clone()->subMinutes(5);
        Carbon::setTestNow($then);

        $game = $this->seedGame(achievements: 4);
        $gameHash = $game->hashes()->first();

        $playerSession = PlayerSession::factory()->create([
            'user_id' => $this->user->id,
            'game_id' => $game->id,
            'game_hash_id' => $gameHash->id,
            'rich_presence' => 'Playing ' . $game->title,
            'rich_presence_updated_at' => $then,
            'duration' => 1,
            'user_agent' => $this->userAgentValid,
        ])->first();

        Carbon::setTestNow($now);

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('startsession', ['g' => $game->id, 'm' => $gameHash->md5]))
            ->assertExactJson([
                'Success' => true,
                'HardcoreUnlocks' => [],
                'Unlocks' => [],
                'ServerNow' => Carbon::now()->timestamp,
            ]);

        // player session extended
        $playerSessionCount = PlayerSession::where([
            'user_id' => $this->user->id,
            'game_id' => $game->id,
        ])->count();
        $this->assertEquals(1, $playerSessionCount);

        $playerSession->refresh();
        $this->assertEquals(5, $playerSession->duration);
        $this->assertEquals('Playing ' . $game->title, $playerSession->rich_presence);
    });

    test('creates new session after long absence', function () {
        $now = Carbon::create(2020, 3, 4, 16, 40, 13); // 4:40:13pm 4 Mar 2020
        $then = $now->clone()->subHours(4);
        Carbon::setTestNow($then);

        $game = $this->seedGame(achievements: 4);
        $gameHash = $game->hashes()->first();

        $playerSession = PlayerSession::factory()->create([
            'user_id' => $this->user->id,
            'game_id' => $game->id,
            'game_hash_id' => $gameHash->id,
            'rich_presence' => 'Playing ' . $game->title,
            'rich_presence_updated_at' => $then,
            'duration' => 1,
            'user_agent' => $this->userAgentValid,
        ])->first();

        Carbon::setTestNow($now);

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('startsession', ['g' => $game->id, 'm' => $gameHash->md5]))
            ->assertExactJson([
                'Success' => true,
                'HardcoreUnlocks' => [],
                'Unlocks' => [],
                'ServerNow' => Carbon::now()->timestamp,
            ]);

        // player session extended
        $playerSessionCount = PlayerSession::where([
            'user_id' => $this->user->id,
            'game_id' => $game->id,
        ])->count();
        $this->assertEquals(2, $playerSessionCount);

        $playerSession->refresh();
        $this->assertEquals(1, $playerSession->duration);
        $this->assertEquals('Playing ' . $game->title, $playerSession->rich_presence);

        $playerSession2 = PlayerSession::where([
            'user_id' => $this->user->id,
            'game_id' => $game->id,
        ])->orderByDesc('id')->first();
        $this->assertModelExists($playerSession2);
        $this->assertNotEquals($playerSession->id, $playerSession2->id);
        $this->assertEquals(1, $playerSession2->duration);
        $this->assertEquals('Playing ' . $game->title, $playerSession2->rich_presence);
    });

    test('creates delegated session by username', function () {
        $standalonesSystem = System::factory()->create(['id' => 102]);
        $game = $this->seedGame(system: $standalonesSystem);
        $gameHash = $game->hashes()->first();

        $integrationUser = User::factory()->create(['Permissions' => Permissions::Registered, 'connect_token' => Str::random(16)]);
        $delegatedUser = User::factory()->create(['Permissions' => Permissions::Registered, 'connect_token' => Str::random(16)]);

        // The integration user is the sole author of all the set's achievements.
        $coreAchievements = Achievement::factory()->promoted()->count(3)->create([
            'game_id' => $game->id,
            'user_id' => $integrationUser->id,
        ]);
        (new UpsertGameCoreAchievementSetFromLegacyFlagsAction())->execute($game);

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->post($this->apiUrl('startsession', [
                'g' => $game->id,
                'u' => $integrationUser->username,
                't' => $integrationUser->connect_token,
                'k' => $delegatedUser->username,
            ], credentials: false))
            ->assertExactJson([
                'Success' => true,
                'HardcoreUnlocks' => [],
                'Unlocks' => [],
                'ServerNow' => Carbon::now()->timestamp,
            ]);

        // player session created
        $playerSession = PlayerSession::where([
            'user_id' => $delegatedUser->id,
            'game_id' => $game->id,
        ])->first();
        $this->assertModelExists($playerSession);
        $this->assertEquals(1, $playerSession->duration);
        $this->assertEquals('Playing ' . $game->title, $playerSession->rich_presence);
        $this->assertEquals($this->userAgentValid, $playerSession->user_agent);
        $this->assertNull($playerSession->game_hash_id);

        $delegatedUser->refresh();
        $this->assertEquals($game->id, $delegatedUser->rich_presence_game_id);
        $this->assertEquals("Playing " . $game->title, $delegatedUser->rich_presence);

        // integration user session not created
        $this->assertFalse(PlayerSession::where('user_id', $integrationUser->id)->exists());
    });

    test('creates delegated session by ULID', function () {
        $standalonesSystem = System::factory()->create(['id' => 102]);
        $game = $this->seedGame(system: $standalonesSystem);
        $gameHash = $game->hashes()->first();

        $integrationUser = User::factory()->create(['Permissions' => Permissions::Registered, 'connect_token' => Str::random(16)]);
        $delegatedUser = User::factory()->create(['Permissions' => Permissions::Registered, 'connect_token' => Str::random(16)]);

        // The integration user is the sole author of all the set's achievements.
        $coreAchievements = Achievement::factory()->promoted()->count(3)->create([
            'game_id' => $game->id,
            'user_id' => $integrationUser->id,
        ]);
        (new UpsertGameCoreAchievementSetFromLegacyFlagsAction())->execute($game);

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->post($this->apiUrl('startsession', [
                'g' => $game->id,
                'u' => $integrationUser->username,
                't' => $integrationUser->connect_token,
                'k' => $delegatedUser->ulid,
            ], credentials: false))
            ->assertExactJson([
                'Success' => true,
                'HardcoreUnlocks' => [],
                'Unlocks' => [],
                'ServerNow' => Carbon::now()->timestamp,
            ]);

        // player session created
        $playerSession = PlayerSession::where([
            'user_id' => $delegatedUser->id,
            'game_id' => $game->id,
        ])->first();
        $this->assertModelExists($playerSession);
        $this->assertEquals(1, $playerSession->duration);
        $this->assertEquals('Playing ' . $game->title, $playerSession->rich_presence);
        $this->assertEquals($this->userAgentValid, $playerSession->user_agent);
        $this->assertNull($playerSession->game_hash_id);

        $delegatedUser->refresh();
        $this->assertEquals($game->id, $delegatedUser->rich_presence_game_id);
        $this->assertEquals("Playing " . $game->title, $delegatedUser->rich_presence);

        // integration user session not created
        $this->assertFalse(PlayerSession::where('user_id', $integrationUser->id)->exists());
    });
});

describe('unlocks', function () {
    test('returns no unlocks for game user has never played', function () {
        $game = $this->seedGame(achievements: 4);
        $gameHash = $game->hashes()->first();

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('startsession', ['g' => $game->id, 'm' => $gameHash->md5]))
            ->assertExactJson([
                'Success' => true,
                'HardcoreUnlocks' => [],
                'Unlocks' => [],
                'ServerNow' => Carbon::now()->timestamp,
            ]);
    });

    test('returns unlocks for game user has played before', function () {
        $game = $this->seedGame(achievements: 4);
        $gameHash = $game->hashes()->first();

        $achievement1 = $game->achievements->get(0);
        $unlock1Date = Carbon::now()->clone()->subMinutes(65);
        $this->addHardcoreUnlock($this->user, $achievement1, $unlock1Date);

        $achievement2 = $game->achievements->get(1);
        $unlock2Date = Carbon::now()->clone()->subMinutes(22);
        $this->addHardcoreUnlock($this->user, $achievement2, $unlock2Date);

        $achievement3 = $game->achievements->get(2);
        $unlock3Date = Carbon::now()->clone()->subMinutes(1);
        $this->addSoftcoreUnlock($this->user, $achievement3, $unlock3Date);

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('startsession', ['g' => $game->id, 'm' => $gameHash->md5]))
            ->assertExactJson([
                'Success' => true,
                'HardcoreUnlocks' => [
                    [
                        'ID' => $achievement1->id,
                        'When' => $unlock1Date->timestamp,
                    ],
                    [
                        'ID' => $achievement2->id,
                        'When' => $unlock2Date->timestamp,
                    ],
                ],
                'Unlocks' => [
                    [
                        'ID' => $achievement3->id,
                        'When' => $unlock3Date->timestamp,
                    ],
                ],
                'ServerNow' => Carbon::now()->timestamp,
            ]);
    });

    test('returns bonus subset unlocks for base game', function () {
        $game = $this->seedGame(achievements: 4);
        $gameHash = $game->hashes()->first();
        $subsetGame = $this->seedSubset($game, achievements: 2);

        $achievement1 = $game->achievements->get(0);
        $unlock1Date = Carbon::now()->clone()->subMinutes(65);
        $this->addHardcoreUnlock($this->user, $achievement1, $unlock1Date);

        $achievement2 = $game->achievements->get(1);
        $unlock2Date = Carbon::now()->clone()->subMinutes(22);
        $this->addHardcoreUnlock($this->user, $achievement2, $unlock2Date);

        $achievement3 = $game->achievements->get(2);
        $unlock3Date = Carbon::now()->clone()->subMinutes(1);
        $this->addSoftcoreUnlock($this->user, $achievement3, $unlock3Date);

        $bonusAchievement1 = $subsetGame->achievements->get(0);
        $bonusUnlock1Date = Carbon::now()->clone()->subMinutes(45);
        $this->addHardcoreUnlock($this->user, $bonusAchievement1, $bonusUnlock1Date);

        $bonusAchievement2 = $subsetGame->achievements->get(1);
        $bonusUnlock2Date = Carbon::now()->clone()->subMinutes(15);
        $this->addSoftcoreUnlock($this->user, $bonusAchievement2, $bonusUnlock2Date);

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('startsession', ['g' => $game->id, 'm' => $gameHash->md5]))
            ->assertExactJson([
                'Success' => true,
                'HardcoreUnlocks' => [
                    [
                        'ID' => $achievement1->id,
                        'When' => $unlock1Date->timestamp,
                    ],
                    [
                        'ID' => $achievement2->id,
                        'When' => $unlock2Date->timestamp,
                    ],
                    [
                        'ID' => $bonusAchievement1->id,
                        'When' => $bonusUnlock1Date->timestamp,
                    ],
                ],
                'Unlocks' => [
                    [
                        'ID' => $achievement3->id,
                        'When' => $unlock3Date->timestamp,
                    ],
                    [
                        'ID' => $bonusAchievement2->id,
                        'When' => $bonusUnlock2Date->timestamp,
                    ],
                ],
                'ServerNow' => Carbon::now()->timestamp,
            ]);
    });

    test('returns base game unlocks for bonus subset', function () {
        $game = $this->seedGame(achievements: 4);
        $subsetGame = $this->seedSubset($game, achievements: 2, withHash: true);
        $subsetGameHash = $subsetGame->hashes()->first();

        $achievement1 = $game->achievements->get(0);
        $unlock1Date = Carbon::now()->clone()->subMinutes(65);
        $this->addHardcoreUnlock($this->user, $achievement1, $unlock1Date);

        $achievement2 = $game->achievements->get(1);
        $unlock2Date = Carbon::now()->clone()->subMinutes(22);
        $this->addHardcoreUnlock($this->user, $achievement2, $unlock2Date);

        $achievement3 = $game->achievements->get(2);
        $unlock3Date = Carbon::now()->clone()->subMinutes(1);
        $this->addSoftcoreUnlock($this->user, $achievement3, $unlock3Date);

        $bonusAchievement1 = $subsetGame->achievements->get(0);
        $bonusUnlock1Date = Carbon::now()->clone()->subMinutes(45);
        $this->addHardcoreUnlock($this->user, $bonusAchievement1, $bonusUnlock1Date);

        $bonusAchievement2 = $subsetGame->achievements->get(1);
        $bonusUnlock2Date = Carbon::now()->clone()->subMinutes(15);
        $this->addSoftcoreUnlock($this->user, $bonusAchievement2, $bonusUnlock2Date);

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('startsession', ['g' => $subsetGame->id, 'm' => $subsetGameHash->md5]))
            ->assertExactJson([
                'Success' => true,
                'HardcoreUnlocks' => [
                    [
                        'ID' => $achievement1->id,
                        'When' => $unlock1Date->timestamp,
                    ],
                    [
                        'ID' => $achievement2->id,
                        'When' => $unlock2Date->timestamp,
                    ],
                    [
                        'ID' => $bonusAchievement1->id,
                        'When' => $bonusUnlock1Date->timestamp,
                    ],
                ],
                'Unlocks' => [
                    [
                        'ID' => $achievement3->id,
                        'When' => $unlock3Date->timestamp,
                    ],
                    [
                        'ID' => $bonusAchievement2->id,
                        'When' => $bonusUnlock2Date->timestamp,
                    ],
                ],
                'ServerNow' => Carbon::now()->timestamp,
            ]);
    });

    test('returns base game unlocks for specialty subset', function () {
        $game = $this->seedGame(achievements: 4);
        $subsetGame = $this->seedSubset($game, type: AchievementSetType::Specialty, achievements: 2, withHash: true);
        $subsetGameHash = $subsetGame->hashes()->first();

        $achievement1 = $game->achievements->get(0);
        $unlock1Date = Carbon::now()->clone()->subMinutes(65);
        $this->addHardcoreUnlock($this->user, $achievement1, $unlock1Date);

        $achievement2 = $game->achievements->get(1);
        $unlock2Date = Carbon::now()->clone()->subMinutes(22);
        $this->addHardcoreUnlock($this->user, $achievement2, $unlock2Date);

        $achievement3 = $game->achievements->get(2);
        $unlock3Date = Carbon::now()->clone()->subMinutes(1);
        $this->addSoftcoreUnlock($this->user, $achievement3, $unlock3Date);

        $bonusAchievement1 = $subsetGame->achievements->get(0);
        $bonusUnlock1Date = Carbon::now()->clone()->subMinutes(45);
        $this->addHardcoreUnlock($this->user, $bonusAchievement1, $bonusUnlock1Date);

        $bonusAchievement2 = $subsetGame->achievements->get(1);
        $bonusUnlock2Date = Carbon::now()->clone()->subMinutes(15);
        $this->addSoftcoreUnlock($this->user, $bonusAchievement2, $bonusUnlock2Date);

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('startsession', ['g' => $subsetGame->id, 'm' => $subsetGameHash->md5]))
            ->assertExactJson([
                'Success' => true,
                'HardcoreUnlocks' => [
                    [
                        'ID' => $achievement1->id,
                        'When' => $unlock1Date->timestamp,
                    ],
                    [
                        'ID' => $achievement2->id,
                        'When' => $unlock2Date->timestamp,
                    ],
                    [
                        'ID' => $bonusAchievement1->id,
                        'When' => $bonusUnlock1Date->timestamp,
                    ],
                ],
                'Unlocks' => [
                    [
                        'ID' => $achievement3->id,
                        'When' => $unlock3Date->timestamp,
                    ],
                    [
                        'ID' => $bonusAchievement2->id,
                        'When' => $bonusUnlock2Date->timestamp,
                    ],
                ],
                'ServerNow' => Carbon::now()->timestamp,
            ]);
    });

    test('returns only subset unlocks for exclusive subset', function () {
        $game = $this->seedGame(achievements: 4);
        $subsetGame = $this->seedSubset($game, type: AchievementSetType::Exclusive, achievements: 2, withHash: true);
        $subsetGameHash = $subsetGame->hashes()->first();

        $achievement1 = $game->achievements->get(0);
        $unlock1Date = Carbon::now()->clone()->subMinutes(65);
        $this->addHardcoreUnlock($this->user, $achievement1, $unlock1Date);

        $achievement2 = $game->achievements->get(1);
        $unlock2Date = Carbon::now()->clone()->subMinutes(22);
        $this->addHardcoreUnlock($this->user, $achievement2, $unlock2Date);

        $achievement3 = $game->achievements->get(2);
        $unlock3Date = Carbon::now()->clone()->subMinutes(1);
        $this->addSoftcoreUnlock($this->user, $achievement3, $unlock3Date);

        $bonusAchievement1 = $subsetGame->achievements->get(0);
        $bonusUnlock1Date = Carbon::now()->clone()->subMinutes(45);
        $this->addHardcoreUnlock($this->user, $bonusAchievement1, $bonusUnlock1Date);

        $bonusAchievement2 = $subsetGame->achievements->get(1);
        $bonusUnlock2Date = Carbon::now()->clone()->subMinutes(15);
        $this->addSoftcoreUnlock($this->user, $bonusAchievement2, $bonusUnlock2Date);

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('startsession', ['g' => $subsetGame->id, 'm' => $subsetGameHash->md5]))
            ->assertExactJson([
                'Success' => true,
                'HardcoreUnlocks' => [
                    [
                        'ID' => $bonusAchievement1->id,
                        'When' => $bonusUnlock1Date->timestamp,
                    ],
                ],
                'Unlocks' => [
                    [
                        'ID' => $bonusAchievement2->id,
                        'When' => $bonusUnlock2Date->timestamp,
                    ],
                ],
                'ServerNow' => Carbon::now()->timestamp,
            ]);
    });

    test('not unlocked event achievement hides hardcore unlock', function () {
        $game = $this->seedGame(achievements: 4);
        $gameHash = $game->hashes()->first();

        $achievement1 = $game->achievements->get(0);
        $unlock1Date = Carbon::now()->clone()->subDays(2); // hardcore unlock before active range for event achievement
        $this->addHardcoreUnlock($this->user, $achievement1, $unlock1Date);

        $eventAchievement = StartSessionTestHelpers::createEventAchievement($achievement1, Carbon::now()->clone()->subDays(1), Carbon::now()->clone()->addDays(1));

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('startsession', ['g' => $game->id, 'm' => $gameHash->md5]))
            ->assertExactJson([
                'Success' => true,
                'HardcoreUnlocks' => [], // hardcore unlock not returned
                'Unlocks' => [ // softcore unlock still returned
                    [
                        'ID' => $achievement1->id,
                        'When' => $unlock1Date->timestamp,
                    ],
                ],
                'ServerNow' => Carbon::now()->timestamp,
            ]);
    });

    test('unlocked event achievement does not hide hardcore unlock', function () {
        $game = $this->seedGame(achievements: 4);
        $gameHash = $game->hashes()->first();

        $achievement1 = $game->achievements->get(0);
        $unlock1Date = Carbon::now()->clone()->subDays(2); // hardcore unlock before active range for event achievement
        $this->addHardcoreUnlock($this->user, $achievement1, $unlock1Date);

        $eventAchievement = StartSessionTestHelpers::createEventAchievement($achievement1, Carbon::now()->clone()->subDays(1), Carbon::now()->clone()->addDays(1));

        $unlock2Date = Carbon::now()->clone()->subMinutes(65);
        $this->addHardcoreUnlock($this->user, $eventAchievement, $unlock2Date);

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('startsession', ['g' => $game->id, 'm' => $gameHash->md5]))
            ->assertExactJson([
                'Success' => true,
                'HardcoreUnlocks' => [ // original hardcore unlock date should be returned - not event unlock
                    [
                        'ID' => $achievement1->id,
                        'When' => $unlock1Date->timestamp,
                    ],
                ],
                'Unlocks' => [],
                'ServerNow' => Carbon::now()->timestamp,
            ]);
    });

    test('if multiple event achievements are active, all must be unlocked to not filter the unlock', function () {
        $game = $this->seedGame(achievements: 4);
        $gameHash = $game->hashes()->first();

        $achievement1 = $game->achievements->get(0);
        $unlock1Date = Carbon::now()->clone()->subDays(2); // hardcore unlock before active range for event achievement
        $this->addHardcoreUnlock($this->user, $achievement1, $unlock1Date);

        $eventAchievement1 = StartSessionTestHelpers::createEventAchievement($achievement1, Carbon::now()->clone()->subDays(1), Carbon::now()->clone()->addDays(1));
        $eventAchievement2 = StartSessionTestHelpers::createEventAchievement($achievement1, Carbon::now()->clone()->subDays(1), Carbon::now()->clone()->addDays(1));

        $unlock2Date = Carbon::now()->clone()->subMinutes(65);
        $this->addHardcoreUnlock($this->user, $eventAchievement1, $unlock2Date);

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('startsession', ['g' => $game->id, 'm' => $gameHash->md5]))
            ->assertExactJson([
                'Success' => true,
                'HardcoreUnlocks' => [], // hardcore unlock not returned
                'Unlocks' => [ // softcore unlock still returned
                    [
                        'ID' => $achievement1->id,
                        'When' => $unlock1Date->timestamp,
                    ],
                ],
                'ServerNow' => Carbon::now()->timestamp,
            ]);

        // unlock other event achievement. should return hardcore unlock again
        $unlock3Date = Carbon::now()->clone()->subMinutes(15);
        $this->addHardcoreUnlock($this->user, $eventAchievement2, $unlock2Date);

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('startsession', ['g' => $game->id, 'm' => $gameHash->md5]))
            ->assertExactJson([
                'Success' => true,
                'HardcoreUnlocks' => [
                    [
                        'ID' => $achievement1->id,
                        'When' => $unlock1Date->timestamp,
                    ],
                ],
                'Unlocks' => [],
                'ServerNow' => Carbon::now()->timestamp,
            ]);
    });

    test('inactive event achievement does not hide hardcore unlock', function () {
        $game = $this->seedGame(achievements: 4);
        $gameHash = $game->hashes()->first();

        $achievement1 = $game->achievements->get(0);
        $unlock1Date = Carbon::now()->clone()->subDays(10); // before event
        $this->addHardcoreUnlock($this->user, $achievement1, $unlock1Date);

        $eventAchievement = StartSessionTestHelpers::createEventAchievement($achievement1,
            Carbon::now()->clone()->subDays(3), Carbon::now()->clone()->subDays(1));

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('startsession', ['g' => $game->id, 'm' => $gameHash->md5]))
            ->assertExactJson([
                'Success' => true,
                'HardcoreUnlocks' => [
                    [
                        'ID' => $achievement1->id,
                        'When' => $unlock1Date->timestamp,
                    ],
                ],
                'Unlocks' => [],
                'ServerNow' => Carbon::now()->timestamp,
            ]);
    });

    test('demoted event achievement does not hide hardcore unlock', function () {
        $game = $this->seedGame(achievements: 4);
        $gameHash = $game->hashes()->first();

        $achievement1 = $game->achievements->get(0);
        $unlock1Date = Carbon::now()->clone()->subDays(10); // before event
        $this->addHardcoreUnlock($this->user, $achievement1, $unlock1Date);

        $eventAchievement = StartSessionTestHelpers::createEventAchievement($achievement1,
            Carbon::now()->clone()->subDays(1), Carbon::now()->clone()->addDays(1));
        $eventAchievement->is_promoted = false;
        $eventAchievement->save();

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('startsession', ['g' => $game->id, 'm' => $gameHash->md5]))
            ->assertExactJson([
                'Success' => true,
                'HardcoreUnlocks' => [
                    [
                        'ID' => $achievement1->id,
                        'When' => $unlock1Date->timestamp,
                    ],
                ],
                'Unlocks' => [],
                'ServerNow' => Carbon::now()->timestamp,
            ]);
    });

    test('outdated emulator indicates warning achievement is unlocked in softcore', function () {
        $game = $this->seedGame(achievements: 4);
        $gameHash = $game->hashes()->first();

        $this->withHeaders(['User-Agent' => $this->userAgentOutdated])
            ->get($this->apiUrl('startsession', ['g' => $game->id, 'm' => $gameHash->md5]))
            ->assertExactJson([
                'Success' => true,
                'HardcoreUnlocks' => [],
                'Unlocks' => [
                    [
                        'ID' => Achievement::CLIENT_WARNING_ID,
                        'When' => Carbon::now()->timestamp,
                    ],
                ],
                'ServerNow' => Carbon::now()->timestamp,
            ]);

        // player session still created
        $playerSession = PlayerSession::where([
            'user_id' => $this->user->id,
            'game_id' => $game->id,
        ])->first();
        $this->assertModelExists($playerSession);
        $this->assertEquals(1, $playerSession->duration);
        $this->assertEquals('Playing ' . $game->title, $playerSession->rich_presence);
        $this->assertEquals($this->userAgentOutdated, $playerSession->user_agent);
        $this->assertEquals($gameHash->id, $playerSession->game_hash_id);

        $this->user->refresh();
        $this->assertEquals($game->id, $this->user->rich_presence_game_id);
        $this->assertEquals("Playing " . $game->title, $this->user->rich_presence);
    });

    test('unsupported emulator indicates warning achievement is unlocked in softcore', function () {
        $game = $this->seedGame(achievements: 4);
        $gameHash = $game->hashes()->first();

        $this->withHeaders(['User-Agent' => $this->userAgentUnsupported])
            ->get($this->apiUrl('startsession', ['g' => $game->id, 'm' => $gameHash->md5]))
            ->assertExactJson([
                'Success' => true,
                'HardcoreUnlocks' => [],
                'Unlocks' => [
                    [
                        'ID' => Achievement::CLIENT_WARNING_ID,
                        'When' => Carbon::now()->timestamp,
                    ],
                ],
                'ServerNow' => Carbon::now()->timestamp,
            ]);

        // player session still created
        $playerSession = PlayerSession::where([
            'user_id' => $this->user->id,
            'game_id' => $game->id,
        ])->first();
        $this->assertModelExists($playerSession);
        $this->assertEquals(1, $playerSession->duration);
        $this->assertEquals('Playing ' . $game->title, $playerSession->rich_presence);
        $this->assertEquals($this->userAgentUnsupported, $playerSession->user_agent);
        $this->assertEquals($gameHash->id, $playerSession->game_hash_id);

        $this->user->refresh();
        $this->assertEquals($game->id, $this->user->rich_presence_game_id);
        $this->assertEquals("Playing " . $game->title, $this->user->rich_presence);
    });

    test('unknown emulator indicates warning achievement is unlocked in softcore', function () {
        $game = $this->seedGame(achievements: 4);
        $gameHash = $game->hashes()->first();

        $this->withHeaders(['User-Agent' => $this->userAgentUnknown])
            ->get($this->apiUrl('startsession', ['g' => $game->id, 'm' => $gameHash->md5]))
            ->assertExactJson([
                'Success' => true,
                'HardcoreUnlocks' => [],
                'Unlocks' => [
                    [
                        'ID' => Achievement::CLIENT_WARNING_ID,
                        'When' => Carbon::now()->timestamp,
                    ],
                ],
                'ServerNow' => Carbon::now()->timestamp,
            ]);

        // player session still created
        $playerSession = PlayerSession::where([
            'user_id' => $this->user->id,
            'game_id' => $game->id,
        ])->first();
        $this->assertModelExists($playerSession);
        $this->assertEquals(1, $playerSession->duration);
        $this->assertEquals('Playing ' . $game->title, $playerSession->rich_presence);
        $this->assertEquals($this->userAgentUnknown, $playerSession->user_agent);
        $this->assertEquals($gameHash->id, $playerSession->game_hash_id);

        $this->user->refresh();
        $this->assertEquals($game->id, $this->user->rich_presence_game_id);
        $this->assertEquals("Playing " . $game->title, $this->user->rich_presence);
    });

    test('returns delegated unlocks by username', function () {
        $standalonesSystem = System::factory()->create(['id' => 102]);
        $game = $this->seedGame(system: $standalonesSystem);
        $gameHash = $game->hashes()->first();

        $integrationUser = User::factory()->create(['Permissions' => Permissions::Registered, 'connect_token' => Str::random(16)]);
        $delegatedUser = User::factory()->create(['Permissions' => Permissions::Registered, 'connect_token' => Str::random(16)]);

        // The integration user is the sole author of all the set's achievements.
        $coreAchievements = Achievement::factory()->promoted()->count(3)->create([
            'game_id' => $game->id,
            'user_id' => $integrationUser->id,
        ]);
        (new UpsertGameCoreAchievementSetFromLegacyFlagsAction())->execute($game);

        $achievement1 = $game->achievements->get(0);
        $unlock1Date = Carbon::now()->clone()->subDays(10); // before event
        $this->addHardcoreUnlock($delegatedUser, $achievement1, $unlock1Date);

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->post($this->apiUrl('startsession', [
                'g' => $game->id,
                'u' => $integrationUser->username,
                't' => $integrationUser->connect_token,
                'k' => $delegatedUser->username,
            ], credentials: false))
            ->assertExactJson([
                'Success' => true,
                'HardcoreUnlocks' => [
                    [
                        'ID' => $achievement1->id,
                        'When' => $unlock1Date->timestamp,
                    ],
                ],
                'Unlocks' => [],
                'ServerNow' => Carbon::now()->timestamp,
            ]);
    });

    test('returns delegated unlocks by ULID', function () {
        $standalonesSystem = System::factory()->create(['id' => 102]);
        $game = $this->seedGame(system: $standalonesSystem);
        $gameHash = $game->hashes()->first();

        $integrationUser = User::factory()->create(['Permissions' => Permissions::Registered, 'connect_token' => Str::random(16)]);
        $delegatedUser = User::factory()->create(['Permissions' => Permissions::Registered, 'connect_token' => Str::random(16)]);

        // The integration user is the sole author of all the set's achievements.
        $coreAchievements = Achievement::factory()->promoted()->count(3)->create([
            'game_id' => $game->id,
            'user_id' => $integrationUser->id,
        ]);
        (new UpsertGameCoreAchievementSetFromLegacyFlagsAction())->execute($game);

        $achievement1 = $game->achievements->get(0);
        $unlock1Date = Carbon::now()->clone()->subDays(10); // before event
        $this->addHardcoreUnlock($delegatedUser, $achievement1, $unlock1Date);

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->post($this->apiUrl('startsession', [
                'g' => $game->id,
                'u' => $integrationUser->username,
                't' => $integrationUser->connect_token,
                'k' => $delegatedUser->ulid,
            ], credentials: false))
            ->assertExactJson([
                'Success' => true,
                'HardcoreUnlocks' => [
                    [
                        'ID' => $achievement1->id,
                        'When' => $unlock1Date->timestamp,
                    ],
                ],
                'Unlocks' => [],
                'ServerNow' => Carbon::now()->timestamp,
            ]);
    });

    test('returns no unlocks for incompatible hash when not tester', function () {
        $game = $this->seedGame(achievements: 4);
        $gameHash = $game->hashes()->first();
        $gameHash->compatibility = GameHashCompatibility::Incompatible;
        $gameHash->save();

        $achievement1 = $game->achievements->get(0);
        $unlock1Date = Carbon::now()->clone()->subMinutes(65);
        $this->addHardcoreUnlock($this->user, $achievement1, $unlock1Date);

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('startsession', ['g' => $game->id, 'm' => $gameHash->md5]))
            ->assertExactJson([
                'Success' => true,
                'HardcoreUnlocks' => [],
                'Unlocks' => [],
                'ServerNow' => Carbon::now()->timestamp,
            ]);
    });

    test('returns unlocks for incompatible hash when tester', function () {
        $game = $this->seedGame(achievements: 4);
        $gameHash = $game->hashes()->first();
        $gameHash->compatibility = GameHashCompatibility::Incompatible;
        $gameHash->compatibility_tester_id = $this->user->id;
        $gameHash->save();

        $achievement1 = $game->achievements->get(0);
        $unlock1Date = Carbon::now()->clone()->subMinutes(65);
        $this->addHardcoreUnlock($this->user, $achievement1, $unlock1Date);

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('startsession', ['g' => $game->id, 'm' => $gameHash->md5]))
            ->assertExactJson([
                'Success' => true,
                'HardcoreUnlocks' => [
                    [
                        'ID' => $achievement1->id,
                        'When' => $unlock1Date->timestamp,
                    ],
                ],
                'Unlocks' => [],
                'ServerNow' => Carbon::now()->timestamp,
            ]);
    });

    test('returns no unlocks for incompatible game when not tester', function () {
        $game = $this->seedGame(achievements: 4);
        $gameHash = $game->hashes()->first();
        $gameHash->compatibility = GameHashCompatibility::Incompatible;
        $gameHash->save();

        $achievement1 = $game->achievements->get(0);
        $unlock1Date = Carbon::now()->clone()->subMinutes(65);
        $this->addHardcoreUnlock($this->user, $achievement1, $unlock1Date);

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('startsession', [
                'g' => VirtualGameIdService::encodeVirtualGameId($game->id, $gameHash->compatibility),
                'm' => $gameHash->md5,
            ]))
            ->assertExactJson([
                'Success' => true,
                'HardcoreUnlocks' => [],
                'Unlocks' => [],
                'ServerNow' => Carbon::now()->timestamp,
            ]);
    });

    test('returns unlocks for incompatible game when tester', function () {
        $game = $this->seedGame(achievements: 4);
        $gameHash = $game->hashes()->first();
        $gameHash->compatibility = GameHashCompatibility::Incompatible;
        $gameHash->compatibility_tester_id = $this->user->id;
        $gameHash->save();

        $achievement1 = $game->achievements->get(0);
        $unlock1Date = Carbon::now()->clone()->subMinutes(65);
        $this->addHardcoreUnlock($this->user, $achievement1, $unlock1Date);

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('startsession', [
                'g' => VirtualGameIdService::encodeVirtualGameId($game->id, $gameHash->compatibility),
                'm' => $gameHash->md5,
            ]))
            ->assertExactJson([
                'Success' => true,
                'HardcoreUnlocks' => [
                    [
                        'ID' => $achievement1->id,
                        'When' => $unlock1Date->timestamp,
                    ],
                ],
                'Unlocks' => [],
                'ServerNow' => Carbon::now()->timestamp,
            ]);
    });
});

describe('validation', function () {
    test('non-existent game', function () {
        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('startsession', ['g' => 999999]))
            ->assertStatus(404)
            ->assertExactJson([
                'Success' => false,
                'Error' => 'Unknown game.',
                'Code' => 'not_found',
                'Status' => 404,
            ]);

        // no player session
        $playerSession = PlayerSession::where([
            'user_id' => $this->user->id,
            'game_id' => 999999,
        ])->first();
        $this->assertNull($playerSession);
    });

    test('cannot delegate to non-standalone game', function () {
        $game = $this->seedGame(achievements: 4);
        $gameHash = $game->hashes()->first();

        $integrationUser = User::factory()->create(['Permissions' => Permissions::Registered, 'connect_token' => Str::random(16)]);
        $delegatedUser = User::factory()->create(['Permissions' => Permissions::Registered, 'connect_token' => Str::random(16)]);

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->post($this->apiUrl('startsession', [
                'g' => $game->id,
                'u' => $integrationUser->username,
                't' => $integrationUser->connect_token,
                'k' => $delegatedUser->username,
            ], credentials: false))
            ->assertstatus(403)
            ->assertExactJson([
                'Success' => false,
                'Error' => 'Access denied.',
                'Code' => 'access_denied',
                'Status' => 403,
            ]);

        // player session not created
        $this->assertFalse(PlayerSession::where('user_id', $delegatedUser->id)->exists());

        // integration user session not created
        $this->assertFalse(PlayerSession::where('user_id', $integrationUser->id)->exists());
    });

    test('cannot delegate to standalone game if not author', function () {
        $standalonesSystem = System::factory()->create(['id' => 102]);
        $game = $this->seedGame(system: $standalonesSystem, achievements: 4);
        $gameHash = $game->hashes()->first();

        // achievements will be authored by $this->user
        $integrationUser = User::factory()->create(['Permissions' => Permissions::Registered, 'connect_token' => Str::random(16)]);
        $delegatedUser = User::factory()->create(['Permissions' => Permissions::Registered, 'connect_token' => Str::random(16)]);

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->post($this->apiUrl('startsession', [
                'g' => $game->id,
                'u' => $integrationUser->username,
                't' => $integrationUser->connect_token,
                'k' => $delegatedUser->username,
            ], credentials: false))
            ->assertstatus(403)
            ->assertExactJson([
                'Success' => false,
                'Error' => 'Access denied.',
                'Code' => 'access_denied',
                'Status' => 403,
            ]);

        // player session not created
        $this->assertFalse(PlayerSession::where('user_id', $delegatedUser->id)->exists());

        // integration user session not created
        $this->assertFalse(PlayerSession::where('user_id', $integrationUser->id)->exists());
    });
});
