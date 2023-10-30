@props([
    'beatenGameCreditDialogContext' => 's:|h:',
    'gameId' => 0,
    'isBeatable' => false,
    'isBeatenHardcore' => false,
    'isBeatenSoftcore' => false,
    'isCompleted' => false,
    'isMastered' => false,
    'isEvent' => false,
    'numEarnedHardcoreAchievements' => 0,
    'numEarnedHardcorePoints' => 0,
    'numEarnedSoftcoreAchievements' => 0,
    'numEarnedSoftcorePoints' => 0,
    'numEarnedWeightedPoints' => 0,
    'totalAchievementsCount' => 0,
    'totalPointsCount' => 0,
])

<?php
$hasUnlockedAnyAchievements = $numEarnedHardcoreAchievements > 0 || $numEarnedSoftcoreAchievements > 0;
$hasAnyProgressionAward = $isBeatenSoftcore || $isBeatenHardcore || $isCompleted || $isMastered;

$canShowGlow = $hasUnlockedAnyAchievements && $hasAnyProgressionAward;
?>

@if ($totalAchievementsCount > 0)
    <div class="-mx-5 lg:mx-0 relative group">
        @if ($canShowGlow)
            <x-game.current-progress.glow isMastered="{{ $isMastered }}" />
        @endif

        @if ($hasUnlockedAnyAchievements)
            <div class="absolute top-2 right-2 z-10">
                <x-game.current-progress.secondary-actions-menu gameId="{{ $gameId }}" />
            </div>
        @endif

        <div class="lg:rounded bg-embed border border-embed-highlight px-5 pt-3.5 pb-5 relative">
            <div class="mb-2">
                <p class="sr-only">Your Progress</p>

                @if (!$hasUnlockedAnyAchievements)
                    <p class="leading-4 mt-2">You haven't unlocked any achievements for this game.</p>
                @else
                    <x-game.current-progress.big-status-label
                        :beatenGameCreditDialogContext="$beatenGameCreditDialogContext"
                        :gameId="$gameId"
                        :isBeatable="$isBeatable"
                        :isBeatenHardcore="$isBeatenHardcore"
                        :isBeatenSoftcore="$isBeatenSoftcore"
                        :isCompleted="$isCompleted"
                        :isMastered="$isMastered"
                        :isEvent="$isEvent"
                    />

                    <x-game.current-progress.stats
                        :totalAchievementsCount="$totalAchievementsCount"
                        :totalPointsCount="$totalPointsCount"
                        :numEarnedSoftcoreAchievements="$numEarnedSoftcoreAchievements"
                        :numEarnedHardcoreAchievements="$numEarnedHardcoreAchievements"
                        :numEarnedSoftcorePoints="$numEarnedSoftcorePoints"
                        :numEarnedHardcorePoints="$numEarnedHardcorePoints"
                        :numEarnedWeightedPoints="$numEarnedWeightedPoints"
                    />
                @endif
            </div>

            <x-game.current-progress.progress-bar
                :totalAchievementsCount="$totalAchievementsCount"
                :numEarnedSoftcoreAchievements="$numEarnedSoftcoreAchievements"
                :numEarnedHardcoreAchievements="$numEarnedHardcoreAchievements"
            />
        </div>
    </div>
@endif