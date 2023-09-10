@props([
    'softcoreCompletionPercentage' => 0,
    'hardcoreCompletionPercentage' => 0,
    'numPossible' => 0,
])

<?php
$softcoreBarWidth = 0;
if ($softcoreCompletionPercentage > 0) {
    $softcoreBarWidth = $softcoreCompletionPercentage - $hardcoreCompletionPercentage;
}
?>

<div class="cprogress-pbar__root">
    {{-- .cprogress-pbar__root > div[role='progressbar'] --}}
    <div
        role="progressbar"
        aria-valuemin="0"
        aria-valuemax="100"
        aria-valuenow="{{ $softcoreCompletionPercentage }}"
    >
        @if ($numPossible > 0)
            {{-- .cprogress-pbar__root > div[role='progressbar'] > div:first-child --}}
            <div
                style="width: {{ $hardcoreCompletionPercentage }}%"
                class="{{ $hardcoreCompletionPercentage == 100 ? 'rounded-r' : '' }}"
            ></div>

            {{-- .cprogress-pbar__root > div[role='progressbar'] > div:last-child --}}
            <div
                style="width: {{ $softcoreBarWidth }}%"
                class="{{ $hardcoreCompletionPercentage == 0 ? 'rounded-l' : '' }} {{ $softcoreCompletionPercentage == 100 ? 'rounded-r' : '' }}"
            ></div>
        @else
            {{-- .cprogress-pbar__root > div[role='progressbar'] > div:first-child --}}
            <div class="w-full rounded-l rounded-r !bg-zinc-800 light:!bg-zinc-300"></div>
        @endif
    </div>

    {{-- .cprogress-pbar__root > p --}}
    <p class="text-2xs flex justify-between w-full">
        @if ($numPossible === 0)
            Set is retired
        @else
            {{ $softcoreCompletionPercentage }}%
        @endif
    </p>
</div>
