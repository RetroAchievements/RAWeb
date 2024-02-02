@props([
    'processedRecentlyPlayedEntities' => [],
    'recentlyPlayedCount' => 0,
    'targetUsername' => '',
])

<div>
    <h2 class="text-h4">
        @if ($recentlyPlayedCount === 1)
            Last game played
        @else
            Last {{ localized_number($recentlyPlayedCount) }} Games Played
        @endif
    </h2>

    <div class="flex flex-col gap-y-1">
        @foreach ($processedRecentlyPlayedEntities as $processedRecentlyPlayedEntity)
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
        @endforeach
    </div>
</div>
