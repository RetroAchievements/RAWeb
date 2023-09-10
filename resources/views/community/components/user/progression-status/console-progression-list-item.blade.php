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

$widthsPreference = request()->cookie('progression_status_widths_preference');

$widthMode = $widthsPreference;
if ($widthMode !== 'equal' && $widthMode !== 'dynamic') {
    $widthMode = 'equal';
}

$displayLabel = $label ?? config('systems')[$consoleId]['name_short'];
$consoleTooltipLabel = $label ?? config('systems')[$consoleId]['name'];
?>

<li class="progression-status-row">
    <a
        href="#"
        class="border-embed-highlight w-[102px] !min-w-[92px] pl-2 rounded-l hover:border-link-hover"
        @if (!$label) title="{{ $consoleTooltipLabel }}" @endif
    >
        <img
            src="{{ $gameSystemIconSrc }}"
            width="18"
            height="18"
            alt="{{ $label ? 'RA icon' : $consoleTooltipLabel }} console icon"
        >
        <p class="block tracking-tighter">{{ $displayLabel }}</p>
    </a>

    <x-user.progression-status.list-item-cell-link
        cellType="unfinished"
        :widthMode="$widthMode"
        :cellGamesCounts="[$unfinishedCount]"
        :totalGamesCount="$totalGamesCount"
    >
        {{ $unfinishedCount }}
    </x-user.progression-status.list-item-cell-link>

    <x-user.progression-status.list-item-cell-link
        cellType="beaten"
        :widthMode="$widthMode"
        :cellGamesCounts="[$beatenSoftcoreCount, $beatenHardcoreCount]"
        :totalGamesCount="$totalGamesCount"
    >
        @if ($beatenSoftcoreCount > 0)
            <div class="tally text-zinc-400 light:text-zinc-600">
                <div class="dot border border-zinc-400 light:border-zinc-600"></div>
                {{ $beatenSoftcoreCount }}
            </div>
        @endif

        @if ($beatenHardcoreCount > 0 || !$beatenSoftcoreCount)
            <div class="tally">
                <div class="dot bg-zinc-300 light:bg-zinc-500"></div>
                {{ $beatenHardcoreCount }}
            </div>
        @endif
    </x-user.progression-status.list-item-cell-link>

    <x-user.progression-status.list-item-cell-link
        cellType="mastered"
        :widthMode="$widthMode"
        :cellGamesCounts="[$completedCount, $masteredCount]"
        :totalGamesCount="$totalGamesCount"
    >
        @if ($completedCount > 0)
            <div class="tally text-yellow-600">
                <div class="dot border border-yellow-600"></div>
                {{ $completedCount }}
            </div>
        @endif

        @if ($masteredCount > 0 || !$completedCount)
            <div class="tally">
                <div class="dot bg-[gold] light:bg-yellow-600"></div>
                {{ $masteredCount }}
            </div>
        @endif
    </x-user.progression-status.list-item-cell-link>
</li>
