<?php

declare(strict_types=1);

use App\Http\ResponseCache\AnonymousCacheProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->profile = new AnonymousCacheProfile();
});

describe('shouldCacheRequest', function () {
    it('caches anonymous GET requests', function () {
        // ARRANGE
        $request = Request::create('/game/1', 'GET');

        // ACT
        $result = $this->profile->shouldCacheRequest($request);

        // ASSERT
        expect($result)->toBeTrue();
    });

    it('rejects non-GET requests', function () {
        // ARRANGE
        $request = Request::create('/game/1', 'POST');

        // ACT
        $result = $this->profile->shouldCacheRequest($request);

        // ASSERT
        expect($result)->toBeFalse();
    });

    it('rejects authenticated requests', function () {
        // ARRANGE
        Auth::login(User::factory()->create());
        $request = Request::create('/game/1', 'GET');

        // ACT
        $result = $this->profile->shouldCacheRequest($request);

        // ASSERT
        expect($result)->toBeFalse();
    });

    it('rejects Inertia partial reloads', function () {
        // ARRANGE
        $request = Request::create('/game/1', 'GET');
        $request->headers->set('X-Inertia-Partial-Component', 'Game/Show');

        // ACT
        $result = $this->profile->shouldCacheRequest($request);

        // ASSERT
        expect($result)->toBeFalse();
    });
});

describe('shouldCacheResponse', function () {
    it('caches successful HTML and JSON responses', function (string $contentType) {
        // ARRANGE
        $response = new Response('OK', 200, ['Content-Type' => $contentType]);

        // ACT
        $result = $this->profile->shouldCacheResponse($response);

        // ASSERT
        expect($result)->toBeTrue();
    })->with([
        'text/html; charset=utf-8',
        'application/json',
    ]);

    it('rejects redirects, errors, and binary responses', function (int $status, string $contentType) {
        // ARRANGE
        $response = new Response('', $status, ['Content-Type' => $contentType]);

        // ACT
        $result = $this->profile->shouldCacheResponse($response);

        // ASSERT
        expect($result)->toBeFalse();
    })->with([
        'redirect' => [302, 'text/html'],
        'not found' => [404, 'text/html'],
        'binary' => [200, 'image/png'],
        'css' => [200, 'text/css'],
    ]);
});

describe('useCacheNameSuffix', function () {
    it('returns "anonymous" for guests and the user ID for authenticated users', function () {
        // ARRANGE
        $user = User::factory()->create();
        $request = Request::create('/game/1', 'GET');

        // ACT
        $anonymousSuffix = $this->profile->useCacheNameSuffix($request);
        Auth::login($user);
        $authenticatedSuffix = $this->profile->useCacheNameSuffix($request);

        // ASSERT
        expect($anonymousSuffix)->toEqual('anonymous');
        expect($authenticatedSuffix)->toEqual((string) $user->id);
    });
});
