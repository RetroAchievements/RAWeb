@props([
    'allSystems' => null,
    'selectedConsoleId' => 0,
])

<label class="text-xs font-bold" for="filter-by-console-select">System</label>
<select id="filter-by-console-id" class="w-full sm:max-w-[240px]" @change="handleConsoleChanged" autocomplete="off">
    <option @if (!$selectedConsoleId) selected @endif value="0">All systems</option>

    @foreach ($allSystems as $system)
        @if (isValidConsoleId($system['ID']) && $system['ID'] != 101)
            <option
                value="{{ $system['ID'] }}"
                @if ($system['ID'] == $selectedConsoleId) selected @endif
            >
                {{ $system->Name }}
            </option>
        @endif
    @endforeach 
</select>