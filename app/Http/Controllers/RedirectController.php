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
        'api-docs.retroachievements.org',
        'docs.retroachievements.org',
        'media.retroachievements.org',
        'news.retroachievements.org',
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

    public function redirect(Request $request): View|RedirectResponse
    {
        $url = $request->get('url');

        if (!$url) {
            return back()->getRequest()->getPathInfo() === '/redirect'
                ? redirect('/')
                : back();
        }

        /**
         * forward allowed domains
         */
        $fullDomain = parse_url($url, PHP_URL_HOST);
        if (is_string($fullDomain)) {
            $mainDomain = implode('.', array_slice(explode('.', $fullDomain), -2));
            if (in_array($mainDomain, $this->allowedDomains) || in_array($fullDomain, $this->allowedDomains)) {
                return redirect($url);
            }
        }

        return view('pages.redirect')
            ->with('url', $url);
    }
}
