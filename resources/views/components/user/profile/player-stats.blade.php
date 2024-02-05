<?php

use App\Community\Enums\Rank;
?>

@props([
    'playerStats' => [],
    'userMassData' => [],
])

<?php
$hardcorePoints = $userMassData['TotalPoints'] ?? 0;
$softcorePoints = $userMassData['TotalSoftcorePoints'] ?? 0;
$weightedPoints = $userMassData['TotalTruePoints'] ?? 0;

$hasMixedProgress = $hardcorePoints && $softcorePoints;
$primaryMode = $softcorePoints > $hardcorePoints ? 'softcore' : 'hardcore';
$secondaryMode = $softcorePoints > $hardcorePoints ? 'hardcore' : 'softcore';
?>

<p role="heading" aria-level="2" class="mb-0.5 text-2xs font-bold">Player Stats</p>
<div
    class="relative w-full px-2 pt-2 bg-embed rounded mb-6 pb-4 transition-all"
    x-data="{
        isExpanded: false,
        handleToggle() { this.isExpanded = !this.isExpanded; }
    }"
    :class="{ '!pb-2': isExpanded }"
>
    @if ($hasMixedProgress)
        @if ($primaryMode === 'hardcore')
            <x-user.profile.arranged-stat-items
                :stats="[
                    $playerStats['hardcorePointsStat'],                 $playerStats['softcorePointsStat'],
                    $playerStats['hardcoreSiteRankStat'],               $playerStats['softcoreSiteRankStat'],
                    $playerStats['hardcoreAchievementsUnlockedStat'],   $playerStats['softcoreAchievementsUnlockedStat'],
                    $playerStats['retroRatioStat'],                     $playerStats['startedGamesBeatenPercentageStat'],
                ]"
            />
        @elseif ($primaryMode === 'softcore')
            <x-user.profile.arranged-stat-items
                :stats="[
                    $playerStats['softcorePointsStat'],                 $playerStats['hardcorePointsStat'],
                    $playerStats['softcoreSiteRankStat'],               $playerStats['hardcoreSiteRankStat'],
                    $playerStats['softcoreAchievementsUnlockedStat'],   $playerStats['hardcoreAchievementsUnlockedStat'],
                    $playerStats['startedGamesBeatenPercentageStat'],   $playerStats['retroRatioStat'],
                ]"
            />
        @endif
    @elseif ($primaryMode === 'hardcore')
        <x-user.profile.arranged-stat-items
            :stats="[
                $playerStats['hardcoreAchievementsUnlockedStat'],   $playerStats['retroRatioStat'],
                $playerStats['totalGamesBeatenStat'],               $playerStats['startedGamesBeatenPercentageStat'],   
            ]"
        />
    @elseif ($primaryMode === 'softcore')
        <x-user.profile.arranged-stat-items
            :stats="[
                $playerStats['softcoreAchievementsUnlockedStat'],   $playerStats['startedGamesBeatenPercentageStat'], 
            ]"
        />
    @endif

    <div
        x-cloak
        x-show="isExpanded"
        x-transition:enter="ease-in-out duration-100"
        x-transition:enter-start="opacity-0 max-h-0 -translate-y-1 overflow-hidden"
        x-transition:enter-end="opacity-1 {{ $hasMixedProgress ? 'max-h-[114px] md:max-h-[54px]' : 'max-h-[96px] md:max-h-[36px]' }} translate-y-0 overflow-hidden"
        class="pt-1"
    >
        {{--
            We very intentionally bury Average Completion Percentage behind a secondary click.
            There is plenty of feedback to suggest this statistic leads to some undesirable
            behavior patterns, but we don't want to remove it completely.
        --}}
        @if ($hasMixedProgress)
            <x-user.profile.arranged-stat-items
                :stats="[
                    $playerStats['pointsLast7DaysStat'],        $playerStats['totalGamesBeatenStat'],
                    $playerStats['pointsLast30DaysStat'],       $playerStats['averageCompletionStat'],
                    $playerStats['averagePointsPerWeekStat'],
                ]"
            />
        @else
            <x-user.profile.arranged-stat-items
                :stats="[
                    $playerStats['pointsLast7DaysStat'],        $playerStats['averagePointsPerWeekStat'],
                    $playerStats['pointsLast30DaysStat'],       $playerStats['averageCompletionStat'],
                ]"
            />
        @endif
    </div>

    <button
        class="absolute left-1/2 -translate-x-1/2 bottom-[-10px] text-2xs btn z-[2] transition lg:active:scale-95"
        @click="handleToggle"
        :class="{ 'hidden': isExpanded }"
    >
        see more
    </button>
</div>
