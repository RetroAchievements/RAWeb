@props([
    'totalCompletionPercentage' => 0,
    'softcoreCompletionPercentage' => 0,
    'hardcoreCompletionPercentage' => 0,
])

<?php
$isMixedProgress = $softcoreCompletionPercentage > 0 && $hardcoreCompletionPercentage > 0;

$percentageDifference = abs($softcoreCompletionPercentage - $hardcoreCompletionPercentage);
$canShowMixedProgress = $isMixedProgress && $percentageDifference >= 9;
?>

@if ($canShowMixedProgress)
    <p
        class="absolute bottom-2 text-[0.64rem] opacity-0 group-hover:opacity-100 text-yellow-500 tracking-tighter select-none"
        style="left: calc({{ $hardcoreCompletionPercentage }}% - 10px)"
    >
        {{ number_format($hardcoreCompletionPercentage, 0) }}%
    </p>

    <p
        class="absolute bottom-2 text-[0.64rem] opacity-0 group-hover:opacity-100 text-neutral-400 tracking-tighter select-none"
        style="left: calc({{ $softcoreCompletionPercentage }}% - 10px)"
    >
        {{ number_format($softcoreCompletionPercentage, 0) }}%
    </p>
@else
    <p
        class="absolute bottom-2 text-[0.65rem] opacity-0 group-hover:opacity-100 select-none"
        style="left: calc({{ $totalCompletionPercentage }}% - 10px)"
    >
        {{ $totalCompletionPercentage }}%
    </p>    
@endif