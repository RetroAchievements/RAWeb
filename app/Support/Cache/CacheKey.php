<?php

declare(strict_types=1);

namespace App\Support\Cache;

class CacheKey
{
    public static function buildUserCanTicketCacheKey(string $userName): string
    {
        return self::buildNormalizedUserCacheKey($userName, "canTicket");
    }

    public static function buildUserCardDataCacheKey(string $userName): string
    {
        return self::buildNormalizedUserCacheKey($userName, "card-data");
    }

    /**
     * @param string $userName the name of the user for which the cache key is being constructed
     * @param int $gameID the target game ID for the unlocks data
     * @param int $flags The type of achievement flag which should correspond to values in the `AchievementType` enum.
     *                   3 for 'OfficialCore' (default), 5 for 'Unofficial'.
     */
    public static function buildUserGameUnlocksCacheKey(string $userName, int $gameID, int $flags = 3): string
    {
        return self::buildNormalizedUserCacheKey($userName, "gameUnlocks", [$gameID, $flags]);
    }

    /**
     * @param string $userName the name of the user for which the cache key is being constructed
     * @param int $rankType The type of the rank which should correspond to values in the `RankType` enum.
     *                      1 for 'Hardcore' (default), 2 for 'Softcore', 3 for 'TruePoints'.
     */
    public static function buildUserRankCacheKey(string $userName, int $rankType = 1): string
    {
        $rankTypeParam = match ($rankType) {
            default => 'hardcore',
            2 => 'softcore',
            3 => 'truepoints',
        };

        return self::buildNormalizedUserCacheKey($userName, "rank", [$rankTypeParam]);
    }

    public static function buildUserRecentGamesCacheKey(string $userName): string
    {
        return self::buildNormalizedUserCacheKey($userName, "recentGames");
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
     * @param string $userName the name of the user for which the cache key is constructed
     * @param string $keyKind the kind of the cache key
     * @param array  $params optional parameters for the cache key
     *
     * @return string the constructed cache key
     */
    private static function buildNormalizedUserCacheKey(string $userName, string $keyKind, array $params = []): string
    {
        $normalizedUserName = strtolower($userName);

        $cacheKey = "user:$normalizedUserName:$keyKind";
        if (count($params) > 0) {
            $colonSeparatedParams = implode(':', $params);
            $cacheKey .= ":$colonSeparatedParams";
        }

        return $cacheKey;
    }
}
