@props([
    'achievement' => [],
    'totalPlayerCount' => 0,
    'progressionTypeValue' => 'progression', // `AchievementType`
    'winConditionTypeValue' => 'win_condition', // `AchievementType`
])

<?php
use Illuminate\Support\Carbon;

$isUnlocked = isset($achievement['DateEarnedHardcore']) || isset($achievement['DateEarned']);
$isUnlockedOnHardcore = isset($achievement['DateEarnedHardcore']);

$achBadgeName = $achievement['BadgeName'];
if (!$isUnlocked) {
    $achBadgeName .= "_lock";
}

$imgClass = $isUnlockedOnHardcore ? 'goldimagebig' : 'badgeimg';
$imgClass .= ' w-[54px] h-[54px] sm:w-16 sm:h-16';

$renderedAchievementAvatar = achievementAvatar(
    $achievement,
    label: false,
    icon: $achBadgeName,
    iconSize: 64,
    iconClass: $imgClass,
    tooltip: false
);

$renderedAchievementTitle = renderAchievementTitle($achievement['Title']);

$unlockDate = '';
if (isset($achievement['DateEarned'])) {
    $unlockDate = Carbon::parse($achievement['DateEarned'])->format('F j Y, g:ia');
}
if (isset($achievement['DateEarnedHardcore'])) {
    $unlockDate = Carbon::parse($achievement['DateEarnedHardcore'])->format('F j Y, g:ia');
}
?>

<li class="flex gap-x-3 odd:bg-[rgba(50,50,50,0.4)] light:odd:bg-neutral-200  px-2 py-3 md:py-1 w-full {{ $isUnlocked ? 'unlocked-row' : '' }}">
    <div class="flex flex-col gap-y-1">
        {!! $renderedAchievementAvatar !!}
    </div>

    <div class="grid w-full gap-y-1.5 gap-x-5 leading-4 md:grid-cols-6 mt-1">
        <div class="md:col-span-4">
            <div class="flex justify-between gap-x-2 mb-0.5">
                <div>
                    <a class="inline mr-1" href="{{ route('achievement.show', $achievement['ID']) }}">
                        {!! $renderedAchievementTitle !!}
                    </a>

                    @if ($achievement['Points'] > 0 || $achievement['TrueRatio'] > 0)
                        <p class="inline text-xs whitespace-nowrap">
                            <span>({{ $achievement['Points'] }})</span>
                            <x-points-weighted-container>
                                ({{ localized_number($achievement['TrueRatio']) }})
                            </x-points-weighted-container>
                        </p>
                    @endif
                </div>

                @hasfeature("beat")
                    @if ($achievement['type'])
                        <div class="flex items-center gap-x-1 md:hidden -mt-1.5">
                            <div class="-mt-1.5">
                                <x-game.achievements-list.type-indicator
                                    :achievementType="$achievement['type']"
                                    :progressionTypeValue="$progressionTypeValue"
                                    :winConditionTypeValue="$winConditionTypeValue"
                                />
                            </div>
                        </div>
                    @endif
                @endhasfeature
            </div>

            <p class="leading-4">{{ $achievement['Description'] }}</p>

            @if ($unlockDate)
                <p class="hidden md:block mt-1.5 text-[0.6rem] text-neutral-400/70">Unlocked {{ $unlockDate }}</p>
            @endif
        </div>

        <div class="md:col-span-2 md:flex md:flex-col-reverse md:justify-end md:pt-1 md:gap-y-1">
            @hasfeature("beat")
                @if ($achievement['type'])
                    <div class="hidden md:flex items-center justify-end gap-x-1">
                        <x-game.achievements-list.type-indicator
                            :achievementType="$achievement['type']"
                            :progressionTypeValue="$progressionTypeValue"
                            :winConditionTypeValue="$winConditionTypeValue"
                        />
                    </div>
                @endif
            @endhasfeature

            <x-game.achievements-list.list-item-global-progress
                :achievement="$achievement"
                :totalPlayerCount="$totalPlayerCount"
            />
        </div>

        @if ($unlockDate)
            <p class="text-[0.6rem] text-neutral-400/70 md:hidden">Unlocked {{ $unlockDate }}</p>
        @endif
    </div>
</li>