<?php

declare(strict_types=1);

namespace App\Support\Redirector;

use GuzzleHttp\Psr7\Query;
use Spatie\MissingPageRedirector\Redirector\Redirector;
use Spatie\Url\Url;
use Symfony\Component\HttpFoundation\Request;

class LegacyRedirector implements Redirector
{
    public function getRedirectsFor(Request $request): array
    {
        $redirects = config('missing-page-redirector.redirects');

        // allow scripts in public/ to abort with 404 without triggering legacy redirects
        // removing the script will redirect eventually
        if (file_exists(public_path($request->getPathInfo()))) {
            return [];
        }

        /*
         * handle query string
         */
        if ($request->getQueryString()) {
            if (isset($redirects[$request->getPathInfo()])) {
                $queryParams = Query::parse($request->getQueryString());

                // replace query string markers
                $redirectUrl = URL::fromString($redirects[$request->getPathInfo()]);
                foreach ($redirectUrl->getAllQueryParameters() as $queryParameter => $queryParameterValue) {
                    if (str_starts_with($queryParameterValue, '{') && str_ends_with($queryParameterValue, '}')) {
                        $key = substr($queryParameterValue, 1, -1);
                        if (array_key_exists($key, $queryParams)) {
                            // found a replacement, substitute it
                            $redirectUrl = $redirectUrl->withQueryParameter($queryParameter, $queryParams[$key]);
                        } else {
                            // did not find a replacement, discard it
                            $redirectUrl = $redirectUrl->withoutQueryParameter($queryParameter);
                        }
                    }
                }

                return [$request->getPathInfo() => (string) $redirectUrl];
            }
        }

        return $redirects;
    }
}
