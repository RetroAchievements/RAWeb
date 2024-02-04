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
        'news.retroachievements.org',
        'docs.retroachievements.org',
    ];

    public function redirect(Request $request): View|RedirectResponse
    {
        $url = $request->get('url');

        if (!$url) {
            return back();
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

        return view('redirect')
            ->with('url', $url);
    }

    // TODO move wiki-edit-redirect.php over here
    // public function wiki(): void
    // {
    //     if (empty($_GET['page'])) {
    //         return;
    //     }
    //     $page = pathinfo($_GET['page'])['filename'];
    //     if (empty($page)) {
    //         return;
    //     }
    //     // path rewrites
    //     switch ($page) {
    //         case 'index':
    //             $page = 'Home';
    //             break;
    //     }
    //     header("location: https://github.com/retroachievements/docs/wiki/$page/_edit");
    // }
}
