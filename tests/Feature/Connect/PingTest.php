<?php

declare(strict_types=1);

namespace Tests\Feature\Connect;

use App\Enums\Permissions;
use App\Models\Achievement;
use App\Models\Game;
use App\Models\GameHash;
use App\Models\PlayerGame;
use App\Models\PlayerSession;
use App\Models\System;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\Feature\Platform\Concerns\TestsPlayerAchievements;
use Tests\TestCase;

class PingTest extends TestCase
{
    use BootstrapsConnect;
    use RefreshDatabase;
    use TestsPlayerAchievements;

    public function testPing(): void
    {
        Carbon::setTestNow(Carbon::now());

        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create(['ConsoleID' => $system->ID]);
        /** @var GameHash $gameHash */
        $gameHash = GameHash::factory()->create(['game_id' => $game->id]);

        $this->user->LastGameID = $game->ID;
        $this->user->save();

        // this API requires POST
        $this->post('dorequest.php', $this->apiParams('ping', ['g' => $game->ID, 'm' => 'Doing good', 'x' => $gameHash->md5]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
            ]);

        // player session resumed
        $playerSession = PlayerSession::where([
            'user_id' => $this->user->id,
            'game_id' => $game->id,
        ])->first();
        $this->assertModelExists($playerSession);
        $this->assertEquals(1, $playerSession->duration);
        $this->assertEquals('Doing good', $playerSession->rich_presence);
        $this->assertEquals($gameHash->id, $playerSession->game_hash_id);

        // player game created
        $playerGame = PlayerGame::where('game_id', $game->id)->where('user_id', $this->user->id)->first();
        $this->assertNotNull($playerGame);
        $this->assertEquals(0, $playerGame->playtime_total);
        $this->assertEquals(0, $playerGame->time_taken);
        $this->assertEquals(0, $playerGame->time_taken_hardcore);

        /** @var User $user1 */
        $user1 = User::whereName($this->user->User)->first();
        $this->assertEquals($game->ID, $user1->LastGameID);
        $this->assertEquals('Doing good', $user1->RichPresenceMsg);

        // string sent by GET will not update user's rich presence message
        $this->get($this->apiUrl('ping', ['g' => $game->ID, 'm' => 'Doing better', 'x' => $gameHash->md5]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
            ]);

        // player session resumed
        $playerSession2 = PlayerSession::where([
            'user_id' => $this->user->id,
            'game_id' => $game->id,
        ])->first();
        $this->assertEquals($playerSession->id, $playerSession2->id);
        $this->assertEquals(1, $playerSession2->duration);
        $this->assertEquals('Doing good', $playerSession2->rich_presence);

        $user1 = User::whereName($this->user->User)->first();
        $this->assertEquals($game->ID, $user1->LastGameID);
        $this->assertEquals('Doing good', $user1->RichPresenceMsg);

        // invalid UTF-8 should be sanitized
        $this->post('dorequest.php', $this->apiParams('ping', ['g' => $game->ID, 'm' => "T\xC3\xA9st t\xC3st", 'x' => $gameHash->md5]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
            ]);

        // player session resumed
        $playerSession2 = PlayerSession::where([
            'user_id' => $this->user->id,
            'game_id' => $game->id,
        ])->first();
        $this->assertEquals($playerSession->id, $playerSession2->id);
        $this->assertEquals(1, $playerSession2->duration);
        $this->assertEquals('Tést t?st', $playerSession2->rich_presence);
        $this->assertEquals($gameHash->id, $playerSession2->game_hash_id);

        $user1 = User::whereName($this->user->User)->first();
        $this->assertEquals($game->ID, $user1->LastGameID);
        $this->assertEquals('Tést t?st', $user1->RichPresenceMsg);
    }

    public function testPingInvalidGame(): void
    {
        $this->post('dorequest.php', $this->apiParams('ping', ['g' => 123456789]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => false,
            ]);

        $this->post('dorequest.php', $this->apiParams('ping', ['g' => 'abcdef']))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => false,
            ]);
    }

    public function testPingInvalidUser(): void
    {
        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create(['ConsoleID' => $system->ID]);

        $params = [
            'u' => 'UnknownUser',
            't' => 'ABCDEFGHIJK',
            'r' => 'ping',
            'g' => $game->ID,
            'm' => 'Doing good',
        ];

        // try with unknown user
        $this->post('dorequest.php', $params)
            ->assertStatus(401)
            ->assertHeader('WWW-Authenticate', 'Bearer')
            ->assertExactJson([
                'Success' => false,
                'Status' => 401,
                'Code' => 'invalid_credentials',
                'Error' => 'Invalid user/token combination.',
            ]);

        // try with incorrect token
        $params['u'] = $this->user->User;

        $this->post('dorequest.php', $params)
            ->assertStatus(401)
            ->assertHeader('WWW-Authenticate', 'Bearer')
            ->assertExactJson([
                'Success' => false,
                'Status' => 401,
                'Code' => 'invalid_credentials',
                'Error' => 'Invalid user/token combination.',
            ]);

        // try with banned user
        /** @var User $user */
        $user = User::factory()->create(['Permissions' => Permissions::Banned, 'appToken' => Str::random(16)]);

        $params['u'] = $user->User;
        $params['t'] = $user->appToken;

        $this->post('dorequest.php', $params)
            ->assertStatus(403)
            ->assertExactJson([
                'Success' => false,
                'Status' => 403,
                'Code' => 'access_denied',
                'Error' => 'Access denied.',
            ]);
    }

    public function testPingUnregisteredUser(): void
    {
        // NOTE: Attempting to lump this in the previous test case doesn't work. The
        // response indicates the function was still validating the banned user, despite
        // passing the credentials for the unregistered user. This seems to be a known
        // issue (https://stackoverflow.com/questions/37418155/post-bodies-ignored-when-making-multiple-post-calls-in-laravel-test),
        // but the provided solution didn't seem to work.
        // While a separate test case is desirable, the overhead or refreshing the database
        // is not desirable, which is why most of the test cases are lumped in singular functions.

        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create(['ConsoleID' => $system->ID]);

        /** @var User $user */
        $user = User::factory()->create(['Permissions' => Permissions::Unregistered, 'appToken' => Str::random(16)]);

        $params = [
            'u' => $user->User,
            't' => $user->appToken,
            'r' => 'ping',
            'g' => $game->ID,
            'm' => 'Doing good',
        ];

        $this->post('dorequest.php', $params)
            ->assertStatus(403)
            ->assertExactJson([
                'Success' => false,
                'Status' => 403,
                'Code' => 'access_denied',
                'Error' => 'Access denied. Please verify your email address.',
            ]);
    }

    public function testPingUserTokenMismatch(): void
    {
        // NOTE: Attempting to lump this in the previous test case doesn't work. The
        // response indicates the function was still validating the banned user, despite
        // passing the credentials for the unregistered user. This seems to be a known
        // issue (https://stackoverflow.com/questions/37418155/post-bodies-ignored-when-making-multiple-post-calls-in-laravel-test),
        // but the provided solution didn't seem to work.
        // While a separate test case is desirable, the overhead or refreshing the database
        // is not desirable, which is why most of the test cases are lumped in singular functions.

        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create(['ConsoleID' => $system->ID]);

        /** @var User $user */
        $user = User::factory()->create(['Permissions' => Permissions::Registered, 'appToken' => Str::random(16)]);

        $params = [
            'u' => $user->User,
            't' => $this->user->appToken,
            'r' => 'ping',
            'g' => $game->ID,
            'm' => 'Doing good',
        ];

        $this->post('dorequest.php', $params)
            ->assertStatus(401)
            ->assertHeader('WWW-Authenticate', 'Bearer')
            ->assertExactJson([
                'Success' => false,
                'Status' => 401,
                'Code' => 'invalid_credentials',
                'Error' => 'Invalid user/token combination.',
            ]);
    }

    public function testPingDelegatedByName(): void
    {
        /** @var System $standalonesSystem */
        $standalonesSystem = System::factory()->create(['ID' => 102]);
        /** @var Game $gameOne */
        $gameOne = Game::factory()->create(['ConsoleID' => $standalonesSystem->ID]);

        /** @var User $integrationUser */
        $integrationUser = User::factory()->create(['Permissions' => Permissions::Registered, 'appToken' => Str::random(16)]);
        /** @var User $delegatedUser */
        $delegatedUser = User::factory()->create(['Permissions' => Permissions::Registered, 'appToken' => Str::random(16)]);

        $delegatedUser->LastGameID = $gameOne->id;
        $delegatedUser->save();

        // The integration user is the sole author of all the set's achievements.
        Achievement::factory()->published()->count(6)->create([
            'GameID' => $gameOne->id,
            'user_id' => $integrationUser->id,
        ]);

        $params = [
            'u' => $integrationUser->User,
            't' => $integrationUser->appToken,
            'r' => 'ping',
            'g' => $gameOne->id,
            'm' => 'Doing good',
            'k' => $delegatedUser->User, // !!
        ];

        $this->post('dorequest.php', $params)
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
            ]);

        $delegatedPlayerSession = PlayerSession::where([
            'user_id' => $delegatedUser->id,
            'game_id' => $gameOne->id,
        ])->first();
        $this->assertModelExists($delegatedPlayerSession);
        $this->assertEquals(1, $delegatedPlayerSession->duration);
        $this->assertEquals('Doing good', $delegatedPlayerSession->rich_presence);

        // While delegating, updates are made on behalf of username `k`.
        $this->assertDatabaseMissing((new PlayerSession())->getTable(), [
            'user_id' => $integrationUser->id,
            'game_id' => $gameOne->id,
        ]);

        // Next, try to delegate on a non-standalone game.
        // This is not allowed and should fail.
        /** @var System $normalSystem */
        $normalSystem = System::factory()->create(['ID' => 1]);
        /** @var Game $gameTwo */
        $gameTwo = Game::factory()->create(['ConsoleID' => $normalSystem->ID]);

        $params['g'] = $gameTwo->id;

        $this->post('dorequest.php', $params)
            ->assertStatus(403)
            ->assertExactJson([
                "Success" => false,
                "Error" => "You do not have permission to do that.",
                "Code" => "access_denied",
                "Status" => 403,
            ]);

        // Next, try to delegate on a game with no achievements authored by the integration user.
        // This is not allowed and should fail.
        /** @var Game $gameThree */
        $gameThree = Game::factory()->create(['ConsoleID' => $standalonesSystem->ID]);
        /** @var User $randomUser */
        $randomUser = User::factory()->create(['Permissions' => Permissions::Registered, 'appToken' => Str::random(16)]);
        Achievement::factory()->published()->count(6)->create(['GameID' => $gameThree->id, 'user_id' => $randomUser->id]);
        $params['g'] = $gameThree->id;

        $this->post('dorequest.php', $params)
            ->assertStatus(403)
            ->assertExactJson([
                "Success" => false,
                "Error" => "You do not have permission to do that.",
                "Code" => "access_denied",
                "Status" => 403,
            ]);
    }

    public function testPingDelegatedByUlid(): void
    {
        /** @var System $standalonesSystem */
        $standalonesSystem = System::factory()->create(['ID' => 102]);
        /** @var Game $gameOne */
        $gameOne = Game::factory()->create(['ConsoleID' => $standalonesSystem->ID]);

        /** @var User $integrationUser */
        $integrationUser = User::factory()->create(['Permissions' => Permissions::Registered, 'appToken' => Str::random(16)]);
        /** @var User $delegatedUser */
        $delegatedUser = User::factory()->create(['Permissions' => Permissions::Registered, 'appToken' => Str::random(16)]);

        $delegatedUser->LastGameID = $gameOne->id;
        $delegatedUser->save();

        // The integration user is the sole author of all the set's achievements.
        Achievement::factory()->published()->count(6)->create([
            'GameID' => $gameOne->id,
            'user_id' => $integrationUser->id,
        ]);

        $params = [
            'u' => $integrationUser->User,
            't' => $integrationUser->appToken,
            'r' => 'ping',
            'g' => $gameOne->id,
            'm' => 'Doing good',
            'k' => $delegatedUser->ulid, // !!
        ];

        $this->post('dorequest.php', $params)
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
            ]);

        $delegatedPlayerSession = PlayerSession::where([
            'user_id' => $delegatedUser->id,
            'game_id' => $gameOne->id,
        ])->first();
        $this->assertModelExists($delegatedPlayerSession);
        $this->assertEquals(1, $delegatedPlayerSession->duration);
        $this->assertEquals('Doing good', $delegatedPlayerSession->rich_presence);

        // While delegating, updates are made on behalf of username `k`.
        $this->assertDatabaseMissing((new PlayerSession())->getTable(), [
            'user_id' => $integrationUser->id,
            'game_id' => $gameOne->id,
        ]);

        // Next, try to delegate on a non-standalone game.
        // This is not allowed and should fail.
        /** @var System $normalSystem */
        $normalSystem = System::factory()->create(['ID' => 1]);
        /** @var Game $gameTwo */
        $gameTwo = Game::factory()->create(['ConsoleID' => $normalSystem->ID]);

        $params['g'] = $gameTwo->id;

        $this->post('dorequest.php', $params)
            ->assertStatus(403)
            ->assertExactJson([
                "Success" => false,
                "Error" => "You do not have permission to do that.",
                "Code" => "access_denied",
                "Status" => 403,
            ]);

        // Next, try to delegate on a game with no achievements authored by the integration user.
        // This is not allowed and should fail.
        /** @var Game $gameThree */
        $gameThree = Game::factory()->create(['ConsoleID' => $standalonesSystem->ID]);
        /** @var User $randomUser */
        $randomUser = User::factory()->create(['Permissions' => Permissions::Registered, 'appToken' => Str::random(16)]);
        Achievement::factory()->published()->count(6)->create(['GameID' => $gameThree->id, 'user_id' => $randomUser->id]);
        $params['g'] = $gameThree->id;

        $this->post('dorequest.php', $params)
            ->assertStatus(403)
            ->assertExactJson([
                "Success" => false,
                "Error" => "You do not have permission to do that.",
                "Code" => "access_denied",
                "Status" => 403,
            ]);
    }

    public function testPingExtendsSession(): void
    {
        $now = Carbon::now()->startOfSecond()->subMinutes(30);
        Carbon::setTestNow($now);

        $game = $this->seedGame(achievements: 2);
        $gameHash = $game->hashes->first();

        // session is assumed to be hardcore if player has more hardcore points than
        // softcore points. when the ping dispatches an UpdatePlayerGameMetrics job
        // because playtime_total is null, the score will get recalculated, so we
        // need to actually unlock an achievement.
        $achievement = $this->seedAchievement();
        $unlock = $this->user->playerAchievements()->create(['achievement_id' => $achievement->id, 'unlocked_at' => $now, 'unlocked_hardcore_at' => $now]);
        $unlock->save();
        $playerGame = new PlayerGame([
            'game_id' => $achievement->game->id,
            'user_id' => $this->user->id,
            'points_hardcore' => $achievement->points,
            'achievements_unlocked' => 1,
        ]);
        $playerGame->save();

        // this API requires POST
        $this->post('dorequest.php', $this->apiParams('ping', ['g' => $game->ID, 'm' => 'Doing good', 'x' => $gameHash->md5]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
            ]);

        // player session created
        $playerSession = PlayerSession::where('game_id', $game->id)->where('user_id', $this->user->id)->first();
        $this->assertModelExists($playerSession);
        $this->assertEquals(1, $playerSession->duration);
        $this->assertEquals('Doing good', $playerSession->rich_presence);
        $this->assertEquals($gameHash->id, $playerSession->game_hash_id);

        // player game created
        $playerGame = PlayerGame::where('game_id', $game->id)->where('user_id', $this->user->id)->first();
        $this->assertModelExists($playerGame);
        $this->assertEquals(0, $playerGame->playtime_total);
        $this->assertEquals(0, $playerGame->time_taken);
        $this->assertEquals(0, $playerGame->time_taken_hardcore);

        // second ping 30 second later
        $now = $now->addSeconds(30);
        Carbon::setTestNow($now);

        $this->post('dorequest.php', $this->apiParams('ping', ['g' => $game->ID, 'm' => 'Title screen', 'x' => $gameHash->md5]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
            ]);

        $playerSession->refresh();
        $this->assertEquals(1, $playerSession->duration);
        $this->assertEquals('Title screen', $playerSession->rich_presence);

        // session duration in minutes, so times will be in 60 second increments
        $playerGame->refresh();
        $this->assertEquals(60, $playerGame->playtime_total);
        $this->assertEquals(60, $playerGame->time_taken);
        $this->assertEquals(60, $playerGame->time_taken_hardcore);

        // third ping two minutes later
        $now = $now->addMinutes(2);
        Carbon::setTestNow($now);

        $this->post('dorequest.php', $this->apiParams('ping', ['g' => $game->ID, 'm' => 'Level 1', 'x' => $gameHash->md5]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
            ]);

        // total duration = 2.5 minutes, truncated down to 2 minutes
        $playerSession->refresh();
        $this->assertEquals(2, $playerSession->duration);
        $this->assertEquals('Level 1', $playerSession->rich_presence);

        $playerGame->refresh();
        $this->assertEquals(120, $playerGame->playtime_total);
        $this->assertEquals(120, $playerGame->time_taken);
        $this->assertEquals(120, $playerGame->time_taken_hardcore);

        // unlock all achievements in softcore
        $playerGame->beaten_at = $playerGame->completed_at = $now->clone()->addSeconds(37);
        $playerGame->time_taken += 37;
        $playerGame->time_to_complete = $playerGame->time_taken;
        $playerGame->save();

        // ping two minutes later
        $now = $now->addMinutes(2);
        Carbon::setTestNow($now);

        $this->post('dorequest.php', $this->apiParams('ping', ['g' => $game->ID, 'm' => 'Level 1', 'x' => $gameHash->md5]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
            ]);

        // total duration = 4.5 minutes, truncated down to 4 minutes
        $playerSession->refresh();
        $this->assertEquals(4, $playerSession->duration);
        $this->assertEquals('Level 1', $playerSession->rich_presence);

        $playerGame->refresh();
        $this->assertEquals(240, $playerGame->playtime_total);
        $this->assertEquals(157, $playerGame->time_taken); // time_taken stops after game marked as completed
        $this->assertEquals(240, $playerGame->time_taken_hardcore);

        // unlock all achievements in hardcore
        $playerGame->beaten_hardcore_at = $playerGame->completed_hardcore_at = $now->clone()->addSeconds(74);
        $playerGame->time_taken_hardcore += 74;
        $playerGame->time_to_complete_hardcore = $playerGame->time_taken_hardcore;
        $playerGame->save();

        // ping two minutes later
        $now = $now->addMinutes(2);
        Carbon::setTestNow($now);

        $this->post('dorequest.php', $this->apiParams('ping', ['g' => $game->ID, 'm' => 'Level 1', 'x' => $gameHash->md5]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
            ]);

        // total duration = 4.5 minutes, truncated down to 4 minutes
        $playerSession->refresh();
        $this->assertEquals(6, $playerSession->duration);
        $this->assertEquals('Level 1', $playerSession->rich_presence);

        $playerGame->refresh();
        $this->assertEquals(360, $playerGame->playtime_total);
        $this->assertEquals(157, $playerGame->time_taken);
        $this->assertEquals(314, $playerGame->time_taken_hardcore); // time_taken_hardcore stops after game marked as completed hardcore
    }
}
