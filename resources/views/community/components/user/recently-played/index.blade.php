@props([
    'processedRecentlyPlayedEntities' => [],
    'recentlyPlayedCount' => 0,
    'targetUsername' => '',
])

<script>
/** @type {Record<number, boolean>} */
window.preloadedGameIds = {};

/**
 * @param {Record<number, string>} badgeUrls
 * @param {number} gameId
 */
function preloadAchievementBadges(badgeUrls, gameId) {
    // Only try to preload badges a single time per game.
    if (window.preloadedGameIds[gameId]) {
        return;
    }

    for (const badgeUrl of Object.values(badgeUrls)) {
        const img = new Image();
        img.src = badgeUrl;

        window.preloadedGameIds[gameId] = true;
    }
}
</script>

<div>
    <h4>
        @if ($recentlyPlayedCount === 1)
            Last game played
        @else
            Last {{ localized_number($recentlyPlayedCount) }} games played
        @endif
    </h4>

    <div class="flex flex-col gap-y-1">
        @foreach ($processedRecentlyPlayedEntities as $processedRecentlyPlayedEntity)
            <div onmouseenter="preloadAchievementBadges({{ json_encode($processedRecentlyPlayedEntity['AchievementBadgeURLs']) }}, {{ $processedRecentlyPlayedEntity['GameID'] }})">
                <x-game-list-item
                    :game="$processedRecentlyPlayedEntity"
                    :targetUserName="$targetUsername"
                    :isExpandable="true"
                    :isDefaultExpanded="$loop->index === 0 && !empty($processedRecentlyPlayedEntity['AchievementAvatars'])"
                    variant="user-recently-played"
                >
                    @foreach ($processedRecentlyPlayedEntity['AchievementAvatars'] as $achievementAvatar)
                        {!! $achievementAvatar !!}
                    @endforeach
                </x-game-list-item>
            </div>
        @endforeach
    </div>
</div>
