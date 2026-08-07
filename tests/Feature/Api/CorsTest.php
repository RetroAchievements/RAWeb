<?php

declare(strict_types=1);

use Illuminate\Testing\TestResponse;

/**
 * Preflight requests carry no credentials and are matched on path alone, before
 * routing. If a path the API answers is missing from the CORS path list, the
 * preflight falls through to the router and the browser blocks the real request.
 */
function preflight(string $uri, string $method = 'GET'): TestResponse
{
    return test()->call('OPTIONS', $uri, [], [], [], [
        'HTTP_ORIGIN' => 'https://example.com',
        'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => $method,
        'HTTP_ACCESS_CONTROL_REQUEST_HEADERS' => 'authorization',
    ]);
}

it('given a preflight for an API path, it answers with permissive CORS headers', function (string $uri, string $method) {
    // Act
    $response = preflight($uri, $method);

    // Assert
    $response->assertNoContent();
    $response->assertHeader('Access-Control-Allow-Origin', '*');
    expect($response->headers->get('Access-Control-Allow-Methods'))->toContain($method);
})->with([
    'subdomain-shaped v2' => ['/v2/users/Scott', 'GET'],
    'subdomain-shaped v1' => ['/v1/GetUserSummary', 'GET'],
    'prefixed v2' => ['/api/v2/users/Scott', 'GET'],
    'OAuth token' => ['/oauth/token', 'POST'],
]);

it('given a preflight for a non-API path, it does not answer with CORS headers', function () {
    // Act
    $response = preflight('/user/Scott');

    // Assert
    $response->assertHeaderMissing('Access-Control-Allow-Origin');
});
