<?php

declare(strict_types=1);

use App\Http\ResponseCache\AnonymousCacheProfile;
use App\Http\ResponseCache\InertiaAwareHasher;
use Illuminate\Http\Request;

function createHasher(): InertiaAwareHasher
{
    return new InertiaAwareHasher(new AnonymousCacheProfile());
}

describe('getHashFor', function () {
    it('produces different hashes for HTML vs Inertia requests to the same URL', function () {
        // ARRANGE
        $hasher = createHasher();

        $htmlRequest = Request::create('/game/1', 'GET');

        $inertiaRequest = Request::create('/game/1', 'GET');
        $inertiaRequest->headers->set('X-Inertia', 'true');

        // ACT
        $htmlHash = $hasher->getHashFor($htmlRequest);
        $inertiaHash = $hasher->getHashFor($inertiaRequest);

        // ASSERT
        expect($htmlHash)->not->toEqual($inertiaHash);
    });

    it('produces the same hash regardless of query parameter order', function () {
        // ARRANGE
        $hasher = createHasher();
        $requestA = Request::create('/games?page=1&sort=title', 'GET');
        $requestB = Request::create('/games?sort=title&page=1', 'GET');

        // ACT
        $hashA = $hasher->getHashFor($requestA);
        $hashB = $hasher->getHashFor($requestB);

        // ASSERT
        expect($hashA)->toEqual($hashB);
    });

    it('produces the same hash regardless of nested query parameter order', function () {
        // ARRANGE
        $hasher = createHasher();
        $requestA = Request::create('/games?filter[system]=1&filter[title]=foo', 'GET');
        $requestB = Request::create('/games?filter[title]=foo&filter[system]=1', 'GET');

        // ACT
        $hashA = $hasher->getHashFor($requestA);
        $hashB = $hasher->getHashFor($requestB);

        // ASSERT
        expect($hashA)->toEqual($hashB);
    });

    it('produces different hashes for different paths', function () {
        // ARRANGE
        $hasher = createHasher();
        $requestA = Request::create('/game/1', 'GET');
        $requestB = Request::create('/game/2', 'GET');

        // ACT
        $hashA = $hasher->getHashFor($requestA);
        $hashB = $hasher->getHashFor($requestB);

        // ASSERT
        expect($hashA)->not->toEqual($hashB);
    });

    it('produces different hashes for different query strings', function () {
        // ARRANGE
        $hasher = createHasher();
        $requestA = Request::create('/games?page=1', 'GET');
        $requestB = Request::create('/games?page=2', 'GET');

        // ACT
        $hashA = $hasher->getHashFor($requestA);
        $hashB = $hasher->getHashFor($requestB);

        // ASSERT
        expect($hashA)->not->toEqual($hashB);
    });
});
