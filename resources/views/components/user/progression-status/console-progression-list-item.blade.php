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

use App\Models\System;

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

$system = System::find($consoleId);
if ($system) {
    $gameSystemIconSrc = getSystemIconUrl($system);
    $displayLabel = $label ?? $system->name_short;
    $consoleTooltipLabel = $label ?? $system->name;
}
else {
    $gameSystemIconSrc = getSystemIconUrl(0);
    $displayLabel = $label;
    $consoleTooltipLabel = $label;
}

$targetUser = request('user');
$cellUrls = [
    'totals' => route('user.completion-progress', ['user' => $targetUser, 'filter[system]' => $consoleId]),
    'unfinished' => route('user.completion-progress', ['user' => $targetUser, 'filter[system]' => $consoleId, 'filter[status]' => 'unawarded']),
    'any-beaten' => route('user.completion-progress', ['user' => $targetUser, 'filter[system]' => $consoleId, 'filter[status]' => 'any-beaten']),
    'any-mastery' => route('user.completion-progress', ['user' => $targetUser, 'filter[system]' => $consoleId, 'filter[status]' => 'gte-completed']),
];
?>

<li class="progression-status-row">
    <a
        href="{{ $cellUrls['totals'] }}"
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
        :href="$cellUrls['unfinished']"
        :widthMode="$widthMode"
        :cellGamesCounts="[$unfinishedCount]"
        :totalGamesCount="$totalGamesCount"
    >
        {{ $unfinishedCount }}
    </x-user.progression-status.list-item-cell-link>

    <x-user.progression-status.list-item-cell-link
        cellType="beaten"
        :href="$cellUrls['any-beaten']"
        :widthMode="$widthMode"
        :cellGamesCounts="[$beatenSoftcoreCount, $beatenHardcoreCount]"
        :totalGamesCount="$totalGamesCount"
    >
        @if ($beatenSoftcoreCount > 0)
            <div class="tally text-zinc-400 light:text-zinc-600 group-hover:text-link-hover">
                <div class="dot border border-zinc-400 light:border-zinc-600 group-hover:border-link-hover"></div>
                {{ $beatenSoftcoreCount }}
            </div>
        @endif

        @if ($beatenHardcoreCount > 0 || !$beatenSoftcoreCount)
            <div class="tally group-hover:text-link-hover">
                <div class="dot bg-zinc-300 light:bg-zinc-500 group-hover:bg-link-hover"></div>
                {{ $beatenHardcoreCount }}
            </div>
        @endif
    </x-user.progression-status.list-item-cell-link>

    <x-user.progression-status.list-item-cell-link
        cellType="mastered"
        :href="$cellUrls['any-mastery']"
        :widthMode="$widthMode"
        :cellGamesCounts="[$completedCount, $masteredCount]"
        :totalGamesCount="$totalGamesCount"
    >
        @if ($completedCount > 0)
            <div class="tally text-yellow-600 group-hover:text-link-hover">
                <div class="dot border border-yellow-600 group-hover:border-link-hover"></div>
                {{ $completedCount }}
            </div>
        @endif

        @if ($masteredCount > 0 || !$completedCount)
            <div class="tally group-hover:text-link-hover">
                <div class="dot bg-[gold] light:bg-yellow-600 group-hover:bg-link-hover"></div>
                {{ $masteredCount }}
            </div>
        @endif
    </x-user.progression-status.list-item-cell-link>
</li>
