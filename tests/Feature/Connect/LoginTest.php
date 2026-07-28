<?php

declare(strict_types=1);

namespace Tests\Feature\Connect;

use App\Enums\Permissions;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

uses(LazilyRefreshDatabase::class);
uses(TestsConnect::class);

beforeEach(function () {
    Carbon::setTestNow(Carbon::now()->startOfSecond());

    $this->createConnectUser();
});

describe('login', function () {
    test('with password', function () {
        $password = 'Pa$$w0rd';
        $this->user->display_name = 'DisplayName'; // unique display name
        $this->user->password = Hash::make($password);
        $this->user->Permissions = Permissions::JuniorDeveloper;
        $this->user->points_hardcore = 12345;
        $this->user->points = 4321;
        $this->user->save();

        $this->post('dorequest.php', ['r' => 'login2', 'u' => $this->user->username, 'p' => $password])
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'User' => $this->user->display_name,
                'AvatarUrl' => $this->user->avatar_url,
                'AvatarUpdatedAt' => 0,
                'Token' => $this->user->connect_token,
                'Score' => 12345,
                'SoftcoreScore' => 4321,
                'Messages' => 0,
                'Permissions' => Permissions::JuniorDeveloper,
                'AccountType' => 'Junior Developer',
            ]);

        // connect expiration should have been updated
        $this->user->refresh();
        $this->assertEquals(Carbon::now()->clone()->addDays(365)->startOfSecond(), $this->user->connect_token_expires_at);
    });

    test('with token', function () {
        $this->user->Permissions = Permissions::JuniorDeveloper;
        $this->user->points_hardcore = 12345;
        $this->user->points = 4321;
        $this->user->save();

        $this->post('dorequest.php', ['r' => 'login2', 'u' => $this->user->username, 't' => $this->user->connect_token])
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'User' => $this->user->display_name,
                'AvatarUrl' => $this->user->avatar_url,
                'AvatarUpdatedAt' => 0,
                'Token' => $this->user->connect_token,
                'Score' => 12345,
                'SoftcoreScore' => 4321,
                'Messages' => 0,
                'Permissions' => Permissions::JuniorDeveloper,
                'AccountType' => 'Junior Developer',
            ]);

        // connect expiration should have been updated
        $this->user->refresh();
        $this->assertEquals(Carbon::now()->clone()->addDays(365)->startOfSecond(), $this->user->connect_token_expires_at);
    });

    test('with legacy password', function () {
        $password = 'Pa$$w0rd';
        $this->user->legacy_salted_password = md5($password . config('app.legacy_password_salt'));
        $this->user->Permissions = Permissions::JuniorDeveloper;
        $this->user->points_hardcore = 12345;
        $this->user->points = 4321;
        $this->user->save();

        $this->post('dorequest.php', ['r' => 'login2', 'u' => $this->user->username, 'p' => $password])
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'User' => $this->user->display_name,
                'AvatarUrl' => $this->user->avatar_url,
                'AvatarUpdatedAt' => 0,
                'Token' => $this->user->connect_token,
                'Score' => 12345,
                'SoftcoreScore' => 4321,
                'Messages' => 0,
                'Permissions' => Permissions::JuniorDeveloper,
                'AccountType' => 'Junior Developer',
            ]);

        // connect expiration should have been updated
        $this->user->refresh();
        $this->assertEquals(Carbon::now()->clone()->addDays(365)->startOfSecond(), $this->user->connect_token_expires_at);

        // password should be initialized and legacy salted password should have been discarded
        $this->assertTrue(Hash::check($password, $this->user->password));
        $this->assertEquals('', $this->user->legacy_salted_password);
    });

    test('using display name', function () {
        $password = 'Pa$$w0rd';
        $this->user->display_name = 'DisplayName';
        $this->user->password = Hash::make($password);
        $this->user->Permissions = Permissions::JuniorDeveloper;
        $this->user->points_hardcore = 12345;
        $this->user->points = 4321;
        $this->user->save();

        $this->post('dorequest.php', ['r' => 'login2', 'u' => $this->user->display_name, 'p' => $password])
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'User' => $this->user->display_name,
                'AvatarUrl' => $this->user->avatar_url,
                'AvatarUpdatedAt' => 0,
                'Token' => $this->user->connect_token,
                'Score' => 12345,
                'SoftcoreScore' => 4321,
                'Messages' => 0,
                'Permissions' => Permissions::JuniorDeveloper,
                'AccountType' => 'Junior Developer',
            ]);

        // connect expiration should have been updated
        $this->user->refresh();
        $this->assertEquals(Carbon::now()->clone()->addDays(365)->startOfSecond(), $this->user->connect_token_expires_at);
    });

    // SQLite doesn't match case insensitively
    // test('case insensitive', function () {
    //     $password = 'Pa$$w0rd';
    //     $this->user->display_name = 'DisplayName';
    //     $this->user->username = 'UserName123';
    //     $this->user->password = Hash::make($password);
    //     $this->user->Permissions = Permissions::JuniorDeveloper;
    //     $this->user->points_hardcore = 12345;
    //     $this->user->points = 4321;
    //     $this->user->save();

    //     $this->post('dorequest.php', ['r' => 'login2', 'u' => 'username123', 'p' => $password])
    //         ->assertStatus(200)
    //         ->assertExactJson([
    //             'Success' => true,
    //             'User' => $this->user->display_name,
    //             'AvatarUrl' => $this->user->avatar_url,
    //             'AvatarUpdatedAt' => 0,
    //             'Token' => $this->user->connect_token,
    //             'Score' => 12345,
    //             'SoftcoreScore' => 4321,
    //             'Messages' => 0,
    //             'Permissions' => Permissions::JuniorDeveloper,
    //             'AccountType' => 'Junior Developer',
    //         ]);

    //     // connect expiration should have been updated
    //     $this->user->refresh();
    //     $this->assertEquals(Carbon::now()->clone()->addDays(365)->startOfSecond(), $this->user->connect_token_expires_at);
    // });

    test('avatar updated', function () {
        $this->user->avatar_updated_at = Carbon::parse('2026-07-02 10:00:00 UTC');
        $this->user->save();

        $this->post('dorequest.php', ['r' => 'login2', 'u' => $this->user->username, 't' => $this->user->connect_token])
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'User' => $this->user->display_name,
                'AvatarUrl' => media_asset("UserPic/{$this->user->username}.png"), // version not included in avatar url
                'AvatarUpdatedAt' => 1782986400,
                'Token' => $this->user->connect_token,
                'Score' => $this->user->points_hardcore,
                'SoftcoreScore' => $this->user->points,
                'Messages' => 0,
                'Permissions' => Permissions::Registered,
                'AccountType' => 'Registered',
            ]);
    });
});

describe('legacy login', function () {
    test('with password', function () {
        $password = 'Pa$$w0rd';
        $this->user->password = Hash::make($password);
        $this->user->Permissions = Permissions::JuniorDeveloper;
        $this->user->points_hardcore = 12345;
        $this->user->points = 4321;
        $this->user->save();

        $this->get($this->apiUrl('login', ['u' => $this->user->username, 'p' => $password], credentials: false))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'User' => $this->user->display_name,
                'AvatarUrl' => $this->user->avatar_url,
                'AvatarUpdatedAt' => 0,
                'Token' => $this->user->connect_token,
                'Score' => 12345,
                'SoftcoreScore' => 4321,
                'Messages' => 0,
                'Permissions' => Permissions::JuniorDeveloper,
                'AccountType' => 'Junior Developer',
            ]);

        // connect expiration should have been updated
        $this->user->refresh();
        $this->assertEquals(Carbon::now()->clone()->addDays(365)->startOfSecond(), $this->user->connect_token_expires_at);
    });

    test('with token', function () {
        $this->user->Permissions = Permissions::JuniorDeveloper;
        $this->user->points_hardcore = 12345;
        $this->user->points = 4321;
        $this->user->save();

        $this->get($this->apiUrl('login', ['u' => $this->user->username, 't' => $this->user->connect_token], credentials: false))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'User' => $this->user->display_name,
                'AvatarUrl' => $this->user->avatar_url,
                'AvatarUpdatedAt' => 0,
                'Token' => $this->user->connect_token,
                'Score' => 12345,
                'SoftcoreScore' => 4321,
                'Messages' => 0,
                'Permissions' => Permissions::JuniorDeveloper,
                'AccountType' => 'Junior Developer',
            ]);

        // connect expiration should have been updated
        $this->user->refresh();
        $this->assertEquals(Carbon::now()->clone()->addDays(365)->startOfSecond(), $this->user->connect_token_expires_at);
    });

    test('with legacy password', function () {
        $password = 'Pa$$w0rd';
        $this->user->legacy_salted_password = md5($password . config('app.legacy_password_salt'));
        $this->user->Permissions = Permissions::JuniorDeveloper;
        $this->user->points_hardcore = 12345;
        $this->user->points = 4321;
        $this->user->save();

        $this->get($this->apiUrl('login', ['u' => $this->user->username, 'p' => $password], credentials: false))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'User' => $this->user->display_name,
                'AvatarUrl' => $this->user->avatar_url,
                'AvatarUpdatedAt' => 0,
                'Token' => $this->user->connect_token,
                'Score' => 12345,
                'SoftcoreScore' => 4321,
                'Messages' => 0,
                'Permissions' => Permissions::JuniorDeveloper,
                'AccountType' => 'Junior Developer',
            ]);

        // connect expiration should have been updated
        $this->user->refresh();
        $this->assertEquals(Carbon::now()->clone()->addDays(365)->startOfSecond(), $this->user->connect_token_expires_at);

        // password should be initialized and legacy salted password should have been discarded
        $this->assertTrue(Hash::check($password, $this->user->password));
        $this->assertEquals('', $this->user->legacy_salted_password);
    });

    test('avatar updated', function () {
        $this->user->avatar_updated_at = Carbon::parse('2026-07-02 10:00:00 UTC');
        $this->user->save();

        $this->get($this->apiUrl('login', ['u' => $this->user->username, 't' => $this->user->connect_token], credentials: false))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'User' => $this->user->display_name,
                'AvatarUrl' => media_asset("UserPic/{$this->user->username}.png"), // version not included in avatar url
                'AvatarUpdatedAt' => 1782986400,
                'Token' => $this->user->connect_token,
                'Score' => $this->user->points_hardcore,
                'SoftcoreScore' => $this->user->points,
                'Messages' => 0,
                'Permissions' => Permissions::Registered,
                'AccountType' => 'Registered',
            ]);
    });
});

describe('validation', function () {
    test('invalid password', function () {
        $soon = Carbon::now()->clone()->addHours(3);
        $password = 'Pa$$w0rd';
        $this->user->password = Hash::make($password);
        $this->user->connect_token_expires_at = $soon;
        $this->user->save();

        $this->post('dorequest.php', ['r' => 'login2', 'u' => $this->user->username, 'p' => $password . '1'])
            ->assertStatus(401)
            ->assertHeader('WWW-Authenticate', 'Bearer')
            ->assertExactJson([
                'Success' => false,
                'Status' => 401,
                'Code' => 'invalid_credentials',
                'Error' => 'Invalid user/password combination. Please try again.',
            ]);

        // connect expiration should have not been updated
        $this->user->refresh();
        $this->assertEquals($soon, $this->user->connect_token_expires_at);
    });

    test('invalid token', function () {
        $soon = Carbon::now()->clone()->addHours(3);
        $this->user->connect_token_expires_at = $soon;
        $this->user->save();

        $this->post('dorequest.php', ['r' => 'login2', 'u' => $this->user->username, 't' => $this->user->connect_token . '1'])
            ->assertStatus(401)
            ->assertHeader('WWW-Authenticate', 'Bearer')
            ->assertExactJson([
                'Success' => false,
                'Status' => 401,
                'Code' => 'invalid_credentials',
                'Error' => 'Invalid user/token combination.',
            ]);

        // connect expiration should have not been updated
        $this->user->refresh();
        $this->assertEquals($soon, $this->user->connect_token_expires_at);
    });

    test('unregistered user', function () {
        $soon = Carbon::now()->clone()->addHours(3);
        $password = 'Pa$$w0rd';
        $this->user->password = Hash::make($password);
        $this->user->Permissions = Permissions::Unregistered;
        $this->user->connect_token_expires_at = $soon;
        $this->user->save();

        $this->post('dorequest.php', ['r' => 'login2', 'u' => $this->user->username, 'p' => $password])
            ->assertStatus(403)
            ->assertExactJson([
                'Success' => false,
                'Status' => 403,
                'Code' => 'access_denied',
                'Error' => 'Access denied. Please verify your email address.',
            ]);

        // connect expiration should have not been updated
        $this->user->refresh();
        $this->assertEquals($soon, $this->user->connect_token_expires_at);
    });

    test('unknown user', function () {
        $soon = Carbon::now()->clone()->addHours(3);
        $password = 'Pa$$w0rd';
        $this->user->password = Hash::make($password);
        $this->user->connect_token_expires_at = $soon;
        $this->user->save();

        $this->post('dorequest.php', ['r' => 'login2', 'u' => $this->user->username . '1', 'p' => $password])
            ->assertStatus(401)
            ->assertHeader('WWW-Authenticate', 'Bearer')
            ->assertExactJson([
                'Success' => false,
                'Status' => 401,
                'Code' => 'invalid_credentials',
                'Error' => 'Invalid user/password combination. Please try again.',
            ]);

        // connect expiration should have not been updated
        $this->user->refresh();
        $this->assertEquals($soon, $this->user->connect_token_expires_at);
    });

    test('no user', function () {
        $soon = Carbon::now()->clone()->addHours(3);
        $this->user->connect_token_expires_at = $soon;
        $this->user->save();

        $this->post('dorequest.php', ['r' => 'login2', 't' => $this->user->connect_token])
            ->assertStatus(422)
            ->assertExactJson([
                'Success' => false,
                'Status' => 422,
                'Code' => 'missing_parameter',
                'Error' => 'One or more required parameters is missing.',
            ]);

        // connect expiration should have not been updated
        $this->user->refresh();
        $this->assertEquals($soon, $this->user->connect_token_expires_at);
    });

    test('blank user', function () {
        $soon = Carbon::now()->clone()->addHours(3);
        $password = 'Pa$$w0rd';
        $this->user->password = Hash::make($password);
        $this->user->connect_token_expires_at = $soon;
        $this->user->save();

        $this->post('dorequest.php', ['r' => 'login2', 'u' => '', 'p' => $password])
            ->assertStatus(401)
            ->assertHeader('WWW-Authenticate', 'Bearer')
            ->assertExactJson([
                'Success' => false,
                'Status' => 401,
                'Code' => 'invalid_credentials',
                'Error' => 'Invalid user/password combination. Please try again.',
            ]);

        // connect expiration should have not been updated
        $this->user->refresh();
        $this->assertEquals($soon, $this->user->connect_token_expires_at);
    });

    test('banned user', function () {
        $soon = Carbon::now()->clone()->addHours(3);
        $password = 'Pa$$w0rd';
        $this->user->password = Hash::make($password);
        $this->user->Permissions = Permissions::Banned;
        $this->user->banned_at = Carbon::now()->clone()->subDays(3);
        $this->user->connect_token_expires_at = $soon;
        $this->user->save();

        // response should be the same as unknown user
        $this->post('dorequest.php', ['r' => 'login2', 'u' => $this->user->username, 'p' => $password])
            ->assertStatus(401)
            ->assertHeader('WWW-Authenticate', 'Bearer')
            ->assertExactJson([
                'Success' => false,
                'Status' => 401,
                'Code' => 'invalid_credentials',
                'Error' => 'Invalid user/password combination. Please try again.',
            ]);

        // connect expiration should have not been updated
        $this->user->refresh();
        $this->assertEquals($soon, $this->user->connect_token_expires_at);
    });

    test('no password or token', function () {
        $soon = Carbon::now()->clone()->addHours(3);
        $this->user->connect_token_expires_at = $soon;
        $this->user->save();

        $this->post('dorequest.php', ['r' => 'login2', 'u' => $this->user->username])
            ->assertStatus(422)
            ->assertExactJson([
                'Success' => false,
                'Status' => 422,
                'Code' => 'missing_parameter',
                'Error' => 'One or more required parameters is missing.',
            ]);

        // connect expiration should have not been updated
        $this->user->refresh();
        $this->assertEquals($soon, $this->user->connect_token_expires_at);
    });

    test('blank password', function () {
        $soon = Carbon::now()->clone()->addHours(3);
        $password = '';
        $this->user->password = Hash::make($password);
        $this->user->connect_token_expires_at = $soon;
        $this->user->save();

        $this->post('dorequest.php', ['r' => 'login2', 'u' => $this->user->username, 'p' => $password])
            ->assertStatus(401)
            ->assertHeader('WWW-Authenticate', 'Bearer')
            ->assertExactJson([
                'Success' => false,
                'Status' => 401,
                'Code' => 'invalid_credentials',
                'Error' => 'Invalid user/password combination. Please try again.',
            ]);

        // connect expiration should have not been updated
        $this->user->refresh();
        $this->assertEquals($soon, $this->user->connect_token_expires_at);
    });

    test('expired token', function () {
        $recently = Carbon::now()->clone()->subHours(3);
        $token = $this->user->connect_token;
        $this->user->connect_token_expires_at = $recently;
        $this->user->save();

        $this->post('dorequest.php', ['r' => 'login2', 'u' => $this->user->username, 't' => $token])
            ->assertStatus(401)
            ->assertExactJson([
                'Success' => false,
                'Status' => 401,
                'Code' => 'expired_token',
                'Error' => 'The access token has expired. Please log in again.',
            ]);

        // a new connect token will have been generated with a future expiration
        $this->user->refresh();
        $this->assertNotEquals($token, $this->user->connect_token);
        $this->assertEquals(Carbon::now()->clone()->addDays(365)->startOfSecond(), $this->user->connect_token_expires_at);
    });
});

describe('legacy validation', function () {
    test('invalid password', function () {
        $soon = Carbon::now()->clone()->addHours(3);
        $password = 'Pa$$w0rd';
        $this->user->password = Hash::make($password);
        $this->user->connect_token_expires_at = $soon;
        $this->user->save();

        $this->get($this->apiUrl('login', ['u' => $this->user->username, 'p' => $password . '1'], credentials: false))
            ->assertStatus(200)
            ->assertHeader('WWW-Authenticate', 'Bearer')
            ->assertExactJson([
                'Success' => false,
                'Status' => 401,
                'Code' => 'invalid_credentials',
                'Error' => 'Invalid user/password combination. Please try again.',
            ]);

        // connect expiration should have not been updated
        $this->user->refresh();
        $this->assertEquals($soon, $this->user->connect_token_expires_at);
    });

    test('invalid token', function () {
        $soon = Carbon::now()->clone()->addHours(3);
        $this->user->connect_token_expires_at = $soon;
        $this->user->save();

        $this->get($this->apiUrl('login', ['u' => $this->user->username, 't' => $this->user->connect_token . '1'], credentials: false))
            ->assertStatus(200)
            ->assertHeader('WWW-Authenticate', 'Bearer')
            ->assertExactJson([
                'Success' => false,
                'Status' => 401,
                'Code' => 'invalid_credentials',
                'Error' => 'Invalid user/token combination.',
            ]);

        // connect expiration should have not been updated
        $this->user->refresh();
        $this->assertEquals($soon, $this->user->connect_token_expires_at);
    });

    test('unregistered user', function () {
        $soon = Carbon::now()->clone()->addHours(3);
        $password = 'Pa$$w0rd';
        $this->user->password = Hash::make($password);
        $this->user->Permissions = Permissions::Unregistered;
        $this->user->connect_token_expires_at = $soon;
        $this->user->save();

        $this->get($this->apiUrl('login', ['u' => $this->user->username, 'p' => $password], credentials: false))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => false,
                'Status' => 403,
                'Code' => 'access_denied',
                'Error' => 'Access denied. Please verify your email address.',
            ]);

        // connect expiration should have not been updated
        $this->user->refresh();
        $this->assertEquals($soon, $this->user->connect_token_expires_at);
    });

    test('unknown user', function () {
        $soon = Carbon::now()->clone()->addHours(3);
        $password = 'Pa$$w0rd';
        $this->user->password = Hash::make($password);
        $this->user->connect_token_expires_at = $soon;
        $this->user->save();

        $this->get($this->apiUrl('login', ['u' => $this->user->username . '1', 'p' => $password], credentials: false))
            ->assertStatus(200)
            ->assertHeader('WWW-Authenticate', 'Bearer')
            ->assertExactJson([
                'Success' => false,
                'Status' => 401,
                'Code' => 'invalid_credentials',
                'Error' => 'Invalid user/password combination. Please try again.',
            ]);

        // connect expiration should have not been updated
        $this->user->refresh();
        $this->assertEquals($soon, $this->user->connect_token_expires_at);
    });

    test('no user', function () {
        $soon = Carbon::now()->clone()->addHours(3);
        $this->user->connect_token_expires_at = $soon;
        $this->user->save();

        $this->get($this->apiUrl('login', ['t' => $this->user->connect_token], credentials: false))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => false,
                'Status' => 422,
                'Code' => 'missing_parameter',
                'Error' => 'One or more required parameters is missing.',
            ]);

        // connect expiration should have not been updated
        $this->user->refresh();
        $this->assertEquals($soon, $this->user->connect_token_expires_at);
    });

    test('blank user', function () {
        $soon = Carbon::now()->clone()->addHours(3);
        $password = 'Pa$$w0rd';
        $this->user->password = Hash::make($password);
        $this->user->connect_token_expires_at = $soon;
        $this->user->save();

        $this->get($this->apiUrl('login', ['u' => '', 'p' => $password], credentials: false))
            ->assertStatus(200)
            ->assertHeader('WWW-Authenticate', 'Bearer')
            ->assertExactJson([
                'Success' => false,
                'Status' => 401,
                'Code' => 'invalid_credentials',
                'Error' => 'Invalid user/password combination. Please try again.',
            ]);

        // connect expiration should have not been updated
        $this->user->refresh();
        $this->assertEquals($soon, $this->user->connect_token_expires_at);
    });

    test('banned user', function () {
        $soon = Carbon::now()->clone()->addHours(3);
        $password = 'Pa$$w0rd';
        $this->user->password = Hash::make($password);
        $this->user->Permissions = Permissions::Banned;
        $this->user->banned_at = Carbon::now()->clone()->subDays(3);
        $this->user->connect_token_expires_at = $soon;
        $this->user->save();

        // response should be the same as unknown user
        $this->get($this->apiUrl('login', ['u' => $this->user->username, 'p' => $password], credentials: false))
            ->assertStatus(200)
            ->assertHeader('WWW-Authenticate', 'Bearer')
            ->assertExactJson([
                'Success' => false,
                'Status' => 401,
                'Code' => 'invalid_credentials',
                'Error' => 'Invalid user/password combination. Please try again.',
            ]);

        // connect expiration should have not been updated
        $this->user->refresh();
        $this->assertEquals($soon, $this->user->connect_token_expires_at);
    });

    test('no password or token', function () {
        $soon = Carbon::now()->clone()->addHours(3);
        $this->user->connect_token_expires_at = $soon;
        $this->user->save();

        $this->get($this->apiUrl('login', ['u' => $this->user->username], credentials: false))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => false,
                'Status' => 422,
                'Code' => 'missing_parameter',
                'Error' => 'One or more required parameters is missing.',
            ]);

        // connect expiration should have not been updated
        $this->user->refresh();
        $this->assertEquals($soon, $this->user->connect_token_expires_at);
    });

    test('blank password', function () {
        $soon = Carbon::now()->clone()->addHours(3);
        $password = '';
        $this->user->password = Hash::make($password);
        $this->user->connect_token_expires_at = $soon;
        $this->user->save();

        $this->get($this->apiUrl('login', ['u' => $this->user->username, 'p' => $password], credentials: false))
            ->assertStatus(200)
            ->assertHeader('WWW-Authenticate', 'Bearer')
            ->assertExactJson([
                'Success' => false,
                'Status' => 401,
                'Code' => 'invalid_credentials',
                'Error' => 'Invalid user/password combination. Please try again.',
            ]);

        // connect expiration should have not been updated
        $this->user->refresh();
        $this->assertEquals($soon, $this->user->connect_token_expires_at);
    });

    test('expired token', function () {
        $recently = Carbon::now()->clone()->subHours(3);
        $token = $this->user->connect_token;
        $this->user->connect_token_expires_at = $recently;
        $this->user->save();

        $this->get($this->apiUrl('login', ['u' => $this->user->username, 't' => $this->user->connect_token], credentials: false))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => false,
                'Status' => 401,
                'Code' => 'expired_token',
                'Error' => 'The access token has expired. Please log in again.',
            ]);

        // a new connect token will have been generated with a future expiration
        $this->user->refresh();
        $this->assertNotEquals($token, $this->user->connect_token);
        $this->assertEquals(Carbon::now()->clone()->addDays(365)->startOfSecond(), $this->user->connect_token_expires_at);
    });

});
