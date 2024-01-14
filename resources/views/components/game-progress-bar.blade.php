@props([
    'awardIndicator' => null, // 'unfinished' | 'beaten-softcore' | 'beaten-hardcore' | 'completed' | 'mastered' | null
    'softcoreProgress' => 0,
    'hardcoreProgress' => 0,
    'maxProgress' => 100,
    'tooltip' => null, // ?string
])

<?php
    $hardcoreProgressBarWidth = $softcoreProgressBarWidth = '0';

    if ($maxProgress > 0) {
        if ($softcoreProgress >= $maxProgress) {
            $softcoreProgressBarWidth = sprintf("%01.2f", ($maxProgress - $hardcoreProgress) * 100 / $maxProgress);
        } elseif ($softcoreProgress > 0) {
            $softcoreProgressBarWidth = sprintf("%01.2f", ($softcoreProgress - $hardcoreProgress) * 100 / $maxProgress);
        }

        if ($hardcoreProgress >= $maxProgress) {
            $hardcoreProgressBarWidth = '100.0';
        } elseif ($hardcoreProgress > 0) {
            $hardcoreProgressBarWidth = sprintf("%01.2f", $hardcoreProgress * 100 / $maxProgress);
        }
    }

    if (!$tooltip) {
        if ($hardcoreProgress === $softcoreProgress) {
            $tooltip = "{$hardcoreProgress} of {$maxProgress}";
        } else {
            $tooltip = "{$softcoreProgress} ({$hardcoreProgress} hardcore) of {$maxProgress}";
        }
    }
?>

<div class="flex items-center">
    <div
        role="progressbar"
        aria-valuemin="0"
        aria-valuemax="{{ $maxProgress }}"
        aria-valuenow="{{ $softcoreProgress }}"
        aria-label="{{ $tooltip }}"
        title="{{ $tooltip }}"
        class="w-full h-1 bg-zinc-950 light:bg-zinc-300 flex space-x-px overflow-hidden {{ $awardIndicator ? 'rounded-l' : 'rounded' }}"
    >
        @if ($hardcoreProgress > 0)
            <div
                style="width: {{ $hardcoreProgressBarWidth }}%"
                class="bg-gradient-to-r from-amber-500 to-[gold] light:bg-yellow-500 h-full"
            ></div>
        @endif

        @if ($softcoreProgress > $hardcoreProgress)
            <div
                style="width: {{ $softcoreProgressBarWidth }}%"
                class="bg-neutral-500 h-full"
            ></div>
        @endif
    </div>

    @if ($awardIndicator)
        @php
            $awardTitles = [
                'unfinished' => 'Unfinished',
                'beaten-softcore' => 'Beaten (softcore)',
                'beaten-hardcore' => 'Beaten',
                'completed' => 'Completed',
                'mastered' => 'Mastered',
            ];
        @endphp

        <div
            class="gprogress-ind__root"
            data-award="{{ $awardIndicator }}"
            title="{{ $awardTitles[$awardIndicator] ?? 'Unfinished' }}"
        >
            <div></div>
        </div>
    @endif
</div>
