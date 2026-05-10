<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
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

    $this->post('dorequest.php', ['r' => 'login2', 'u' => 'someuser'])
        ->assertUnprocessable()
        ->assertHeader('X-RateLimit-Limit', '5');
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

    for ($attempt = 1; $attempt <= 5; $attempt++) {
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

it('caps login attempts per IP even when the attacker rotates usernames', function () {
    for ($attempt = 1; $attempt <= 30; $attempt++) {
        $this->post('dorequest.php', ['r' => 'login2', 'u' => "user{$attempt}"])
            ->assertUnprocessable();
    }

    $this->post('dorequest.php', ['r' => 'login2', 'u' => 'user31'])
        ->assertTooManyRequests();
});
