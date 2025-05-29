<?php

use App\Enums\UserPreference;
?>

@props([
    'canShowHideInactiveAchievements' => false,
    'canShowHideUnlockedAchievements' => false,
    'gameId' => null,
    'numMissableAchievements' => 0,
    'shouldHideUnlocked' => false,
])

<?php
$isMissableFilterAllowed = $numMissableAchievements > 0;
if ($isMissableFilterAllowed) {
    $currentUser = Auth::user();
    if (isset($currentUser) && BitSet($currentUser->websitePrefs, UserPreference::Game_HideMissableIndicators)) {
        $isMissableFilterAllowed = false;
    }
}
?>

<div x-data="toggleAchievementRowsComponent({{ $gameId }}, {{ $shouldHideUnlocked ? 'true' : 'false' }})" x-init="init()" class="flex gap-x-4 sm:flex-col md:flex-row lg:flex-col xl:flex-row">
    @if ($isMissableFilterAllowed)
        <label class="flex items-center gap-x-1 select-none cursor-pointer">
            <input
                type="checkbox"
                autocomplete="off"
                class="cursor-pointer"
                @change="toggleNonMissableRows"
            >
                Only show missables
            </input>
        </label>
    @endif

    @if ($canShowHideUnlockedAchievements)
        <label class="flex items-center gap-x-1 select-none cursor-pointer">
            <input
                type="checkbox"
                autocomplete="off"
                class="cursor-pointer"
                :checked="isUsingHideUnlockedAchievements"
                @change="toggleUnlockedRows"
                @if ($shouldHideUnlocked) checked @endif
            >
                Hide unlocked achievements
            </input>
        </label>
    @endif

    @if ($canShowHideInactiveAchievements)
        <label class="flex items-center gap-x-1 select-none cursor-pointer">
            <input
                type="checkbox"
                autocomplete="off"
                class="cursor-pointer"
                @change="toggleInactiveRows"
            >
                Hide inactive achievements
            </input>
        </label>
    @endif
</div>
