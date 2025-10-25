<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HandleCloudflareChallenge
{
    /**
     * Cloudflare's managed challenges submit as POST requests with challenge tokens.
     * Laravel routes expect GET requests for page views, causing users to see 405 errors.
     * This middleware detects Cloudflare challenge completions and converts them to GET requests.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('POST') && $this->isCloudflareChallenge($request)) {
            // Convert the POST request to GET and clear POST data.
            $request->setMethod('GET');
            $request->request->replace([]);
        }

        return $next($request);
    }

    /**
     * Determine if this is a Cloudflare managed challenge completion.
     */
    private function isCloudflareChallenge(Request $request): bool
    {
        // Quickly look for Cloudflare indicators in cookies/headers first.
        // This avoids processing POST data for 99.9% of normal requests.
        $hasCfClearance = $request->hasCookie('cf_clearance');
        $hasCfChallengeToken = str_contains($request->header('referer', ''), '__cf_chl_tk=');

        if (!$hasCfClearance && !$hasCfChallengeToken) {
            return false;
        }

        // Only check POST data structure if Cloudflare indicators are present.
        $postData = $request->all();

        if (empty($postData)) {
            return false;
        }

        // Cloudflare challenge completions have POST parameters with SHA-256 hashes as keys.
        // Normal forms have recognizable field names (email, password, _token, etc).
        // Fail fast: exit on the first non-hash key to avoid looping through entire form submissions.
        foreach ($postData as $key => $value) {
            if (!is_string($key) || !$this->is64CharHexHash($key) || !is_string($value)) {
                return false; // this request is not a Cloudflare challenge, bail immediately.
            }
        }

        return true;
    }

    private function is64CharHexHash(string $str): bool
    {
        return strlen($str) === 64 && ctype_xdigit($str);
    }
}
