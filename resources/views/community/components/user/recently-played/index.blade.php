@props([
    'processedRecentlyPlayedEntities' => [],
    'recentlyPlayedCount' => 0,
    'targetUsername' => '',
])

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
            <x-game-list-item
                :game="$processedRecentlyPlayedEntity"
                :targetUserName="$targetUsername"
                :isExpandable="true"
                :isDefaultExpanded="$loop->index === 0"
                variant="user-recently-played"
            >
                @foreach ($processedRecentlyPlayedEntity['AchievementAvatars'] as $achievementAvatar)
                    {!! $achievementAvatar !!}
                @endforeach
            </x-game-list-item>
        @endforeach
    </div>
</div>
