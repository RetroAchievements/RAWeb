@props([
    'processedRecentlyPlayedEntities' => [],
    'recentlyPlayedCount' => 0,
    'targetUsername' => '',
])

<script>
/** @type {Record<number, boolean>} */
window.preloadedGameIds = {};

/**
 * @param {HTMLDivElement} element
 * @param {number} gameId
 */
function preloadAchievementBadges(element, gameId) {
    // Only try to preload badges a single time per game.
    if (window.preloadedGameIds[gameId]) {
        return;
    }

    const allChildImgEls = element.querySelectorAll('img');
    for (const imgEl of allChildImgEls) {
        if (imgEl.src) {
            const preload = new Image();
            preload.src = imgEl.src;
        }
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
            <div
                @if ($loop->index !== 0 && !empty($processedRecentlyPlayedEntity['AchievementAvatars']))
                    onmouseenter="preloadAchievementBadges(this, {{ $processedRecentlyPlayedEntity['GameID'] }})"
                @endif
            >
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
