<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RedirectControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testRedirectWithValidHttpUrl(): void
    {
        // Act
        $response = $this->get('/redirect?url=https://example.com');

        // Assert
        $response->assertInertia(fn (Assert $page) => $page
            ->component('redirect')
            ->where('url', 'https://example.com')
        );
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
        $response->assertInertia(fn (Assert $page) => $page
            ->component('redirect')
            ->where('url', 'https://example.com')
        );
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
        $response->assertInertia(fn (Assert $page) => $page
            ->component('redirect')
            ->where('url', 'https://example.com/path')
        );
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
        $response->assertInertia(fn (Assert $page) => $page
            ->component('redirect')
            ->where('url', 'http://example.com')
        );
    }

    public function testRedirectWithXssAttempt(): void
    {
        // Act
        $response = $this->get('/redirect?url=https://example.com/<script>alert(1)</script>');

        // Assert
        $response->assertInertia(fn (Assert $page) => $page
            ->component('redirect')
            ->where('url', 'https://example.com/<script>alert(1)</script>')
        );

        $response->assertDontSee('<script>alert(1)</script>', false);

        $content = $response->getContent();
        $this->assertStringContainsString('https:\/\/example.com\/&lt;script&gt;alert(1)&lt;\/script&gt;', $content);
    }

    public function testRedirectBlocksDangerousProtocols(): void
    {
        $response = $this->get('/redirect?url=javascript:alert(1)');
        $response->assertRedirect('/');

        $response2 = $this->get('/redirect?url=data:text/html,<script>alert(1)</script>');
        $response2->assertRedirect('/');

        $response3 = $this->get('/redirect?url=file:///etc/passwd');
        $response3->assertRedirect('/');

        $response4 = $this->get('/redirect?url=vbscript:alert(1)');
        $response4->assertRedirect('/');

        $response5 = $this->get('/redirect?url=about:blank');
        $response5->assertRedirect('/');

        $response6 = $this->get('/redirect?url=blob:https://example.com/123');
        $response6->assertRedirect('/');
    }

    public function testRedirectBlocksEncodedDangerousProtocols(): void
    {
        $response = $this->get('/redirect?url=%6A%61%76%61%73%63%72%69%70%74:alert(1)');
        $response->assertRedirect('/');

        $response2 = $this->get('/redirect?url=java%73cript:alert(1)');
        $response2->assertRedirect('/');

        $response3 = $this->get('/redirect?url=%256A%2561%2576%2561%2573%2563%2572%2569%2570%2574:alert(1)');
        $response3->assertRedirect('/');

        $response4 = $this->get('/redirect?url=&#106;&#97;&#118;&#97;&#115;&#99;&#114;&#105;&#112;&#116;:alert(1)');
        $response4->assertRedirect('/');
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
        $response->assertInertia(fn (Assert $page) => $page
            ->component('redirect')
            ->where('url', 'https://github-evil.com/malicious')
        );
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
        $response = $this->get('/redirect?url=https://malicious.github.com/evil');

        // Assert
        $response->assertInertia(fn (Assert $page) => $page
            ->component('redirect')
            ->where('url', 'https://malicious.github.com/evil')
        );
    }

    public function testRedirectAllowsGithubGist(): void
    {
        // Act
        $response = $this->get('/redirect?url=https://gist.github.com/user/abc123');

        // Assert
        $response->assertRedirect('https://gist.github.com/user/abc123');
    }

    public function testRedirectAllowsRawGithubusercontent(): void
    {
        // Act
        $response = $this->get('/redirect?url=https://raw.githubusercontent.com/repo/file.txt');

        // Assert
        $response->assertRedirect('https://raw.githubusercontent.com/repo/file.txt');
    }

    public function testRedirectAllowsGithubUserImages(): void
    {
        // Act
        $response = $this->get('/redirect?url=https://user-images.githubusercontent.com/123/image.png');

        // Assert
        $response->assertRedirect('https://user-images.githubusercontent.com/123/image.png');
    }

    public function testRedirectHandlesCaseInsensitiveDomains(): void
    {
        // Act
        $response = $this->get('/redirect?url=https://GitHub.com/retroachievements');

        // Assert
        $response->assertRedirect('https://GitHub.com/retroachievements');
    }

    public function testRedirectEncodesAmpersandFollowedBySpace(): void
    {
        // Arrange
        $url = 'https://www.google.com/search?q=site:www.gamefaqs.com+Mario+&+Sonic';

        // Act
        $response = $this->get('/redirect?url=' . urlencode($url));

        // Assert
        $response->assertInertia(fn (Assert $page) => $page
            ->component('redirect')
            ->where('url', 'https://www.google.com/search?q=site:www.gamefaqs.com+Mario+%26+Sonic')
        );
    }

    public function testRedirectPreservesLegitimateQueryParameterSeparators(): void
    {
        // Arrange
        $url = 'https://example.com/search?q=test&page=2';

        // Act
        $response = $this->get('/redirect?url=' . urlencode($url));

        // Assert
        $response->assertInertia(fn (Assert $page) => $page
            ->component('redirect')
            ->where('url', 'https://example.com/search?q=test&page=2')
        );
    }
}
