@props([
    'label' => null,
    'consoleId' => 0,
    'unfinishedCount' => 0,
    'beatenSoftcoreCount' => 0,
    'beatenHardcoreCount' => 0,
    'completedCount' => 0,
    'masteredCount' => 0,
])

<?php
$gameSystemIconSrc = getSystemIconUrl($consoleId);

$totalBeatenGamesCount = $beatenSoftcoreCount + $beatenHardcoreCount;
$totalMasteredGamesCount = $completedCount + $masteredCount;
$totalGamesCount = $unfinishedCount + $beatenSoftcoreCount + $beatenHardcoreCount + $completedCount + $masteredCount;

$unfinishedGamesWidth = sprintf("%1.2f", ($unfinishedCount / $totalGamesCount) * 100.0);
$beatenGamesWidth = sprintf("%1.2f", ($totalBeatenGamesCount / $totalGamesCount) * 100.0);
$masteredGamesWidth = sprintf("%1.2f", ($totalMasteredGamesCount / $totalGamesCount) * 100.0);

$widthMode = "equal"; // equal or dynamic
?>

<li
    class="
        w-full h-[26px] bg-embed rounded flex items-center [&>*:last-child]:rounded-r 
        [&>a]:h-full [&>a]:border [&>a]:flex [&>a]:items-center [&>a]:gap-x-2 [&>a]:whitespace-nowrap [&>a]:min-w-fit [&>a]:px-2
        [&>a:not(:first-child)]:justify-center
    "
>
    <a
        href="#"
        class="border-embed-highlight w-[102px] !min-w-[92px] pl-2 rounded-l"
    >
        <img src="{{ $gameSystemIconSrc }}" width="18" height="18" alt="{{ $label ? 'RA icon' : config('systems')[$consoleId]['name'] }} console icon">
        <p class="block tracking-tighter">{{ $label ?? config('systems')[$consoleId]['name_short'] }}</p>
    </a>

    @if ($unfinishedCount > 0 || $widthMode === "equal")
        <a
            href="#"
            class="border-embed-highlight text-neutral-500 transition-all"
            style="width: {{ $widthMode === 'equal' ? '100' : $unfinishedGamesWidth }}%"
            x-show="widthMode === 'equal' || {{ $unfinishedCount }} > 0"
            x-transition:leave="100ms"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            x-transition:enter="100ms"
            x-bind:style="'width: ' + (widthMode === 'equal' ? '100' : '{{ $unfinishedGamesWidth }}') + '%'"
        >
            {{ $unfinishedCount }}
        </a>
    @endif

    @if ($beatenSoftcoreCount > 0 || $beatenHardcoreCount > 0 || $widthMode === "equal")
        <a
            href="#"
            class="border-zinc-400/50 bg-neutral-400/10 text-zinc-300 transition-all flex gap-x-4"
            style="width: {{ $widthMode === 'equal' ? '100' : $beatenGamesWidth }}%"
            x-show="widthMode === 'equal' || {{ $beatenSoftcoreCount }} > 0 || {{ $beatenHardcoreCount }} > 0"
            x-transition:leave="100ms"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            x-transition:enter="100ms"
            x-bind:style="'width: ' + (widthMode === 'equal' ? '100' : '{{ $beatenGamesWidth }}') + '%'"
        >
            @if ($beatenSoftcoreCount > 0)
                <div class="flex items-center gap-x-1 text-zinc-400">
                    <div class="rounded-full w-2 h-2 border border-zinc-400"></div>
                    {{ $beatenSoftcoreCount }}
                </div>
            @endif

            @if ($beatenHardcoreCount > 0 || !$beatenSoftcoreCount)
                <div class="flex items-center gap-x-1">
                    <div class="rounded-full w-2 h-2 bg-zinc-300"></div>
                    {{ $beatenHardcoreCount }}
                </div>
            @endif
        </a>
    @endif

    @if ($completedCount > 0 || $masteredCount > 0 || $widthMode === "equal")
        <a
            href="#"
            class="border-yellow-600 bg-yellow-600/10 text-[gold] transition-all flex gap-x-4"
            style="width: {{ $widthMode === 'equal' ? '100' : $masteredGamesWidth }}%"
            x-show="widthMode === 'equal' || {{ $completedCount }} > 0 || {{ $masteredCount }} > 0"
            x-transition:leave="100ms"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            x-transition:enter="100ms"
            x-bind:style="'width: ' + (widthMode === 'equal' ? '100' : '{{ $masteredGamesWidth }}') + '%'"
        >
            @if ($completedCount > 0)
                <div class="flex items-center gap-x-1 text-yellow-600">
                    <div class="rounded-full w-2 h-2 border border-yellow-600"></div>
                    {{ $completedCount }}
                </div>
            @endif

            @if ($masteredCount > 0 || !$completedCount)
                <div class="flex items-center gap-x-1">
                    <div class="rounded-full w-2 h-2 bg-[gold]"></div>
                    {{ $masteredCount }}
                </div>
            @endif
        </a>
    @endif
</li>
