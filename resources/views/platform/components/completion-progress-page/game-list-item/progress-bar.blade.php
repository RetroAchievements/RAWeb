@props([
    'softcoreCompletionPercentage' => 0,
    'hardcoreCompletionPercentage' => 0,
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
    </div>

    {{-- .cprogress-pbar__root > p --}}
    <p class="text-2xs flex justify-between w-full">
        {{ $softcoreCompletionPercentage }}%
    </p>
</div>
