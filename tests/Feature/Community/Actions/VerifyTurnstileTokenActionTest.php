<?php

declare(strict_types=1);

use App\Community\Actions\VerifyTurnstileTokenAction;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    config(['services.cloudflare.turnstile_secret_key' => 'test-secret-key']);
});

it('given no token was submitted, it rejects without calling Cloudflare', function () {
    // ARRANGE
    Http::fake();

    // ACT
    $result = (new VerifyTurnstileTokenAction())->execute('', '203.0.113.7');

    // ASSERT
    expect($result)->toEqual(false);
    Http::assertNothingSent();
});

it('given Cloudflare accepts the token, it allows the submission', function () {
    // ARRANGE
    Http::fake([
        'challenges.cloudflare.com/*' => Http::response(['success' => true]),
    ]);

    // ACT
    $result = (new VerifyTurnstileTokenAction())->execute('valid-token', '203.0.113.7');

    // ASSERT
    expect($result)->toEqual(true);
});

it('given Cloudflare rejects the token, it rejects the submission', function () {
    // ARRANGE
    Http::fake([
        'challenges.cloudflare.com/*' => Http::response([
            'success' => false,
            'error-codes' => ['invalid-input-response'],
        ]),
    ]);

    // ACT
    $result = (new VerifyTurnstileTokenAction())->execute('bogus-token', '203.0.113.7');

    // ASSERT
    expect($result)->toEqual(false);
});

it('given the token was already spent, it rejects the submission', function () {
    // ARRANGE
    Http::fake([
        'challenges.cloudflare.com/*' => Http::response([
            'success' => false,
            'error-codes' => ['timeout-or-duplicate'],
        ]),
    ]);

    // ACT
    $result = (new VerifyTurnstileTokenAction())->execute('replayed-token', '203.0.113.7');

    // ASSERT
    expect($result)->toEqual(false);
});

it('given Cloudflare is unreachable, it allows the submission and logs a warning', function () {
    // ARRANGE
    $log = Log::spy();
    Http::fake(function () {
        throw new ConnectionException('Connection timed out');
    });

    // ACT
    $result = (new VerifyTurnstileTokenAction())->execute('valid-token', '203.0.113.7');

    // ASSERT
    expect($result)->toEqual(true);
    /** @var Mockery\Expectation $warning */
    $warning = $log->shouldHaveReceived('warning');
    $warning->once();
});

it('given a token and an IP, then it sends the configured secret, the token, and the IP', function () {
    // ARRANGE
    Http::fake([
        'challenges.cloudflare.com/*' => Http::response(['success' => true]),
    ]);

    // ACT
    (new VerifyTurnstileTokenAction())->execute('valid-token', '203.0.113.7');

    // ASSERT
    Http::assertSent(function ($request) {
        return
            $request->url() === 'https://challenges.cloudflare.com/turnstile/v0/siteverify'
            && $request['secret'] === 'test-secret-key'
            && $request['response'] === 'valid-token'
            && $request['remoteip'] === '203.0.113.7';
    });
});
