<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Middleware;

use App\Http\Middleware\HandleCloudflareChallenge;
use Illuminate\Http\Request;
use Tests\TestCase;

class HandleCloudflareChallengeTest extends TestCase
{
    private HandleCloudflareChallenge $middleware;

    protected function setUp(): void
    {
        parent::setUp();

        $this->middleware = new HandleCloudflareChallenge();
    }

    /**
     * Helper method to create a request with optional Cloudflare indicators.
     */
    private function createRequest(
        string $method = 'GET',
        array $postData = [],
        array $cookies = [],
        ?string $referer = null,
    ): Request {
        $request = Request::create('/', $method, $postData);

        foreach ($cookies as $name => $value) {
            $request->cookies->set($name, $value);
        }

        if ($referer) {
            $request->headers->set('referer', $referer);
        }

        return $request;
    }

    /**
     * Helper method to execute the middleware and return the modified request.
     */
    private function executeMiddleware(Request $request): Request
    {
        $this->middleware->handle($request, function ($req) {
            return response('OK');
        });

        return $request;
    }

    public function testItDoesNotModifyGetRequests(): void
    {
        // Arrange
        $request = $this->createRequest('GET');

        // Act
        $result = $this->executeMiddleware($request);

        // Assert
        $this->assertEquals('GET', $result->method());
    }

    public function testItDoesNotModifyNormalPostRequests(): void
    {
        // Arrange
        $request = $this->createRequest(
            'POST',
            postData: ['email' => 'test@example.com', 'password' => 'secret'] // !! normal form fields
        );

        // Act
        $result = $this->executeMiddleware($request);

        // Assert
        $this->assertEquals('POST', $result->method());
        $this->assertEquals('test@example.com', $result->input('email'));
    }

    public function testItConvertsCloudflareChallengePOSTWithCfClearanceCookie(): void
    {
        // Arrange
        $request = $this->createRequest(
            'POST',
            postData: [
                '0b9c535f3c859bdb441ffe35228a779a7008ef10b3550f1379eaf584f26c1f3f' => 'challenge_token_1',
                'a93a99913d421b1fb0328b3c6859a26838f5fe6e39892da291c2bd877e2f7067' => 'challenge_token_2',
            ],
            cookies: ['cf_clearance' => 'gPrvvt5qEJgX0hFcSm_lPcjt2CuYrLy6WjUbWE6H9vk']
        );

        // Act
        $result = $this->executeMiddleware($request);

        // Assert
        $this->assertEquals('GET', $result->method());
        $this->assertEmpty($result->all()); // !! POST data should be cleared
    }

    public function testItConvertsCloudflareChallengePOSTWithChallengeTokenInReferer(): void
    {
        // Arrange
        $request = $this->createRequest(
            'POST',
            postData: [
                '0b9c535f3c859bdb441ffe35128a779a7008ef10b3550f1379eaf584f26c1f3f' => 'challenge_token_1',
            ],
            referer: 'http://example.com/?__cf_chl_tk=abc123' // !!
        );

        // Act
        $result = $this->executeMiddleware($request);

        // Assert
        $this->assertEquals('GET', $result->method());
        $this->assertEmpty($result->all()); // !! POST data should be cleared
    }

    public function testItConvertsCloudflareChallengePOSTWithBothIndicators(): void
    {
        // Arrange
        $request = $this->createRequest(
            'POST',
            postData: [
                '5c750a7ba733e314d98d37cf0f6d601b494bd980f83e8a88733d8ddea7335542' => 'challenge_token',
            ],
            cookies: ['cf_clearance' => 'test_token'],
            referer: 'http://example.com/?__cf_chl_tk=xyz789'
        );

        // Act
        $result = $this->executeMiddleware($request);

        // Assert
        $this->assertEquals('GET', $result->method());
    }

    public function testItDoesNotConvertPOSTWithCfClearanceButNormalFormFields(): void
    {
        // Arrange
        $request = $this->createRequest(
            'POST',
            postData: [
                'username' => 'john_doe', // !! normal form field, not a hash
                'password' => 'secret123',
            ],
            cookies: ['cf_clearance' => 'test_token']
        );

        // Act
        $result = $this->executeMiddleware($request);

        // Assert
        $this->assertEquals('POST', $result->method()); // !! should remain a POST, not change to a GET
        $this->assertEquals('john_doe', $result->input('username'));
    }

    public function testItDoesNotConvertPOSTWithEmptyPostData(): void
    {
        // Arrange
        $request = $this->createRequest(
            'POST',
            postData: [], // !! empty
            cookies: ['cf_clearance' => 'test_token']
        );

        // Act
        $result = $this->executeMiddleware($request);

        // Assert
        $this->assertEquals('POST', $result->method());
    }

    public function testItDoesNotConvertPOSTWithMixedHashAndNormalParameters(): void
    {
        // Arrange
        $request = $this->createRequest(
            'POST',
            postData: [
                '0b9c535f3c859bdb441ffe35128a779a7008ef10b3550f1379eaf584f26c1f3f' => 'challenge_token',
                'email' => 'test@example.com', // !! normal field
            ],
            cookies: ['cf_clearance' => 'test_token']
        );

        // Act
        $result = $this->executeMiddleware($request);

        // Assert
        $this->assertEquals('POST', $result->method()); // !! should remain a POST, not change to a GET
    }

    public function testItDoesNotConvertPOSTWithShortHexString(): void
    {
        // Arrange
        $request = $this->createRequest(
            'POST',
            postData: [
                'abc123' => 'value', // !! valid hex but not 64 chars
            ],
            cookies: ['cf_clearance' => 'test_token']
        );

        // Act
        $result = $this->executeMiddleware($request);

        // Assert
        $this->assertEquals('POST', $result->method());
    }

    public function testItConvertsCloudflareChallengePOSTWithChallengeTokenInCurrentUrl(): void
    {
        // Arrange
        $request = Request::create(
            '/?__cf_chl_tk=0VeHnmvRcLVRZxIt5gubVJXp95b0iSnAfuO.Q0ZrHl4-1761781650-1.0.1.1-IrmmPDGOcvr.sYCzQD7Cb.2HcRcEX5BBi10akil0MF4', // !!
            'POST',
            [
                '0b9c535f3c859bdb441ffe35128a779a7008ef10b3550f1379eaf584f26c1f3f' => 'challenge_token',
            ]
        );

        // Act
        $result = $this->executeMiddleware($request);

        // Assert
        $this->assertEquals('GET', $result->method()); // !! should be converted from POST to GET
        $this->assertEmpty($result->post()); // !! POST data should be cleared
        $this->assertNotEmpty($result->query('__cf_chl_tk')); // !! query params should be preserved
    }

    public function testItDoesNotConvertPOSTWithoutCloudflarelndicators(): void
    {
        // Arrange
        $request = $this->createRequest(
            'POST',
            postData: [
                '0b9c535f3c859bdb441ffe35128a779a7008ef10b3550f1379eaf584f26c1f3f' => 'challenge_token',
            ]
            // !! no cf_clearance cookie or __cf_chl_tk in referer
        );

        // Act
        $result = $this->executeMiddleware($request);

        // Assert
        $this->assertEquals('POST', $result->method());
    }

    public function testItPreservesOtherCookiesWhenConverting(): void
    {
        // Arrange
        $request = $this->createRequest(
            'POST',
            postData: [
                '0b9c535f3c859bdb441ffe35128a779a7008ef10b3550f1379eaf584f26c1f3f' => 'challenge_token',
            ],
            cookies: [
                'cf_clearance' => 'test_token',
                'session' => 'user_session_123', // !!
            ]
        );

        // Act
        $result = $this->executeMiddleware($request);

        // Assert
        $this->assertEquals('GET', $result->method());
        $this->assertEquals('user_session_123', $request->cookie('session'));
    }

    public function testItPreservesQueryParametersWhenConverting(): void
    {
        // Arrange
        $request = Request::create(
            '/game/1?set=5&filter=completed', // !!
            'POST',
            [
                '0b9c535f3c859bdb441ffe35128a779a7008ef10b3550f1379eaf584f26c1f3f' => 'challenge_token',
            ]
        );
        $request->cookies->set('cf_clearance', 'test_token');

        // Act
        $result = $this->executeMiddleware($request);

        // Assert
        $this->assertEquals('GET', $result->method());
        $this->assertEmpty($result->post()); // POST data cleared
        $this->assertEquals('5', $result->query('set')); // query params preserved
        $this->assertEquals('completed', $result->query('filter')); // query params preserved
    }
}
