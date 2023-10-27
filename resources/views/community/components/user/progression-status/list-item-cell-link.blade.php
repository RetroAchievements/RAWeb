@props([
    'cellGamesCounts' => [0],
    'cellType' => 'unfinished', // 'unfinished' | 'beaten' | 'mastered'
    'href' => '#',
    'totalGamesCount' => 1,
    'widthMode' => 'equal', // 'equal' | 'dynamic'
])

<?php
$cellGamesCount = array_sum($cellGamesCounts);

$classNames = "border-embed-highlight text-neutral-500";
if ($cellType === 'beaten') {
    $classNames = "border-zinc-400/50 bg-neutral-400/10 text-zinc-300 light:text-zinc-600";
} elseif ($cellType === 'mastered') {
    $classNames = "border-yellow-600 bg-yellow-600/10 text-[gold] light:text-yellow-600";
}

$classNames .= " cell group";

if ($widthMode === 'dynamic' && $cellGamesCount > 0) {
    $classNames .= " min-w-fit px-2";
}

$dynamicWidth = sprintf("%1.2f", ($cellGamesCount / $totalGamesCount) * 100.0);
?>

<a
    x-data="{ cellGamesCount: {{ $cellGamesCount }} }"
    href="{{ $href }}"
    class="{{ $classNames }}"
    :class="{'pointer-events-none': widthMode === 'dynamic' && cellGamesCount === 0, 'min-w-fit px-2': widthMode === 'dynamic' && cellGamesCount > 0}"
    style="width: {{ $widthMode === 'equal' ? '100' : $dynamicWidth }}%"
    :style="'width: ' + (widthMode === 'equal' ? '100' : '{{ $dynamicWidth }}') + '%'"
    :aria-disabled="widthMode === 'dynamic' && cellGamesCount === 0"
    :tabindex="widthMode === 'dynamic' && cellGamesCount === 0 ? '-1' : '0'"
>
    {{ $slot }}
</a>
