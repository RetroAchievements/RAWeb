<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RedirectControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testRedirectWithValidHttpUrl(): void
    {
        // Act
        $response = $this->get('/redirect?url=https://example.com');

        // Assert
        $response->assertStatus(200);
        $response->assertViewIs('pages.redirect');
        $response->assertViewHas('url', 'https://example.com');
    }

    public function testRedirectWithAllowedDomain(): void
    {
        // Act
        $response = $this->get('/redirect?url=https://github.com/retroachievements');

        // Assert
        $response->assertRedirect('https://github.com/retroachievements');
    }

    public function testRedirectWithJavascriptProtocol(): void
    {
        // Act
        $response = $this->get('/redirect?url=javascript:alert(1)');

        // Assert
        $response->assertRedirect('/');
    }

    public function testRedirectWithDataUri(): void
    {
        // Act
        $response = $this->get('/redirect?url=data:text/html,<script>alert(1)</script>');

        // Assert
        $response->assertRedirect('/');
    }

    public function testRedirectWithInvalidUrl(): void
    {
        // Act
        $response = $this->get('/redirect?url=not-a-valid-url');

        // Assert
        $response->assertRedirect('/');
    }

    public function testRedirectWithNoUrl(): void
    {
        // Act
        $response = $this->get('/redirect');

        // Assert
        $response->assertRedirect('/');
    }

    public function testRedirectWithUrlMissingProtocol(): void
    {
        // Act
        $response = $this->get('/redirect?url=example.com');

        // Assert
        $response->assertStatus(200);
        $response->assertViewIs('pages.redirect');
        $response->assertViewHas('url', 'https://example.com');
    }

    public function testRedirectWithAllowedDomainMissingProtocol(): void
    {
        // Act
        $response = $this->get('/redirect?url=github.com/retroachievements');

        // Assert
        $response->assertRedirect('https://github.com/retroachievements');
    }

    public function testRedirectWithProtocolRelativeUrl(): void
    {
        // Act
        $response = $this->get('/redirect?url=//example.com/path');

        // Assert
        $response->assertStatus(200);
        $response->assertViewIs('pages.redirect');
        $response->assertViewHas('url', 'https://example.com/path');
    }

    public function testRedirectWithProtocolRelativeAllowedDomain(): void
    {
        // Act
        $response = $this->get('/redirect?url=//github.com/retroachievements');

        // Assert
        $response->assertRedirect('https://github.com/retroachievements');
    }

    public function testRedirectWithHttpUrlStaysHttp(): void
    {
        // Act
        $response = $this->get('/redirect?url=http://example.com');

        // Assert
        $response->assertStatus(200);
        $response->assertViewIs('pages.redirect');
        $response->assertViewHas('url', 'http://example.com');
    }

    public function testRedirectWithXssAttempt(): void
    {
        // Act
        $response = $this->get('/redirect?url=https://example.com/<script>alert(1)</script>');

        // Assert
        $response->assertStatus(200);
        $response->assertViewIs('pages.redirect');

        // ... the URL should be properly escaped in the view ...
        $response->assertViewHas('url', 'https://example.com/<script>alert(1)</script>');

        // ... the script tag should be escaped in the actual output ...
        $response->assertDontSee('<script>alert(1)</script>', false);
        $response->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
    }

    public function testRedirectStillBlocksDangerousProtocols(): void
    {
        $response = $this->get('/redirect?url=javascript:alert(1)');
        $response->assertRedirect('/');

        $response2 = $this->get('/redirect?url=data:text/html,<script>alert(1)</script>');
        $response2->assertRedirect('/');

        $response3 = $this->get('/redirect?url=file:///etc/passwd');
        $response3->assertRedirect('/');
    }

    public function testRedirectBlocksMaliciousSubdomains(): void
    {
        // Act
        $response = $this->get('/redirect?url=https://malicious.github.com/evil');

        // Assert
        $response->assertStatus(200);
        $response->assertViewIs('pages.redirect');
        $response->assertViewHas('url', 'https://malicious.github.com/evil');
    }

    public function testRedirectAllowsLegitimateSubdomains(): void
    {
        // Act
        $response = $this->get('/redirect?url=https://www.youtube.com/watch?v=123');

        // Assert
        $response->assertRedirect('https://www.youtube.com/watch?v=123');
    }

    public function testRedirectBlocksSimilarDomains(): void
    {
        // Act
        $response = $this->get('/redirect?url=https://github-evil.com/malicious');

        // Assert
        $response->assertStatus(200);
        $response->assertViewIs('pages.redirect');
        $response->assertViewHas('url', 'https://github-evil.com/malicious');
    }

    public function testRedirectHandlesExactDomainMatch(): void
    {
        // Act
        $response = $this->get('/redirect?url=https://retroachievements.org');

        // Assert
        $response->assertRedirect('https://retroachievements.org');
    }

    public function testRedirectAllowsRetroAchievementsSubdomains(): void
    {
        // Act
        $response = $this->get('/redirect?url=https://api.retroachievements.org/docs');

        // Assert
        $response->assertRedirect('https://api.retroachievements.org/docs');
    }

    public function testRedirectBlocksArbitraryGithubSubdomains(): void
    {
        // Act
        $response = $this->get('/redirect?url=https://gist.github.com/malicious');

        // Assert
        $response->assertStatus(200);
        $response->assertViewIs('pages.redirect');
        $response->assertViewHas('url', 'https://gist.github.com/malicious');
    }

    public function testRedirectHandlesCaseInsensitiveDomains(): void
    {
        // Act
        $response = $this->get('/redirect?url=https://GitHub.com/retroachievements');

        // Assert
        $response->assertRedirect('https://GitHub.com/retroachievements');
    }
}
