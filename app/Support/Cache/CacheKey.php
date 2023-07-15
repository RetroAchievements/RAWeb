<?php

declare(strict_types=1);

namespace App\Support\Cache;

class CacheKey
{
    public static function buildUserCanTicketCacheKey(string $username): string
    {
        return self::buildNormalizedUserCacheKey($username, "can-ticket");
    }

    public static function buildUserCardDataCacheKey(string $username): string
    {
        return self::buildNormalizedUserCacheKey($username, "card-data");
    }

    /**
     * @param string $username The name of the user for which the cache key is being constructed.
     * @param int    $gameID The target game ID for the unlocks data.
     * @param bool   $isOfficial Whether this is for official or unofficial achievements.
     */
    public static function buildUserGameUnlocksCacheKey(string $username, int $gameID, bool $isOfficial = true): string
    {
        $achievementKind = $isOfficial ? 'official' : 'unofficial';

        return self::buildNormalizedUserCacheKey($username, "game-unlocks", [$gameID, $achievementKind]);
    }

    /**
     * @param string $username The name of the user for which the cache key is being constructed.
     * @param int $rankType The type of the rank which should correspond to values in the `RankType` enum.
     *                      1 for 'Hardcore' (default), 2 for 'Softcore', 3 for 'TruePoints'.
     */
    public static function buildUserRankCacheKey(string $username, int $rankType = 1): string
    {
        $rankTypeParam = match ($rankType) {
            default => 'hardcore',
            2 => 'softcore',
            3 => 'truepoints',
        };

        return self::buildNormalizedUserCacheKey($username, "rank", [$rankTypeParam]);
    }

    public static function buildUserRecentGamesCacheKey(string $username): string
    {
        return self::buildNormalizedUserCacheKey($username, "recent-games");
    }

    /**
     * Constructs a normalized user cache key.
     *
     * This function creates a cache key for a specific user with a specified kind and optional parameters.
     * The username is case-corrected to ensure superfluous cache entries for the same user are not created.
     * Cache keys follow the format: "user:{lowercase username}:{key kind}:{optional colon-separated parameters}".
     *
     * Use the function like this:
     * ```php
     * $cacheKey = CacheKey::constructNormalizedUserCacheKey("UserName", "gameUnlocks", [$gameID, $flags]);
     * ```
     * This generates a cache key like: "user:username:gameUnlocks:{gameID}:{flags}".
     *
     * @param string $username The name of the user for which the cache key is constructed.
     * @param string $keyKind The kind of the cache key.
     * @param array  $params Optional parameters for the cache key.
     *
     * @return string The constructed cache key.
     */
    private static function buildNormalizedUserCacheKey(string $username, string $keyKind, array $params = []): string
    {
        $normalizedUserName = strtolower($username);

        $cacheKey = "user:$normalizedUserName:$keyKind";
        if (count($params) > 0) {
            $colonSeparatedParams = implode(':', $params);
            $cacheKey .= ":$colonSeparatedParams";
        }

        return $cacheKey;
    }
}
