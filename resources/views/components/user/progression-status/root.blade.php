<?php
$widthsPreference = request()->cookie('progression_status_widths_preference');
$widthMode = $widthsPreference;
if ($widthMode !== 'equal' && $widthMode !== 'dynamic') {
    $widthMode = 'equal';
}
?>

<h2 class="text-h4 !leading-none mb-2">Progression Status</h2>

<div x-data="{ widthMode: '{{ $widthMode }}' }">
    <div class="flex flex-col-reverse gap-y-2 sm:gap-y-0 sm:flex-row sm:justify-between w-full mb-2">
        <x-user.progression-status.legend
            :totalBeatenHardcoreCount="$totalCounts['beatenHardcore']"
            :totalBeatenSoftcoreCount="$totalCounts['beatenSoftcore']"
            :totalCompletedCount="$totalCounts['completed']"
            :totalMasteredCount="$totalCounts['mastered']"
        />

        <label class="flex items-center gap-x-1 select-none cursor-pointer text-xs transition sm:-mt-[2px] md:active:scale-95">
            <input
                type="checkbox"
                autocomplete="off"
                @if ($widthMode === 'dynamic') checked @endif
                @change="newWidthMode = widthMode === 'equal' ? 'dynamic' : 'equal';  widthMode = newWidthMode;  setCookie('progression_status_widths_preference', newWidthMode);"
                class="cursor-pointer"
            >
            Dynamic widths
        </label>
    </div>

    @if (count($systemProgress) > 2)
        <div class="mb-4">
            <x-user.progression-status.console-progression-list-item
                label="Total"
                :unfinishedCount="$totalCounts['unfinished']"
                :beatenSoftcoreCount="$totalCounts['beatenSoftcore']"
                :beatenHardcoreCount="$totalCounts['beatenHardcore']"
                :completedCount="$totalCounts['completed']"
                :masteredCount="$totalCounts['mastered']"
                :systems="$systems"
            />
        </div>
    @endif

    @if ($topSystem)
        <div class="mb-1.5">
            <p class="text-xs">Most Recent</p>
            <x-user.progression-status.console-progression-list-item
                :consoleId="$topSystem"
                :unfinishedCount="$systemProgress[$topSystem]['unfinishedCount'] ?? 1"
                :beatenSoftcoreCount="$systemProgress[$topSystem]['beatenSoftcoreCount'] ?? 0"
                :beatenHardcoreCount="$systemProgress[$topSystem]['beatenHardcoreCount'] ?? 0"
                :completedCount="$systemProgress[$topSystem]['completedCount'] ?? 0"
                :masteredCount="$systemProgress[$topSystem]['masteredCount'] ?? 0"
                :systems="$systems"
            />
        </div>
    @endif

    {{-- These items are hidden by default. --}}
    @if (count($systemProgress) > 1)
        <ol>
            <x-user.progression-status.hidden-consoles totalConsoleCount="{{ count($systemProgress) }}">
                <p class="text-xs mt-3 -mb-1.5 select-none">Sorted by Most Games Played</p>

                @foreach ($systemProgress as $systemId => $progress)
                    @if ($systemId != $topSystem)
                        <x-user.progression-status.console-progression-list-item
                            :consoleId="$systemId"
                            :unfinishedCount="$progress['unfinishedCount']"
                            :beatenSoftcoreCount="$progress['beatenSoftcoreCount']"
                            :beatenHardcoreCount="$progress['beatenHardcoreCount']"
                            :completedCount="$progress['completedCount']"
                            :masteredCount="$progress['masteredCount']"
                            :systems="$systems"
                        />
                    @endif
                @endforeach
            </x-user.progression-status.hidden-consoles>
        </ol>
    @endif
</div>
