@props([
    'totalAchievementsCount' => 0,
    'numEarnedSoftcoreAchievements' => 0,
    'numEarnedHardcoreAchievements' => 0,
])

<?php
$hardcoreProgressWidth = 0;
$softcoreProgressWidth = 0;

$softcoreCompletionPercentage = 0;
$hardcoreCompletionPercentage = 0;

// Never divide by zero.
if ($totalAchievementsCount > 0) {
    $hardcoreProgressWidth = ($numEarnedHardcoreAchievements / $totalAchievementsCount) * 100;
    $softcoreProgressWidth = ($numEarnedSoftcoreAchievements / $totalAchievementsCount) * 100;

    $hardcoreCompletionPercentage = sprintf("%01.0f", floor($hardcoreProgressWidth));
    $softcoreCompletionPercentage = sprintf("%01.0f", floor($softcoreProgressWidth + $hardcoreCompletionPercentage));
}

$completionPercentage = sprintf("%01.0f", floor($hardcoreProgressWidth + $softcoreProgressWidth));
?>

<div
    role="progressbar"
    aria-valuemin="0"
    aria-valuemax="100"
    aria-valuenow="{{ $completionPercentage }}"
    class="absolute w-full bottom-0 left-0 h-2 bg-embed-highlight lg:rounded-b flex"
>
    <x-game.current-progress.progress-bar-indicator
        :softcoreCompletionPercentage="$softcoreCompletionPercentage"
        :hardcoreCompletionPercentage="$hardcoreCompletionPercentage"
        :totalCompletionPercentage="$completionPercentage"
    />

    <span class="sr-only">{{ $completionPercentage}}% complete</span>

    <!-- Hardcore progress bar -->
    <div 
        class="bg-yellow-600 h-full lg:rounded-bl {{ $hardcoreProgressWidth === 100 ? 'lg:rounded-br' : '' }}" 
        style="width: {{ $hardcoreProgressWidth }}%"
    >
        <span class="sr-only">{{ $numEarnedHardcoreAchievements }} hardcore achievements</span>
    </div>

    <!-- Softcore progress bar -->
    <div 
        class="
            bg-neutral-500/90 h-full 
            {{ $hardcoreProgressWidth === 0 && $softcoreProgressWidth > 0 ? 'lg:rounded-bl' : '' }}
            {{ $softcoreProgressWidth === 100 || $hardcoreProgressWidth + $softcoreProgressWidth > 99.9 ? 'lg:rounded-br' : '' }}
        "
        style="width: {{ $softcoreProgressWidth }}%"
    >
        <span class="sr-only">{{ $numEarnedSoftcoreAchievements }} softcore achievements</span>
    </div>
</div>