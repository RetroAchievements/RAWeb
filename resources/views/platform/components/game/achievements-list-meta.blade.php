@props([
    'isOfficial' => true,
    'numAchievements' => 0,
    'numMissableAchievements' => 0,
    'totalPossible' => 0, // total possible points
    'totalPossibleTrueRatio' => 0, // total possible RetroPoints
])

<?php

use App\Enums\UserPreference;

$canShowMissableCount = true;
if ($numMissableAchievements > 0) {
    $currentUser = Auth::user();
    if (isset($currentUser) && BitSet($currentUser->websitePrefs, UserPreference::Game_HideMissableIndicators)) {
        $canShowMissableCount = false;
    }
}
?>

<div x-data="toggleAchievementRowsComponent()">
    <p @if ($numMissableAchievements > 0) class="mb-1" @endif>
        There {{ $numAchievements === 1 ? 'is' : 'are'}}
        <span class="font-bold">
            {{ localized_number($numAchievements) }}
            @if (!$isOfficial)
                Unofficial
            @endif
        </span>
        {{ mb_strtolower(__res('achievement', $numAchievements)) }}
        worth
        <span class="font-bold">
            {{ localized_number($totalPossible) }}
        </span>
        <x-points-weighted-container>({{ localized_number($totalPossibleTrueRatio )}})</x-points-weighted-container>
        {{ mb_strtolower(__res('point', $totalPossible)) }}.
    </p>

    @if ($canShowMissableCount && $numMissableAchievements > 0)
        <p>
            <div class="flex items-center gap-x-1">
                <div class="w-5 h-5 p-0.5 rounded-full bg-embed border border-dashed border-stone-500 text-white">
                    <x-icon.missable />
                </div>
                <span id="missable-toggle-button-content">
                        This set has {{ $numMissableAchievements }} missable {{ mb_strtolower(__res('achievement', $numMissableAchievements)) }}.
                    </span>
            </div>
        </p>
    @endif
</div>
