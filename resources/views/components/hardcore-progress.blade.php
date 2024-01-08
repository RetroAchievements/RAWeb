@props([
    'softcoreProgress' => 0,
    'hardcoreProgress' => 0,
    'maxProgress' => 100,
    'tooltip' => '',
])
<?php
    $hardcoreProgressBarWidth = $softcoreProgressBarWidth = '0';
    $hardcoreClass = $softcoreClass = '';

    if ($maxProgress > 0) {
        if ($softcoreProgress >= $maxProgress) {
            $softcoreProgressBarWidth = sprintf("%01.2f", ($maxProgress - $hardcoreProgress) * 100 / $maxProgress);
            $softcoreClass = 'rounded';
        } elseif ($softcoreProgress > 0) {
            $softcoreProgressBarWidth = sprintf("%01.2f", ($softcoreProgress - $hardcoreProgress) * 100 / $maxProgress);
            $softcoreClass = 'rounded-l';
        }

        if ($hardcoreProgress >= $maxProgress) {
            $hardcoreProgressBarWidth = '100.0';
            $hardcoreClass = 'rounded';
        } elseif ($hardcoreProgress > 0) {
            $hardcoreProgressBarWidth = sprintf("%01.2f", $hardcoreProgress * 100 / $maxProgress);
            $hardcoreClass = 'rounded-l';
        }
    }
?>
<div role="progressbar" aria-valuemin="0" aria-valuemax="100"
    title="{{ $softcoreProgress }} of {{ $maxProgress }} unlocked"
    class="w-full h-1 bg-embed rounded flex">
    @if ($hardcoreProgress > 0)
    <div style="width: {{ $hardcoreProgressBarWidth }}%"
         class="bg-[#cc9900] h-full {{ $hardcoreClass }}"></div>
    @endif
    @if ($softcoreProgress > $hardcoreProgress)
    <div style="width: {{ $softcoreProgressBarWidth }}%"
         class="bg-[rgb(11,113,193)] h-full {{ $softcoreClass }}"></div>
    @endif
</div>