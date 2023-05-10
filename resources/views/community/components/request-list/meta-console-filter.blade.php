<label class="text-xs font-bold" for="filter-by-console-select">Console</label>
<select id="filter-by-console-id" class="w-full sm:max-w-[240px]" @change="handleConsoleChanged">
    @if ($selectedConsoleId === null)
        <option selected>Only supported systems</option>
    @else 
        <option value=''>Only supported systems</option>
    @endif

    @if ($selectedConsoleId == -1)
        <option selected>All systems</option>
    @else
        <option value="-1">All systems</option>
    @endif

    @foreach ($consoles as $console)
        @if ($selectedConsoleId == $console['ID'])
            <option selected>{{ e($console->Name) }}</option>
        @else
            <option value="{{ $console['ID'] }}">{{ e($console->Name) }}</option>
        @endif
    @endforeach
</select>