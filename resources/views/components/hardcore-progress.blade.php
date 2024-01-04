@props([
    'softcoreProgress' => 0,
    'hardcoreProgress' => 0,
    'maxProgress' => 100,
    'tooltip' => '',
])

<?php
    $hardcoreProgressBarWidth = $softcoreProgressBarWidth = 0;
    if ($maxProgress > 0) {
        $hardcoreProgressBarWidth = sprintf("%01.2f", $hardcoreProgress * 100 / $maxProgress);
        $softcoreProgressBarWidth = sprintf("%01.2f", ($softcoreProgress - $hardcoreProgress) * 100 / $maxProgress);
    }
?>
<div role="progressbar" aria-valuemin="0" aria-valuemax="100"
    title="{{ $softcoreProgress }} of {{ $maxProgress }} unlocked"
    class="w-full h-1 bg-embed rounded flex">
    <div style="width: {{ $hardcoreProgressBarWidth }}%"
         class="bg-[#cc9900] h-full {{ $hardcoreProgressBarWidth > 0 ? 'rounded-l' : '' }}"></div>
    <div style="width: {{ $softcoreProgressBarWidth }}%"
         class="bg-[rgb(11,113,193)] h-full {{ $hardcoreProgressBarWidth === 0 ? 'rounded-l' : '' }}"></div>
</div>