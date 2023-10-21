<?php

declare(strict_types=1);

namespace Tests\Feature\Connect;

use App\Platform\Models\Game;
use App\Platform\Models\PlayerSession;
use App\Platform\Models\System;
use App\Site\Enums\Permissions;
use App\Site\Models\User;
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
}
