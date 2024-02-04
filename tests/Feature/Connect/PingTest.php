<?php

declare(strict_types=1);

namespace Tests\Feature\Connect;

use App\Enums\Permissions;
use App\Models\User;
use App\Platform\Models\Achievement;
use App\Platform\Models\Game;
use App\Platform\Models\PlayerSession;
use App\Platform\Models\System;
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

        $this->user->LastGameID = $game->ID;
        $this->user->save();

        // this API requires POST
        $this->post('dorequest.php', $this->apiParams('ping', ['g' => $game->ID, 'm' => 'Doing good']))
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

        /** @var User $user1 */
        $user1 = User::firstWhere('User', $this->user->User);
        $this->assertEquals($game->ID, $user1->LastGameID);
        $this->assertEquals('Doing good', $user1->RichPresenceMsg);

        // string sent by GET will not update user's rich presence message
        $this->get($this->apiUrl('ping', ['g' => $game->ID, 'm' => 'Doing better']))
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

        $user1 = User::firstWhere('User', $this->user->User);
        $this->assertEquals($game->ID, $user1->LastGameID);
        $this->assertEquals('Doing good', $user1->RichPresenceMsg);

        // invalid UTF-8 should be sanitized
        $this->post('dorequest.php', $this->apiParams('ping', ['g' => $game->ID, 'm' => "T\xC3\xA9st t\xC3st"]))
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

        $user1 = User::firstWhere('User', $this->user->User);
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

    public function testPingDelegated(): void
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
        Achievement::factory()->published()->count(6)->create(['GameID' => $gameOne->id, 'Author' => $integrationUser->User]);

        $params = [
            'u' => $integrationUser->User,
            't' => $integrationUser->appToken,
            'r' => 'ping',
            'g' => $gameOne->id,
            'm' => 'Doing good',
            'k' => $delegatedUser->User,
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
        Achievement::factory()->published()->count(6)->create(['GameID' => $gameThree->id]);
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
}
