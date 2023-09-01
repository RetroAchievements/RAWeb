<h4 class="!leading-none mb-2">Progression Status</h4>

<div x-data="{ widthMode: 'equal' }">
    <div class="flex flex-col sm:flex-row sm:justify-between w-full mb-2">
        <div class="{{ ($totalCompletedCount || $totalBeatenSoftcoreCount) ? 'grid grid-cols-2' : 'flex' }} sm:flex gap-x-4 [&>div]:flex [&>div]:items-center [&>div]:gap-x-1">
            <div class="order-1">
                <div class="rounded-full w-2 h-2 bg-zinc-500"></div>
                <p class="text-zinc-500 font-bold text-xs">Unfinished</p>
            </div>

            @if ($totalBeatenSoftcoreCount > 0)
                <div class="order-3 sm:order-2">
                    <div class="rounded-full w-2 h-2 border border-zinc-400"></div>
                    <p class="text-zinc-400 font-bold text-xs">Beaten Softcore</p>
                </div>
            @endif

            @if ($totalBeatenHardcoreCount > 0 || ($totalBeatenHardcoreCount === 0 && $totalBeatenSoftcoreCount === 0))
                <div class="order-5 sm:order-3">
                    <div class="rounded-full w-2 h-2 bg-zinc-300"></div>
                    <p class="text-zinc-300 font-bold text-xs">Beaten</p>
                </div>
            @endif

            @if ($totalCompletedCount > 0)
                <div class="order-2 sm:order-4">
                    <div class="rounded-full w-2 h-2 border border-yellow-600"></div>
                    <p class="text-yellow-600 font-bold text-xs">Completed</p>
                </div>
            @endif

            @if ($totalMasteredCount > 0 || ($totalMasteredCount === 0 && $totalCompletedCount === 0))
                <div class="order-4 sm:order-5">
                    <div class="rounded-full w-2 h-2 bg-yellow-600"></div>
                    <p class="text-[gold] font-bold text-xs">Mastered</p>
                </div>
            @endif
        </div>

        <div class="hidden sm:flex items-center gap-x-1 select-none cursor-pointer text-xs">
            <input
                id="toggle-row-width-mode-checkbox"
                type="checkbox"
                autocomplete="off" 
                @change="widthMode = widthMode === 'equal' ? 'dynamic' : 'equal'"
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
                :unfinishedCount="$consoleProgress[$topConsole]['unfinishedCount']"
                :beatenSoftcoreCount="$consoleProgress[$topConsole]['beatenSoftcoreCount']"
                :beatenHardcoreCount="$consoleProgress[$topConsole]['beatenHardcoreCount']"
                :completedCount="$consoleProgress[$topConsole]['completedCount']"
                :masteredCount="$consoleProgress[$topConsole]['masteredCount']"
            />
        </div>
    @endif

    <!-- These items are hidden by default. -->
    @if (count($consoleProgress) > 4 || (count($consoleProgress) > 3) && $topConsole)
        <ol>
            <x-user.progression-status.hidden-consoles totalConsoleCount="{{ count($consoleProgress) }}">
                <p class="text-xs mt-3 -mb-1.5">Sorted by Most Games Played</p>

                @foreach ($consoleProgress as $consoleId => $progress)
                    @if ($consoleId != $topConsole && $loop->index > 3)
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
