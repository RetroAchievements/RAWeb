@props([
    'softcoreProgress' => 0,
    'hardcoreProgress' => 0,
    'maxProgress' => 100,
    'tooltip' => '',
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
?>
<div role="progressbar" class="w-full h-1 bg-embed rounded flex overflow-hidden"
    aria-valuemin="0" aria-valuemax="{{ $maxProgress }}" aria-valuenow="{{ $softcoreProgress }}"
    title="{{ $tooltip }}">
    @if ($hardcoreProgress > 0)
    <div style="width: {{ $hardcoreProgressBarWidth }}%"
         class="bg-[#cc9900] h-full"></div>
    @endif
    @if ($softcoreProgress > $hardcoreProgress)
    <div style="width: {{ $softcoreProgressBarWidth }}%"
         class="bg-[rgb(11,113,193)] h-full"></div>
    @endif
</div>