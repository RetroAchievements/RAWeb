<?php

declare(strict_types=1);

namespace App\Support\Cache;

class CacheKey
{
    public static function buildGameCardDataCacheKey(int $gameId): string
    {
        return self::buildNormalizedCacheKey("game", $gameId, "card-data");
    }

    public static function buildUserCanTicketCacheKey(string $username): string
    {
        return self::buildNormalizedUserCacheKey($username, "can-ticket");
    }

    public static function buildUserCardDataCacheKey(string $username): string
    {
        return self::buildNormalizedUserCacheKey($username, "card-data");
    }

    /**
     * @param string $username the name of the user for which the cache key is being constructed
     * @param int    $gameID the target game ID for the unlocks data
     * @param bool   $isOfficial whether this is for official or unofficial achievements
     */
    public static function buildUserGameUnlocksCacheKey(string $username, int $gameID, bool $isOfficial = true): string
    {
        $achievementKind = $isOfficial ? 'official' : 'unofficial';

        return self::buildNormalizedUserCacheKey($username, "game-unlocks", [$gameID, $achievementKind]);
    }

    /**
     * @param string $username the name of the user for which the cache key is being constructed
     * @param int $rankType the type of the rank which should correspond to values in the `RankType` enum.
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
     * Constructs a normalized cache key.
     *
     * This function creates a cache key for a specific entity with a given kind and optional parameters.
     * Cache keys follow the format: "entityKind:12345:keyKind:{param1}:{param2}".
     *
     * @param string     $entityKind the kind of entity to cache data for (eg: "user", "game", "achievement")
     * @param string|int $identifier the unique id associated with this entity (eg: 12345, "Scott")
     * @param string     $keyKind the specific kind of data being cached (eg: "card-data")
     * @param array      $params optional parameters for the cache key
     *
     * @return string the constructed cache key
     */
    private static function buildNormalizedCacheKey(
        string $entityKind,
        string|int $identifier,
        string $keyKind,
        array $params = []
    ): string {
        $cacheKey = "$entityKind:$identifier:$keyKind";
        if (count($params) > 0) {
            $colonSeparatedParams = implode(':', $params);
            $cacheKey .= ":$colonSeparatedParams";
        }

        return $cacheKey;
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
     * @param string $username the name of the user for which the cache key is constructed
     * @param string $keyKind the kind of the cache key
     * @param array  $params optional parameters for the cache key
     *
     * @return string the constructed cache key
     */
    private static function buildNormalizedUserCacheKey(string $username, string $keyKind, array $params = []): string
    {
        $normalizedUsername = strtolower($username);

        return self::buildNormalizedCacheKey("user", $normalizedUsername, $keyKind, $params);
    }
}
