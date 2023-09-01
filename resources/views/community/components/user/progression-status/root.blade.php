<?php
$widthsPreference = request()->cookie('progression_status_widths_preference');
$widthMode = $widthsPreference;
if ($widthMode !== 'equal' && $widthMode !== 'dynamic') {
    $widthMode = 'equal';
}
?>

<h4 class="!leading-none mb-2">Progression Status</h4>

<div x-data="{ widthMode: '{{ $widthMode }}' }">
    <div class="flex flex-col sm:flex-row sm:justify-between w-full mb-2">
        <x-user.progression-status.legend
            :totalBeatenHardcoreCount="$totalBeatenHardcoreCount"
            :totalBeatenSoftcoreCount="$totalBeatenSoftcoreCount"
            :totalCompletedCount="$totalCompletedCount"
            :totalMasteredCount="$totalMasteredCount"
        />

        <div class="hidden sm:flex items-center gap-x-1 select-none cursor-pointer text-xs transition md:active:scale-95">
            <input
                id="toggle-row-width-mode-checkbox"
                type="checkbox"
                autocomplete="off"
                @if ($widthMode === 'dynamic') checked @endif
                @change="newWidthMode = widthMode === 'equal' ? 'dynamic' : 'equal';  widthMode = newWidthMode;  setCookie('progression_status_widths_preference', newWidthMode);"
                class="cursor-pointer"
            >
            <label for="toggle-row-width-mode-checkbox" class="cursor-pointer">Dynamic widths</label>
        </div>
    </div>

    @if (count($consoleProgress) > 2)
        <div class="mb-4">
            <x-user.progression-status.console-progression-list-item
                label="Total"
                :unfinishedCount="$totalUnfinishedCount"
                :beatenSoftcoreCount="$totalBeatenSoftcoreCount"
                :beatenHardcoreCount="$totalBeatenHardcoreCount"
                :completedCount="$totalCompletedCount"
                :masteredCount="$totalMasteredCount"
            />
        </div>
    @endif

    @if ($topConsole)
        <div class="mb-1.5">
            <p class="text-xs">Most Recent</p>
            <x-user.progression-status.console-progression-list-item
                :consoleId="$topConsole"
                :unfinishedCount="$consoleProgress[$topConsole]['unfinishedCount'] ?? 1"
                :beatenSoftcoreCount="$consoleProgress[$topConsole]['beatenSoftcoreCount'] ?? 0"
                :beatenHardcoreCount="$consoleProgress[$topConsole]['beatenHardcoreCount'] ?? 0"
                :completedCount="$consoleProgress[$topConsole]['completedCount'] ?? 0"
                :masteredCount="$consoleProgress[$topConsole]['masteredCount'] ?? 0"
            />
        </div>
    @endif

    {{-- These items are hidden by default. --}}
    {{-- TODO: Convert this to LiveWire so these rows aren't in the DOM until expanded by the user. --}}
    @if (count($consoleProgress) > 1)
        <ol>
            <x-user.progression-status.hidden-consoles totalConsoleCount="{{ count($consoleProgress) }}">
                <p class="text-xs mt-3 -mb-1.5">Sorted by Most Games Played</p>

                @foreach ($consoleProgress as $consoleId => $progress)
                    @if ($consoleId != $topConsole)
                        <x-user.progression-status.console-progression-list-item
                            :consoleId="$consoleId"
                            :unfinishedCount="$progress['unfinishedCount']"
                            :beatenSoftcoreCount="$progress['beatenSoftcoreCount']"
                            :beatenHardcoreCount="$progress['beatenHardcoreCount']"
                            :completedCount="$progress['completedCount']"
                            :masteredCount="$progress['masteredCount']"
                        />
                    @endif
                @endforeach
            </x-user.progression-status.hidden-consoles>
        </ol>
    @endif
</div>
