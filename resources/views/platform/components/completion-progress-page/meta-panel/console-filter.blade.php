@props([
    'availableConsoleIds' => [],
    'selectedConsoleId' => null,
])

<?php
// Sort the console IDs based on their names.
usort($availableConsoleIds, function ($a, $b) {
    return strcmp(config('systems')[$a]['name'], config('systems')[$b]['name']);
});
?>

<label class="text-xs font-bold" for="filter-by-console-select">Console</label>
<select
    id="filter-by-console-select"
    class="w-full sm:max-w-[240px]"
    onchange="handleConsoleChanged(event)"
    autocomplete="off"
>
    <option @if (!$selectedConsoleId) selected @endif value="0">All systems</option>

    @foreach ($availableConsoleIds as $consoleId)
        @if (isValidConsoleId($consoleId) && $consoleId != 101)
            @if ($selectedConsoleId == $consoleId)
                <option selected>{{ config('systems')[$consoleId]['name'] }}</option>
            @else
                <option value="{{ $consoleId }}">{{ config('systems')[$consoleId]['name'] }}</option>`
            @endif
        @endif
    @endforeach
</select>