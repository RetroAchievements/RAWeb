<?php

declare(strict_types=1);

namespace Tests\Feature\Connect;

use App\Enums\Permissions;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use BootstrapsConnect;
    use RefreshDatabase;

    public function testLogin(): void
    {
        Carbon::setTestNow(Carbon::now());
        $password = 'Pa$$w0rd';

        // === with password ===

        /** @var User $user */
        $user = User::factory()->create([
            'display_name' => 'MyDisplayName',
            'connect_token' => Str::random(16),
            'password' => Hash::make($password),
            'Permissions' => Permissions::JuniorDeveloper,
            'points_hardcore' => 12345,
            'points' => 4321,
        ]);

        $this->get($this->apiUrl('login', ['u' => $user->username, 'p' => $password], credentials: false))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'User' => $user->display_name,
                'AvatarUrl' => $user->avatar_url,
                'Token' => $user->connect_token,
                'Score' => 12345,
                'SoftcoreScore' => 4321,
                'Messages' => 0,
                'Permissions' => Permissions::JuniorDeveloper,
                'AccountType' => 'Junior Developer',
            ]);

        /** @var User $user1 */
        $user1 = User::whereName($user->username)->first();
        $this->assertEquals(Carbon::now()->clone()->addDays(14)->startOfSecond(), $user1->connect_token_expires_at);

        // === with token ===

        $this->get($this->apiUrl('login', ['u' => $user->username, 't' => $user->connect_token], credentials: false))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'User' => $user->display_name,
                'AvatarUrl' => $user->avatar_url,
                'Token' => $user->connect_token,
                'Score' => 12345,
                'SoftcoreScore' => 4321,
                'Messages' => 0,
                'Permissions' => Permissions::JuniorDeveloper,
                'AccountType' => 'Junior Developer',
            ]);

        // === with legacy password (and no previous connect_token) ===

        /** @var User $user2 */
        $user2 = User::factory()->create([
            'connect_token' => '',
            'legacy_salted_password' => md5($password . config('app.legacy_password_salt')),
            'Permissions' => Permissions::Developer,
            'points_hardcore' => 99999,
            'points' => 99,
        ]);

        $response = $this->get($this->apiUrl('login', ['u' => $user2->username, 'p' => $password], credentials: false));

        /* direct query to access non-visible attributes */
        $data = legacyDbFetch('SELECT connect_token, legacy_salted_password, password FROM users WHERE username=:user', ['user' => $user2->username]);
        $this->assertNotEquals('', $data['connect_token']);
        $this->assertEquals('', $data['legacy_salted_password']);
        $this->assertTrue(Hash::check($password, $data['password']));

        $response->assertStatus(200)->assertExactJson([
            'Success' => true,
            'User' => $user2->display_name,
            'AvatarUrl' => $user2->avatar_url,
            'Token' => $data['connect_token'],
            'Score' => 99999,
            'SoftcoreScore' => 99,
            'Messages' => 0,
            'Permissions' => Permissions::Developer,
            'AccountType' => 'Developer',
        ]);

    }

    public function testInvalidCredentials(): void
    {
        Carbon::setTestNow(Carbon::now());
        $password = 'Pa$$w0rd';

        /** @var User $user */
        $user = User::factory()->create([
            'connect_token' => Str::random(16),
            'password' => Hash::make($password),
        ]);

        // invalid password
        $this->post('dorequest.php', ['r' => 'login2', 'u' => $user->username, 'p' => $password . '1'])
            ->assertStatus(401)
            ->assertHeader('WWW-Authenticate', 'Bearer')
            ->assertExactJson([
                'Success' => false,
                'Status' => 401,
                'Code' => 'invalid_credentials',
                'Error' => 'Invalid user/password combination. Please try again.',
            ]);

        // invalid token
        $this->post('dorequest.php', ['r' => 'login2', 'u' => $user->username, 't' => $user->connect_token . '1'])
            ->assertStatus(401)
            ->assertHeader('WWW-Authenticate', 'Bearer')
            ->assertExactJson([
                'Success' => false,
                'Status' => 401,
                'Code' => 'invalid_credentials',
                'Error' => 'Invalid user/token combination.',
            ]);

        // unknown user
        $this->post('dorequest.php', ['r' => 'login2', 'u' => $user->username . '1', 'p' => $password])
            ->assertStatus(401)
            ->assertHeader('WWW-Authenticate', 'Bearer')
            ->assertExactJson([
                'Success' => false,
                'Status' => 401,
                'Code' => 'invalid_credentials',
                'Error' => 'Invalid user/password combination. Please try again.',
            ]);

        // no user
        $this->post('dorequest.php', ['r' => 'login2', 'p' => $password])
            ->assertStatus(422)
            ->assertExactJson([
                'Success' => false,
                'Status' => 422,
                'Code' => 'missing_parameter',
                'Error' => 'One or more required parameters is missing.',
            ]);

        // blank user
        $this->post('dorequest.php', ['r' => 'login2', 'u' => '', 'p' => $password])
            ->assertStatus(401)
            ->assertExactJson([
                'Success' => false,
                'Status' => 401,
                'Code' => 'invalid_credentials',
                'Error' => 'Invalid user/password combination. Please try again.',
            ]);

        // no password or token
        $this->post('dorequest.php', ['r' => 'login2', 'u' => $user->username])
            ->assertStatus(422)
            ->assertExactJson([
                'Success' => false,
                'Status' => 422,
                'Code' => 'missing_parameter',
                'Error' => 'One or more required parameters is missing.',
            ]);

        // no user or password
        $this->post('dorequest.php', ['r' => 'login2'])
            ->assertStatus(422)
            ->assertExactJson([
                'Success' => false,
                'Status' => 422,
                'Code' => 'missing_parameter',
                'Error' => 'One or more required parameters is missing.',
            ]);

        // blank password
        $this->post('dorequest.php', ['r' => 'login2', 'u' => $user->username, 'p' => ''])
            ->assertStatus(401)
            ->assertExactJson([
                'Success' => false,
                'Status' => 401,
                'Code' => 'invalid_credentials',
                'Error' => 'Invalid user/password combination. Please try again.',
            ]);

        // expired token
        $user->connect_token_expires_at = Carbon::now()->clone()->subDays(15);
        $user->timestamps = false;
        $user->save();
        $this->post('dorequest.php', ['r' => 'login2', 'u' => $user->username, 't' => $user->connect_token])
            ->assertStatus(401)
            ->assertHeader('WWW-Authenticate', 'Bearer')
            ->assertExactJson([
                'Success' => false,
                'Status' => 401,
                'Code' => 'expired_token',
                'Error' => 'The access token has expired. Please log in again.',
            ]);

        // try with banned user - response should be the same as a non-existent user
        /** @var User $user2 */
        $user2 = User::factory()->create(['Permissions' => Permissions::Banned, 'banned_at' => Carbon::now()->clone()->subMonths(2), 'password' => Hash::make($password)]);
        $this->post('dorequest.php', ['r' => 'login2', 'u' => $user2->username, 'p' => $password])
            ->assertStatus(401)
            ->assertHeader('WWW-Authenticate', 'Bearer')
            ->assertExactJson([
                'Success' => false,
                'Status' => 401,
                'Code' => 'invalid_credentials',
                'Error' => 'Invalid user/password combination. Please try again.',
            ]);

        // try with unregistered user - expect permissions error
        /** @var User $user3 */
        $user3 = User::factory()->create(['Permissions' => Permissions::Unregistered, 'password' => Hash::make($password)]);
        $this->post('dorequest.php', ['r' => 'login2', 'u' => $user3->username, 'p' => $password])
            ->assertStatus(403)
            ->assertExactJson([
                'Success' => false,
                'Status' => 403,
                'Code' => 'access_denied',
                'Error' => 'Access denied. Please verify your email address.',
            ]);
    }

    public function testInvalidCredentialsLegacy(): void
    {
        // This is the exact same set of tests as testInvalidCredentials, but it goes through
        // the login API instead of the login2 API. The login API always returns a 200 status
        // code so legacy clients will still look at the response body instead of just failing
        // when they see the non-200 status code.

        Carbon::setTestNow(Carbon::now());
        $password = 'Pa$$w0rd';

        /** @var User $user */
        $user = User::factory()->create([
            'connect_token' => Str::random(16),
            'password' => Hash::make($password),
        ]);

        // invalid password
        $this->get($this->apiUrl('login', ['u' => $user->username, 'p' => $password . '1'], credentials: false))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => false,
                'Status' => 401,
                'Code' => 'invalid_credentials',
                'Error' => 'Invalid user/password combination. Please try again.',
            ]);

        // invalid token
        $this->get($this->apiUrl('login', ['u' => $user->username, 't' => $user->connect_token . '1'], credentials: false))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => false,
                'Status' => 401,
                'Code' => 'invalid_credentials',
                'Error' => 'Invalid user/token combination.',
            ]);

        // unknown user
        $this->get($this->apiUrl('login', ['u' => $user->username . '1', 'p' => $password], credentials: false))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => false,
                'Status' => 401,
                'Code' => 'invalid_credentials',
                'Error' => 'Invalid user/password combination. Please try again.',
            ]);

        // no user
        $this->get($this->apiUrl('login', ['p' => $password], credentials: false))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => false,
                'Status' => 422,
                'Code' => 'missing_parameter',
                'Error' => 'One or more required parameters is missing.',
            ]);

        // blank user
        $this->post($this->apiUrl('login', ['u' => '', 'p' => $password], credentials: false))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => false,
                'Status' => 401,
                'Code' => 'invalid_credentials',
                'Error' => 'Invalid user/password combination. Please try again.',
            ]);

        // no password or token
        $this->get($this->apiUrl('login', ['u' => $user->username], credentials: false))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => false,
                'Status' => 422,
                'Code' => 'missing_parameter',
                'Error' => 'One or more required parameters is missing.',
            ]);

        // blank password
        $this->get($this->apiUrl('login', ['u' => $user->username, 'p' => ''], credentials: false))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => false,
                'Status' => 401,
                'Code' => 'invalid_credentials',
                'Error' => 'Invalid user/password combination. Please try again.',
            ]);

        // no user or password
        $this->get($this->apiUrl('login', [], credentials: false))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => false,
                'Status' => 422,
                'Code' => 'missing_parameter',
                'Error' => 'One or more required parameters is missing.',
            ]);

        // expired token
        $user->connect_token_expires_at = Carbon::now()->clone()->subDays(15);
        $user->timestamps = false;
        $user->save();
        $this->get($this->apiUrl('login', ['u' => $user->username, 't' => $user->connect_token], credentials: false))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => false,
                'Status' => 401,
                'Code' => 'expired_token',
                'Error' => 'The access token has expired. Please log in again.',
            ]);

        // try with banned user - response should be the same as a non-existent user
        /** @var User $user2 */
        $user2 = User::factory()->create(['Permissions' => Permissions::Banned, 'banned_at' => Carbon::now()->clone()->subMonths(2), 'password' => Hash::make($password)]);
        $this->get($this->apiUrl('login', ['u' => $user2->username, 'p' => $password], credentials: false))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => false,
                'Status' => 401,
                'Code' => 'invalid_credentials',
                'Error' => 'Invalid user/password combination. Please try again.',
            ]);

        // try with unregistered user - expect permissions error
        /** @var User $user3 */
        $user3 = User::factory()->create(['Permissions' => Permissions::Unregistered, 'password' => Hash::make($password)]);
        $this->get($this->apiUrl('login', ['u' => $user3->username, 'p' => $password], credentials: false))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => false,
                'Status' => 403,
                'Code' => 'access_denied',
                'Error' => 'Access denied. Please verify your email address.',
            ]);
    }
}
