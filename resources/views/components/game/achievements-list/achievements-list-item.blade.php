<?php

use Illuminate\Support\Carbon;
?>

@props([
    'achievement' => [],
    'beatenGameCreditDialogContext' => 's:|h:',
    'isCreditDialogEnabled' => true,
    'isUnlocked' => false,
    'isUnlockedHardcore' => false,
    'showAuthorName' => false,
    'totalPlayerCount' => 0,
    'useMinimalLayout' => false,
])

<?php
$isUnlocked = $isUnlocked || isset($achievement['DateEarnedHardcore']) || isset($achievement['DateEarned']);
$isUnlockedOnHardcore = $isUnlockedHardcore || isset($achievement['DateEarnedHardcore']);

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

$unlockDate = '';
if (isset($achievement['DateEarned'])) {
    $unlockDate = Carbon::parse($achievement['DateEarned'])->format('F j Y, g:ia');
}
if (isset($achievement['DateEarnedHardcore'])) {
    $unlockDate = Carbon::parse($achievement['DateEarnedHardcore'])->format('F j Y, g:ia');
}
?>

<li class="flex gap-x-3 odd:bg-[rgba(50,50,50,0.4)] light:odd:bg-neutral-200  px-2 py-3 md:py-1 w-full {{ $isUnlocked ? 'unlocked-row' : '' }} {{ $achievement['type'] === 'missable' ? 'missable-row' : '' }}">
    <div class="flex flex-col gap-y-1">
        {!! $renderedAchievementAvatar !!}
    </div>

    <div class="grid w-full gap-y-1.5 gap-x-5 leading-4 md:grid-cols-6 mt-1">
        <div class="md:col-span-4">
            <div class="flex justify-between gap-x-2 mb-0.5">
                <div>
                    <a class="inline mr-1" href="{{ route('achievement.show', $achievement['ID']) }}">
                        <x-achievement.title :rawTitle="$achievement['Title']" />
                    </a>

                    @if ($achievement['Points'] > 0 || $achievement['TrueRatio'] > 0)
                        <p class="inline text-xs whitespace-nowrap">
                            <span>({{ $achievement['Points'] }})</span>
                            <x-points-weighted-container>
                                ({{ localized_number($achievement['TrueRatio']) }})
                            </x-points-weighted-container>
                        </p>
                    @endif

                    @if ($achievement['SourceGameId'] ?? null)
                        <p class="inline text-xs">
                            <span>from</span>
                            <a class="inline mr-1" href="{{ route('game.show', $achievement['SourceGameId']) }}">
                                <x-game-title :rawTitle="$achievement['SourceGameTitle']" />
                            </a>
                        </p>
                    @endif

                    @if ($achievement['ActiveFrom'] ?? null && $achievement['ActiveUntil'] ?? null)
                        <p class="inline smalldate whitespace-nowrap">
                            <x-date :value="$achievement['ActiveFrom']" />
                            <span>-</span>
                            <x-date :value="$achievement['ActiveUntil']" />
                        </p>
                    @endif
                </div>

                @if ($achievement['type'] && !$useMinimalLayout)
                    <div class="flex items-center gap-x-1 md:hidden -mt-1.5">
                        <div class="-mt-1.5">
                            <x-game.achievements-list.type-indicator
                                :achievementType="$achievement['type']"
                                :beatenGameCreditDialogContext="$beatenGameCreditDialogContext"
                                :isCreditDialogEnabled="$isCreditDialogEnabled"
                            />
                        </div>
                    </div>
                @endif
            </div>

            <p class="leading-4">
                {{ $achievement['Description'] }}

                @if ($showAuthorName && $achievement['Author']) {{-- TODO: handle deleted author --}}
                    <span class="flex gap-x-1 text-[0.6rem] mt-2">
                        Author:
                        <a href="{{ route('user.show', $achievement['Author']) }}">
                            {{ $achievement['Author'] }}
                        </a>
                    </span>
                @endif
            </p>

            @if ($isUnlocked || $isUnlockedHardcore)
                <p class="hidden md:block mt-1.5 text-[0.6rem] text-neutral-400/70">
                    Unlocked
                    @if ($unlockDate) {{ $unlockDate }} @endif
                </p>
            @endif
        </div>

        <div class="md:col-span-2 md:flex md:flex-col-reverse md:justify-end md:pt-1 md:gap-y-1">
            @if ($achievement['type'] && !$useMinimalLayout)
                <div class="hidden md:flex items-center justify-end gap-x-1">
                    <x-game.achievements-list.type-indicator
                        :achievementType="$achievement['type']"
                        :beatenGameCreditDialogContext="$beatenGameCreditDialogContext"
                        :isCreditDialogEnabled="$isCreditDialogEnabled"    
                    />
                </div>
            @endif

            @if (!$useMinimalLayout)
                <x-game.achievements-list.list-item-global-progress
                    :achievement="$achievement"
                    :totalPlayerCount="$totalPlayerCount"
                />
            @endif
        </div>

        @if ($unlockDate)
            <p class="text-[0.6rem] text-neutral-400/70 md:hidden">Unlocked {{ $unlockDate }}</p>
        @endif
    </div>
</li>
