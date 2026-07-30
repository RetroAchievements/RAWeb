@props([
    'casualCompletionPercentage' => 0,
    'hardcoreCompletionPercentage' => 0,
    'numPossible' => 0,
    'hasAward' => false,
])

<?php
$casualBarWidth = 0;
if ($casualCompletionPercentage > 0) {
    $casualBarWidth = $casualCompletionPercentage - $hardcoreCompletionPercentage;
}
?>

<div class="cprogress-pbar__root">
    {{-- .cprogress-pbar__root > div[role='progressbar'] --}}
    <div
        role="progressbar"
        aria-valuemin="0"
        aria-valuemax="100"
        aria-valuenow="{{ $casualCompletionPercentage }}"
    >
        @if ($numPossible > 0)
            {{-- .cprogress-pbar__root > div[role='progressbar'] > div:first-child --}}
            <div
                style="width: {{ $hardcoreCompletionPercentage }}%"
                class="{{ $hardcoreCompletionPercentage == 100 ? 'rounded-r' : '' }}"
            ></div>

            {{-- .cprogress-pbar__root > div[role='progressbar'] > div:last-child --}}
            <div
                style="width: {{ $casualBarWidth }}%"
                class="{{ $hardcoreCompletionPercentage == 0 ? 'rounded-l-sm' : '' }} {{ $casualCompletionPercentage == 100 ? 'rounded-r' : '' }}"
            ></div>
        @elseif ($numPossible === 0 && !$hasAward)
            {{-- render an empty bar, there are no achievements yet --}}
        @else
            {{-- .cprogress-pbar__root > div[role='progressbar'] > div:first-child --}}
            <div class="w-full rounded-l-sm rounded-r bg-zinc-800! light:bg-zinc-300!"></div>
        @endif
    </div>

    {{-- .cprogress-pbar__root > p --}}
    <p class="text-2xs flex justify-between w-full">
        @if ($numPossible === 0 && $hasAward)
            Set is retired
        @elseif ($numPossible === 0 && !$hasAward)
            No achievements yet
        @else
            {{ $casualCompletionPercentage }}%
        @endif
    </p>
</div>
