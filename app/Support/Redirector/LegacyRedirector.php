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
        if (isset($redirects[$request->getPathInfo()])) {
            $queryParams = Query::parse($request->getQueryString() ?? '');
            $redirectUrl = $redirects[$request->getPathInfo()];

            // handle single url mapping to multiple paths based on parameters
            if (is_array($redirectUrl)) {
                foreach ($redirectUrl as $param => $url) {
                    if (empty($param) || array_key_exists($param, $queryParams)) {
                        $redirectUrl = $url;
                        break;
                    }
                }
            } elseif (empty($queryParams)) {
                // no query params to replace. no multiple path mapping.
                // just let default behavior handle it.
                $parsedRedirectUrl = Url::fromString($redirectUrl);

                return [$request->getPathInfo() => (string) $parsedRedirectUrl];
            }

            // forward route and query string values
            foreach ($queryParams as $key => $value) {
                $redirectUrl = str_replace("{{$key}}", $value, $redirectUrl);
            }

            // remove remaining, unused markers
            $parsedRedirectUrl = Url::fromString($redirectUrl);
            foreach ($parsedRedirectUrl->getAllQueryParameters() as $queryParameter => $queryParameterValue) {
                if (str_starts_with($queryParameterValue, '{')) {
                    $parsedRedirectUrl = $parsedRedirectUrl->withoutQueryParameter($queryParameter);
                }
            }

            return [$request->getPathInfo() => (string) $parsedRedirectUrl];
        }

        return $redirects;
    }
}
