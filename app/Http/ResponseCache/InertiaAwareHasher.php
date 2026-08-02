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

        // Inertia client-side navigations send X-Inertia and expect JSON.
        // Initial page loads expect full HTML. This segment keeps the two
        // formats in separate cache entries.
        //
        // Partial reloads (deferred props, `only`/`except` visits) also send
        // X-Inertia, but they expect only a subset of the props. The cache
        // never stores them. They must also never read a full page entry,
        // because that entry still lists its deferred props. The client reads
        // the list, requests the props again, and the loop does not stop.
        // This segment gives partial reloads a key that no entry uses.
        //
        // The key omits the requested prop names on purpose. Nothing writes
        // an entry under this segment, so a finer key adds no value.
        $format = match (true) {
            $request->headers->has('X-Inertia-Partial-Component') => 'inertia-partial',
            $request->headers->has('X-Inertia') => 'inertia',
            default => 'html',
        };

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

            $ignored = config('responsecache.ignored_query_parameters', []);
            if (!empty($ignored)) {
                $parsed = array_diff_key($parsed, array_flip($ignored));
            }

            $this->ksortRecursive($parsed);

            $queryString = !empty($parsed) ? '?' . http_build_query($parsed) : '';
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
