<?php

declare(strict_types=1);

namespace App\Http\ResponseCache;

use App\Actions\GetUserDeviceKindAction;
use Illuminate\Http\Request;
use Spatie\ResponseCache\CacheProfiles\CacheProfile;
use Spatie\ResponseCache\Hasher\RequestHasher;

class InertiaAwareHasher implements RequestHasher
{
    public function __construct(
        protected CacheProfile $cacheProfile,
        protected GetUserDeviceKindAction $getDeviceKind,
    ) {
    }

    public function getHashFor(Request $request): string
    {
        $cacheNameSuffix = $this->getCacheNameSuffix($request);

        // Inertia client-side navigations send X-Inertia and expect JSON,
        // while initial page loads expect full HTML. Without this segment
        // the two response formats would share a single cache entry.
        $format = $request->headers->has('X-Inertia') ? 'inertia' : 'html';

        // Many pages render different layouts for mobile vs desktop via
        // `ziggy.device`. Without this segment both device types would
        // share a single cache entry, serving wrong layouts.
        $deviceKind = $this->getDeviceKind->execute();

        return 'responsecache-' . hash('xxh128', implode('-', [
            $request->getHost(),
            $this->getNormalizedRequestUri($request),
            $request->getMethod(),
            $format,
            $deviceKind,
            $cacheNameSuffix,
        ]));
    }

    protected function getNormalizedRequestUri(Request $request): string
    {
        // Sort query params (recursively for nested filter[] style params)
        // so reordered parameters resolve to the same cache key.
        $queryString = '';
        if ($params = $request->getQueryString()) {
            parse_str($params, $parsed);
            $this->ksortRecursive($parsed);

            $queryString = '?' . http_build_query($parsed);
        }

        return $request->getBaseUrl() . $request->getPathInfo() . $queryString;
    }

    protected function ksortRecursive(array &$array): void
    {
        ksort($array);
        foreach ($array as &$value) {
            if (is_array($value)) {
                $this->ksortRecursive($value);
            }
        }
    }

    protected function getCacheNameSuffix(Request $request): string
    {
        if ($request->attributes->has('responsecache.cacheNameSuffix')) {
            return $request->attributes->get('responsecache.cacheNameSuffix');
        }

        return $this->cacheProfile->useCacheNameSuffix($request);
    }
}
