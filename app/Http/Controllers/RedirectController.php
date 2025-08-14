<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RedirectController extends Controller
{
    private array $allowedDomains = [
        // First-party
        'retroachievements.org',

        // Third-party
        'backloggd.com',
        'backloggery.com',
        'completionist.me',
        'exophase.com',
        'discord.com',
        'github.com',
        'howlongtobeat.com',
        'infinitebacklog.net',
        'infinitebacklog.nl',
        'psnprofiles.com',
        'steamcommunity.com',
        'twitch.tv',
        'twitter.com',
        'youtube.com',
    ];

    private array $allowedSubdomains = [
        'gist.github.com',
        'raw.githubusercontent.com',
        'user-images.githubusercontent.com',
    ];

    public function redirect(Request $request): View|RedirectResponse
    {
        $url = $request->get('url');

        if (!$url) {
            return $this->redirectBack();
        }

        // If the URL doesn't have a protocol, add https:// only if it looks like a domain.
        // This handles cases like "example.com" or "//example.com".
        if (!preg_match('/^[a-zA-Z][a-zA-Z0-9+.-]*:/', $url)) {
            // Handle protocol-relative URLs (starting with //).
            if (str_starts_with($url, '//')) {
                $url = 'https:' . $url;
            } elseif (preg_match('/^([a-z0-9-]+\.)+[a-z]{2,}(\/.*)?$/i', $url)) {
                // Only add https:// if it looks like a domain (has dots and TLD).
                $url = 'https://' . $url;
            }
        }

        // Validate that it's a proper URL with either an http or https protocol.
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return $this->redirectBack();
        }

        // Only allow http and https protocols to prevent javascript:, data:, and other dangerous protocols.
        $scheme = parse_url($url, PHP_URL_SCHEME);
        if (!in_array($scheme, ['http', 'https'])) {
            return $this->redirectBack();
        }

        /**
         * forward allowed domains
         */
        $fullDomain = parse_url($url, PHP_URL_HOST);
        if (is_string($fullDomain)) {
            // Normalize to lowercase for comparison.
            $fullDomainLower = strtolower($fullDomain);

            // Check for explicitly allowed subdomains first.
            foreach ($this->allowedSubdomains as $allowedSubdomain) {
                if ($fullDomainLower === strtolower($allowedSubdomain)) {
                    return redirect($url);
                }
            }

            // Check each allowed domain for an exact match or first-party subdomain.
            foreach ($this->allowedDomains as $allowedDomain) {
                $allowedDomainLower = strtolower($allowedDomain);

                if ($fullDomainLower === $allowedDomainLower) {
                    return redirect($url);
                }

                // For third-party domains, we don't allow arbitrary subdomains to prevent abuse.
                // Only allow www. subdomain for better UX.
                if ($fullDomainLower === 'www.' . $allowedDomainLower) {
                    return redirect($url);
                }

                // For first-party retroachievements.org, allow any subdomain.
                if (
                    $allowedDomainLower === 'retroachievements.org'
                    && str_ends_with($fullDomainLower, '.' . $allowedDomainLower)
                ) {
                    return redirect($url);
                }
            }
        }

        return view('pages.redirect')
            ->with('url', $url);
    }

    private function redirectBack(): RedirectResponse
    {
        return back()->getRequest()->getPathInfo() === '/redirect'
            ? redirect('/')
            : back();
    }
}
