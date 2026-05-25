<?php

declare(strict_types=1);

use App\Enums\Permissions;
use App\Models\Achievement;
use App\Models\Game;
use App\Models\System;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Feature\Connect\TestsConnect;

uses(TestsConnect::class, RefreshDatabase::class);

beforeEach(function () {
    $this->createConnectUser();
});

it('segments dorequest rate limits by request type', function () {
    $this->post('dorequest.php', $this->apiParams('submitcodenote'))
        ->assertUnprocessable()
        ->assertHeader('X-RateLimit-Limit', '600');

    $this->post('dorequest.php', $this->apiParams('ping'))
        ->assertUnprocessable()
        ->assertHeader('X-RateLimit-Limit', '180');

    $this->post('dorequest.php', $this->apiParams('submitgametitle'))
        ->assertUnprocessable()
        ->assertHeader('X-RateLimit-Limit', '180');

    $this->post('dorequest.php', $this->apiParams('submitrichpresence'))
        ->assertUnprocessable()
        ->assertHeader('X-RateLimit-Limit', '180');

    $this->post('dorequest.php', ['r' => 'login2', 'u' => 'someuser'])
        ->assertUnprocessable()
        ->assertHeader('X-RateLimit-Limit', '30');
});

it('applies a generous rate limit to delegated connect requests', function () {
    /** @var System $standalonesSystem */
    $standalonesSystem = System::factory()->create(['id' => System::Standalones]);
    /** @var Game $game */
    $game = Game::factory()->create(['system_id' => $standalonesSystem->id]);
    /** @var User $delegatedUser */
    $delegatedUser = User::factory()->create([
        'Permissions' => Permissions::Registered,
        'connect_token' => Str::random(16),
    ]);

    Achievement::factory()->promoted()->create([
        'game_id' => $game->id,
        'user_id' => $this->user->id,
    ]);

    $params = $this->apiParams('ping', [
        'g' => $game->id,
        'k' => $delegatedUser->username,
        'm' => 'Doing good',
    ]);

    $this->post('dorequest.php', $params)
        ->assertOk()
        ->assertJson(['Success' => true])
        ->assertHeader('X-RateLimit-Limit', '6000');
});

it('does not treat k as a rate limit bypass for non-delegated request types', function () {
    $this->post('dorequest.php', $this->apiParams('submitcodenote', ['k' => 'SomeUser']))
        ->assertUnprocessable()
        ->assertHeader('X-RateLimit-Limit', '600');
});

it('does not let an unauthenticated caller bypass the rate limit by adding k', function () {
    $this->post('dorequest.php', ['r' => 'ping', 'u' => 'SomeUser', 'k' => 'AnotherUser'])
        ->assertHeader('X-RateLimit-Limit', '180');
});

it('does not let a GET request bypass the rate limit by adding k', function () {
    $this->get($this->apiUrl('ping', ['g' => 1, 'k' => 'TargetUser', 'm' => 'hi']))
        ->assertHeader('X-RateLimit-Limit', '180');
});

it('caps authenticated developer publish at 600 per minute', function () {
    for ($attempt = 1; $attempt <= 600; $attempt++) {
        $this->post('dorequest.php', $this->apiParams('submitcodenote'))
            ->assertUnprocessable();
    }

    $this->post('dorequest.php', $this->apiParams('submitcodenote'))
        ->assertTooManyRequests();
});

it('does not let a spoofed username consume the authenticated developer publish quota', function () {
    $spoofedParams = [
        'r' => 'submitcodenote',
        'u' => $this->user->username,
        't' => 'invalid-token',
    ];

    for ($attempt = 1; $attempt <= 600; $attempt++) {
        $response = $this->post('dorequest.php', $spoofedParams);

        if ($attempt === 1 || $attempt === 600) {
            $response->assertUnauthorized();
        }
    }

    $this->post('dorequest.php', $spoofedParams)
        ->assertTooManyRequests();

    $this->post('dorequest.php', $this->apiParams('submitcodenote'))
        ->assertUnprocessable()
        ->assertHeader('X-RateLimit-Limit', '600')
        ->assertHeader('X-RateLimit-Remaining', '599');
});

it('returns the connect JSON shape and a Retry-After header when login is rate limited', function () {
    $params = ['r' => 'login2', 'u' => 'someuser'];

    for ($attempt = 1; $attempt <= 30; $attempt++) {
        $this->post('dorequest.php', $params)
            ->assertUnprocessable();
    }

    $response = $this->post('dorequest.php', $params);

    $response
        ->assertTooManyRequests()
        ->assertExactJson([
            'Success' => false,
            'Error' => 'Too Many Attempts',
            'Status' => 429,
        ]);

    $response->assertHeader('Retry-After');
});

it('does not count successful login responses toward the login rate limit', function () {
    $this->user->update(['Permissions' => Permissions::Registered]);

    // The broader of the two login buckets caps at 300 per IP.
    // Succeeding past that proves successful logins aren't counted by either bucket.
    for ($attempt = 1; $attempt <= 301; $attempt++) {
        $response = $this->post('dorequest.php', [
            'r' => 'login2',
            'u' => $this->user->username,
            'p' => 'password',
        ]);
    }

    $response->assertOk()
        ->assertJson(['Success' => true])
        ->assertHeaderMissing('Retry-After');
});

it('caps login attempts per IP even when the attacker rotates usernames', function () {
    for ($attempt = 1; $attempt <= 300; $attempt++) {
        $this->post('dorequest.php', ['r' => 'login2', 'u' => "user{$attempt}"])
            ->assertUnprocessable();
    }

    $this->post('dorequest.php', ['r' => 'login2', 'u' => 'user301'])
        ->assertTooManyRequests();
});
