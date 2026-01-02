@props([
    'allSystems' => null,
    'selectedConsoleId' => 0,
])

<label class="text-xs font-bold" for="filter-by-console-select">System</label>
<select id="filter-by-console-id" class="w-full sm:max-w-[240px]" @change="handleConsoleChanged" autocomplete="off">
    <option @if (!$selectedConsoleId) selected @endif value="0">All systems</option>

    @foreach ($allSystems as $system)
        <option
            value="{{ $system['id'] }}"
            @if ($system['id'] == $selectedConsoleId) selected @endif
        >
            {{ $system->name }}
        </option>
    @endforeach 
</select>