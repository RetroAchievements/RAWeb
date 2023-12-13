<?php

declare(strict_types=1);

namespace Tests\Feature\Connect;

use App\Site\Enums\Permissions;
use App\Site\Models\User;
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
            'appToken' => Str::random(16),
            'Password' => Hash::make($password),
            'Permissions' => Permissions::JuniorDeveloper,
            'RAPoints' => 12345,
            'RASoftcorePoints' => 4321,
        ]);

        $this->get($this->apiUrl('login', ['u' => $user->User, 'p' => $password], credentials: false))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'User' => $user->User,
                'Token' => $user->appToken,
                'Score' => 12345,
                'SoftcoreScore' => 4321,
                'Messages' => 0,
                'Permissions' => Permissions::JuniorDeveloper,
                'AccountType' => 'Junior Developer',
            ]);

        /** @var User $user1 */
        $user1 = User::firstWhere('User', $user->User);
        $this->assertEquals(Carbon::now()->clone()->addDays(14)->startOfSecond(), $user1->appTokenExpiry);

        // === with token ===

        $this->get($this->apiUrl('login', ['u' => $user->User, 't' => $user->appToken], credentials: false))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'User' => $user->User,
                'Token' => $user->appToken,
                'Score' => 12345,
                'SoftcoreScore' => 4321,
                'Messages' => 0,
                'Permissions' => Permissions::JuniorDeveloper,
                'AccountType' => 'Junior Developer',
            ]);

        // === with legacy password (and no previous appToken) ===

        /** @var User $user2 */
        $user2 = User::factory()->create([
            'appToken' => '',
            'SaltedPass' => md5($password . config('app.legacy_password_salt')),
            'Permissions' => Permissions::Developer,
            'RAPoints' => 99999,
            'RASoftcorePoints' => 99,
        ]);

        $response = $this->get($this->apiUrl('login', ['u' => $user2->User, 'p' => $password], credentials: false));

        /* direct query to access non-visible attributes */
        $data = legacyDbFetch('SELECT appToken, SaltedPass, Password FROM UserAccounts WHERE User=:user', ['user' => $user2->User]);
        $this->assertNotEquals('', $data['appToken']);
        $this->assertEquals('', $data['SaltedPass']);
        $this->assertTrue(Hash::check($password, $data['Password']));

        $response->assertStatus(200)->assertExactJson([
            'Success' => true,
            'User' => $user2->User,
            'Token' => $data['appToken'],
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
            'appToken' => Str::random(16),
            'Password' => Hash::make($password),
        ]);

        // invalid password
        $this->post('dorequest.php', ['r' => 'login2', 'u' => $user->User, 'p' => $password . '1'])
            ->assertStatus(401)
            ->assertHeader('WWW-Authenticate', 'Bearer')
            ->assertExactJson([
                'Success' => false,
                'Status' => 401,
                'Code' => 'invalid_credentials',
                'Error' => 'Invalid user/password combination. Please try again.',
            ]);

        // invalid token
        $this->post('dorequest.php', ['r' => 'login2', 'u' => $user->User, 't' => $user->appToken . '1'])
            ->assertStatus(401)
            ->assertHeader('WWW-Authenticate', 'Bearer')
            ->assertExactJson([
                'Success' => false,
                'Status' => 401,
                'Code' => 'invalid_credentials',
                'Error' => 'Invalid user/token combination.',
            ]);

        // unknown user
        $this->post('dorequest.php', ['r' => 'login2', 'u' => $user->User . '1', 'p' => $password])
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
            ->assertStatus(401)
            ->assertHeader('WWW-Authenticate', 'Bearer')
            ->assertExactJson([
                'Success' => false,
                'Status' => 401,
                'Code' => 'invalid_credentials',
                'Error' => 'Invalid username. Please try again.',
            ]);

        // no password or token
        $this->post('dorequest.php', ['r' => 'login2', 'u' => $user->User])
            ->assertStatus(401)
            ->assertHeader('WWW-Authenticate', 'Bearer')
            ->assertExactJson([
                'Success' => false,
                'Status' => 401,
                'Code' => 'invalid_credentials',
                'Error' => 'Invalid user/password combination. Please try again.',
            ]);

        // expired token
        $user->appTokenExpiry = Carbon::now()->clone()->subDays(15);
        $user->timestamps = false;
        $user->save();
        $this->post('dorequest.php', ['r' => 'login2', 'u' => $user->User, 't' => $user->appToken])
            ->assertStatus(401)
            ->assertHeader('WWW-Authenticate', 'Bearer')
            ->assertExactJson([
                'Success' => false,
                'Status' => 401,
                'Code' => 'expired_token',
                'Error' => 'The access token has expired. Please log in again.',
            ]);

        // try with banned user - response should be the same as a non-existant user
        /** @var User $user2 */
        $user2 = User::factory()->create(['Permissions' => Permissions::Banned, 'Password' => Hash::make($password)]);
        $this->post('dorequest.php', ['r' => 'login2', 'u' => $user2->User, 'p' => $password])
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
        $user3 = User::factory()->create(['Permissions' => Permissions::Unregistered, 'Password' => Hash::make($password)]);
        $this->post('dorequest.php', ['r' => 'login2', 'u' => $user3->User, 'p' => $password])
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
            'appToken' => Str::random(16),
            'Password' => Hash::make($password),
        ]);

        // invalid password
        $this->get($this->apiUrl('login', ['u' => $user->User, 'p' => $password . '1'], credentials: false))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => false,
                'Status' => 401,
                'Code' => 'invalid_credentials',
                'Error' => 'Invalid user/password combination. Please try again.',
            ]);

        // invalid token
        $this->get($this->apiUrl('login', ['u' => $user->User, 't' => $user->appToken . '1'], credentials: false))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => false,
                'Status' => 401,
                'Code' => 'invalid_credentials',
                'Error' => 'Invalid user/token combination.',
            ]);

        // unknown user
        $this->get($this->apiUrl('login', ['u' => $user->User . '1', 'p' => $password], credentials: false))
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
                'Status' => 401,
                'Code' => 'invalid_credentials',
                'Error' => 'Invalid username. Please try again.',
            ]);

        // no password or token
        $this->get($this->apiUrl('login', ['u' => $user->User], credentials: false))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => false,
                'Status' => 401,
                'Code' => 'invalid_credentials',
                'Error' => 'Invalid user/password combination. Please try again.',
            ]);

        // expired token
        $user->appTokenExpiry = Carbon::now()->clone()->subDays(15);
        $user->timestamps = false;
        $user->save();
        $this->get($this->apiUrl('login', ['u' => $user->User, 't' => $user->appToken], credentials: false))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => false,
                'Status' => 401,
                'Code' => 'expired_token',
                'Error' => 'The access token has expired. Please log in again.',
            ]);

        // try with banned user - response should be the same as a non-existant user
        /** @var User $user2 */
        $user2 = User::factory()->create(['Permissions' => Permissions::Banned, 'Password' => Hash::make($password)]);
        $this->get($this->apiUrl('login', ['u' => $user2->User, 'p' => $password], credentials: false))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => false,
                'Status' => 401,
                'Code' => 'invalid_credentials',
                'Error' => 'Invalid user/password combination. Please try again.',
            ]);

        // try with unregistered user - expect permissions error
        /** @var User $user3 */
        $user3 = User::factory()->create(['Permissions' => Permissions::Unregistered, 'Password' => Hash::make($password)]);
        $this->get($this->apiUrl('login', ['u' => $user3->User, 'p' => $password], credentials: false))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => false,
                'Status' => 403,
                'Code' => 'access_denied',
                'Error' => 'Access denied. Please verify your email address.',
            ]);
    }
}
