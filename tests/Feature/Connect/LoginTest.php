<?php

declare(strict_types=1);

namespace Tests\Feature\Connect;

use App\Community\Enums\ActivityType;
use App\Community\Models\UserActivityLegacy;
use App\Site\Enums\Permissions;
use App\Site\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
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
            'Password' => hashPassword($password),
            'Permissions' => Permissions::JuniorDeveloper,
            'RAPoints' => 12345,
            'RASoftcorePoints' => 4321,
        ]);

        $this->get($this->apiUrl('login', ['u' => $user->User, 'p' => $password], credentials: false))
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

        /** @var UserActivityLegacy $activity */
        $activity = UserActivityLegacy::latest()->first();
        $this->assertEquals(ActivityType::Login, $activity->activitytype);
        $this->assertEquals($user->User, $activity->User);

        // === with token ===

        $this->get($this->apiUrl('login', ['u' => $user->User, 't' => $user->appToken], credentials: false))
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
        $this->assertTrue(password_verify($password, $data['Password']));

        $response->assertExactJson([
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
            'Password' => hashPassword($password),
        ]);

        // invalid password
        $this->get($this->apiUrl('login', ['u' => $user->User, 'p' => $password . '1'], credentials: false))
            ->assertStatus(401)
            ->assertExactJson([
                'Success' => false,
                'Status' => 401,
                'Code' => 'invalid_credentials',
                'Error' => 'Invalid User/Password combination. Please try again.',
            ]);

        // invalid token
        $this->get($this->apiUrl('login', ['u' => $user->User, 't' => $user->appToken . '1'], credentials: false))
            ->assertStatus(401)
            ->assertExactJson([
                'Success' => false,
                'Status' => 401,
                'Code' => 'invalid_credentials',
                'Error' => 'Invalid User/Token combination. Please try again.',
            ]);

        // unknown user
        $this->get($this->apiUrl('login', ['u' => $user->User . '1', 'p' => $password], credentials: false))
            ->assertStatus(401)
            ->assertExactJson([
                'Success' => false,
                'Status' => 401,
                'Code' => 'invalid_credentials',
                'Error' => 'Invalid User/Password combination. Please try again.',
            ]);

        // no user
        $this->get($this->apiUrl('login', ['p' => $password], credentials: false))
            ->assertStatus(401)
            ->assertExactJson([
                'Success' => false,
                'Status' => 401,
                'Code' => 'invalid_credentials',
                'Error' => 'Invalid User/Password combination. Please try again.',
            ]);

        // no password or token
        $this->get($this->apiUrl('login', ['u' => $user->User], credentials: false))
            ->assertStatus(401)
            ->assertExactJson([
                'Success' => false,
                'Status' => 401,
                'Code' => 'invalid_credentials',
                'Error' => 'Invalid User/Password combination. Please try again.',
            ]);

        // expired token
        $user->appTokenExpiry = Carbon::now()->clone()->subDays(15);
        $user->timestamps = false;
        $user->save();
        $this->get($this->apiUrl('login', ['u' => $user->User, 't' => $user->appToken], credentials: false))
            ->assertStatus(401)
            ->assertExactJson([
                'Success' => false,
                'Status' => 401,
                'Code' => 'expired_token',
                'Error' => 'The access token has expired. Please log in again.',
            ]);

    }
}
