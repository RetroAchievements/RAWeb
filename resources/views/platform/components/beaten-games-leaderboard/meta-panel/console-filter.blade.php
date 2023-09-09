@props([
    'allSystems' => null,
    'selectedConsoleId' => 0,
])

<label class="text-xs font-bold" for="filter-by-console-select">System</label>
<select id="filter-by-console-id" class="w-full sm:max-w-[240px]" @change="handleConsoleChanged" autocomplete="off">
    <option @if (!$selectedConsoleId) selected @endif>All systems</option>

    @foreach ($allSystems as $system)
        @if (isValidConsoleId($system['ID']))
            <option
                value="{{ $system['ID'] }}"
                @if ($system['ID'] == $selectedConsoleId) selected @endif
            >
                {{ config('systems')[$system['ID']]['name']}}
            </option>
        @endif
    @endforeach 
</select>