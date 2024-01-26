@props([
    'averageCompletionPercentage' => '0.0',
    'averageFinishedGames' => 0,
    'averagePointsPerWeek' => 0,
    'hardcoreRankMeta' => [],
    'recentPointsEarned' => [],
    'softcoreRankMeta' => [],
    'totalHardcoreAchievements' => 0,
    'totalSoftcoreAchievements' => 0,
    'userMassData' => [],
])

<?php
use App\Community\Enums\Rank;

$hardcorePoints = $userMassData['TotalPoints'] ?? 0;
$softcorePoints = $userMassData['TotalSoftcorePoints'] ?? 0;
$weightedPoints = $userMassData['TotalTruePoints'] ?? 0;

$hasMixedProgress = $hardcorePoints && $softcorePoints;
$primaryMode = $softcorePoints > $hardcorePoints ? 'softcore' : 'hardcore';
$secondaryMode = $softcorePoints > $hardcorePoints ? 'hardcore' : 'softcore';
$retroRatio = $weightedPoints ? sprintf("%01.2f", $weightedPoints / $hardcorePoints) : null;
?>

<p role="heading" aria-level="2" class="mb-0.5 text-2xs font-bold">Player stats</p>
<div
    class="relative w-full px-2 pt-2 bg-embed rounded mb-6 pb-4 transition-all"
    x-data="{
        isExpanded: false,
        handleToggle() { this.isExpanded = !this.isExpanded; }
    }"
    :class="{ '!pb-2': isExpanded }"
>
    <div class="grid md:grid-cols-2 gap-x-12 gap-y-1" :class="{ 'mb-1': isExpanded }">
        <div class="flex flex-col gap-y-1">
            <x-user.profile.mode-player-stat-cells
                :averageFinishedGames="$averageFinishedGames"
                :hardcoreRankMeta="$hardcoreRankMeta"
                :mode="$primaryMode"    
                :retroRatio="$retroRatio"
                :softcoreRankMeta="$softcoreRankMeta"
                :totalHardcoreAchievements="$totalHardcoreAchievements"
                :totalSoftcoreAchievements="$totalSoftcoreAchievements"
                :userMassData="$userMassData"
            />
        </div>

        <div class="flex flex-col gap-y-1">
            <x-user.profile.mode-player-stat-cells
                :averageFinishedGames="$averageFinishedGames"
                :hardcoreRankMeta="$hardcoreRankMeta"
                :mode="$secondaryMode"    
                :retroRatio="$retroRatio"
                :softcoreRankMeta="$softcoreRankMeta"
                :totalHardcoreAchievements="$totalHardcoreAchievements"
                :totalSoftcoreAchievements="$totalSoftcoreAchievements"
                :userMassData="$userMassData"
            />
        </div>
    </div>

    <div
        x-cloak
        x-show="isExpanded"
        x-transition:enter="ease-in-out duration-100"
        x-transition:enter-start="opacity-0 max-h-0 -translate-y-1 overflow-hidden"
        x-transition:enter-end="opacity-1 max-h-[90px] md:max-h-[32px] translate-y-0 overflow-hidden"
    >
        <div class="grid md:grid-cols-2 gap-x-12 gap-y-1">
            <x-user.profile.stat-element label="Points earned in the last 7 days">
                <span class="{{ $recentPointsEarned['pointsLast7Days'] > 0 ? 'font-bold' : 'text-muted' }}">
                    {{ localized_number($recentPointsEarned['pointsLast7Days']) }}
                </span>
            </x-user.profile.stat-element>

            <x-user.profile.stat-element label="Points earned in the last 30 days">
                <span class="{{ $recentPointsEarned['pointsLast30Days'] > 0 ? 'font-bold' : 'text-muted' }}">
                    {{ localized_number($recentPointsEarned['pointsLast30Days']) }}
                </span>
            </x-user.profile.stat-element>

            <x-user.profile.stat-element label="Average points per week">
                <span class="{{ $averagePointsPerWeek > 0 ? 'font-bold' : 'text-muted' }}">
                    {{ localized_number($averagePointsPerWeek) }}
                </span>
            </x-user.profile.stat-element>

            {{--
                We very intentionally bury Average Completion Percentage behind a secondary click.
                There is plenty of feedback to suggest this statistic leads to some undesirable
                behavior patterns, but we don't want to remove it completely.
            --}}
            <x-user.profile.stat-element label="Average completion percentage">
                <span class="{{ $averageCompletionPercentage === '0.00' ? 'text-muted' : 'font-bold' }}">
                    {{ $averageCompletionPercentage }}%
                </span>
            </x-user.profile.stat-element>
        </div>
    </div>

    <button
        class="absolute left-1/2 -translate-x-1/2 bottom-[-10px] text-2xs btn z-[2] transition lg:active:scale-95"
        @click="handleToggle"
        :class="{ 'hidden': isExpanded }"
    >
        see more
    </button>
</div>