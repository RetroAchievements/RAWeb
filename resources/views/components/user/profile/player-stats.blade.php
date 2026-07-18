<?php

use App\Community\Enums\Rank;
?>

@props([
    'playerStats' => [],
    'userMassData' => [],
])

<?php
$hardcorePoints = $userMassData['TotalPoints'] ?? 0;
$casualPoints = $userMassData['TotalSoftcorePoints'] ?? 0;
$weightedPoints = $userMassData['TotalTruePoints'] ?? 0;

$hasMixedProgress = $hardcorePoints && $casualPoints;
$primaryMode = $casualPoints > $hardcorePoints ? 'casual' : 'hardcore';
$secondaryMode = $casualPoints > $hardcorePoints ? 'hardcore' : 'casual';
?>

<p role="heading" aria-level="2" class="mb-0.5 text-2xs font-bold">Player Stats</p>
<div
    class="relative w-full px-2 pt-2 bg-embed rounded-sm mb-6 pb-4 transition-all"
    x-data="{
        isExpanded: false,
        handleToggle() { this.isExpanded = !this.isExpanded; }
    }"
    :class="{ 'pb-2!': isExpanded }"
>
    @if ($hasMixedProgress)
        @if ($primaryMode === 'hardcore')
            <x-user.profile.arranged-stat-items
                :stats="[
                    $playerStats['hardcorePointsStat'],                 $playerStats['casualPointsStat'],
                    $playerStats['hardcoreSiteRankStat'],               $playerStats['casualSiteRankStat'],
                    $playerStats['hardcoreAchievementsUnlockedStat'],   $playerStats['casualAchievementsUnlockedStat'],
                    $playerStats['retroRatioStat'],                     $playerStats['startedGamesBeatenPercentageStat'],
                ]"
            />
        @elseif ($primaryMode === 'casual')
            <x-user.profile.arranged-stat-items
                :stats="[
                    $playerStats['casualPointsStat'],                 $playerStats['hardcorePointsStat'],
                    $playerStats['casualSiteRankStat'],               $playerStats['hardcoreSiteRankStat'],
                    $playerStats['casualAchievementsUnlockedStat'],   $playerStats['hardcoreAchievementsUnlockedStat'],
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
    @elseif ($primaryMode === 'casual')
        <x-user.profile.arranged-stat-items
            :stats="[
                $playerStats['casualAchievementsUnlockedStat'],   $playerStats['startedGamesBeatenPercentageStat'],
            ]"
        />
    @endif

    <div
        x-cloak
        x-show="isExpanded"
        x-transition:enter="ease-in-out duration-100"
        x-transition:enter-start="opacity-0 max-h-0 transform-[translateY(-0.25rem)] overflow-hidden"
        x-transition:enter-end="opacity-100 {{ $hasMixedProgress ? 'max-h-[114px] md:max-h-[54px]' : 'max-h-[96px] md:max-h-[36px]' }} transform-[translateY(0)] overflow-hidden"
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
        class="absolute left-1/2 -translate-x-1/2 bottom-[-10px] text-2xs btn z-2 transition lg:active:scale-95"
        @click="handleToggle"
        :class="{ 'hidden': isExpanded }"
    >
        see more
    </button>
</div>
