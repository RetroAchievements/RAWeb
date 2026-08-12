<?php

declare(strict_types=1);

namespace App\Community\Services;

use App\Models\ModerationWatchlistTerm;
use App\Support\Cache\CacheKey;
use Illuminate\Support\Facades\Cache;

class WatchedTermMatcher
{
    private const CACHE_TTL_SECONDS = 3600;

    /**
     * @return list<string>
     */
    public function findMatches(string $body): array
    {
        $watchedTerms = $this->watchedTerms();

        if ($watchedTerms === []) {
            return [];
        }

        $storedBody = mb_strtolower($body);

        // Markup can split a term without changing what a reader sees, so
        // the tag-free copy gets checked as well.
        $readableBody = $this->withoutMarkup($storedBody);

        $matches = array_filter(
            $watchedTerms,
            fn (string $term): bool => str_contains($storedBody, $term)
                || str_contains($readableBody, $term),
        );

        return array_values($matches);
    }

    private function withoutMarkup(string $body): string
    {
        return preg_replace('/\[\/?[a-z0-9*]+(=[^\]]*)?\]/i', '', $body) ?? $body;
    }

    /**
     * @return list<string>
     */
    private function watchedTerms(): array
    {
        return Cache::remember(
            CacheKey::ModerationWatchlistTerms,
            self::CACHE_TTL_SECONDS,
            fn (): array => ModerationWatchlistTerm::query()
                ->pluck('term')
                ->filter(fn (string $term): bool => mb_strlen($term) >= ModerationWatchlistTerm::MINIMUM_TERM_LENGTH)
                ->values()
                ->all(),
        );
    }
}
