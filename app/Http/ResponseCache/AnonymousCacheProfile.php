<?php

declare(strict_types=1);

namespace App\Http\ResponseCache;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\ResponseCache\CacheProfiles\BaseCacheProfile;
use Symfony\Component\HttpFoundation\Response;

class AnonymousCacheProfile extends BaseCacheProfile
{
    public function shouldCacheRequest(Request $request): bool
    {
        if ($this->isRunningInConsole()) {
            return false;
        }

        if (!$request->isMethod('get')) {
            return false;
        }

        // Authenticated users always get fresh responses.
        if (Auth::check()) {
            return false;
        }

        // Inertia partial reloads are user-specific and must not be cached.
        if ($request->headers->has('X-Inertia-Partial-Component')) {
            return false;
        }

        return true;
    }

    public function shouldCacheResponse(Response $response): bool
    {
        $contentType = $response->headers->get('Content-Type', '');

        return
            $response->isSuccessful() && (
                str_starts_with($contentType, 'text/html')
                || str_contains($contentType, '/json')
                || str_contains($contentType, '+json')
            )
        ;
    }

    /**
     * The CacheResponse middleware calls the hasher (and therefore this method)
     * to look up a cached entry before consulting shouldCacheRequest(). Without
     * a distinct suffix, authenticated users would match the shared anonymous
     * cache key and get served stale anonymous responses.
     */
    public function useCacheNameSuffix(Request $request): string
    {
        if (Auth::check()) {
            return (string) Auth::id();
        }

        return 'anonymous';
    }
}
