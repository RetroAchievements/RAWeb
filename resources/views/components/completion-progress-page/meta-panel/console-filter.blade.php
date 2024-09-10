@props([
    'availableConsoleIds' => [],
    'selectedConsoleId' => null,
])

<?php

use App\Models\System;

$systems = System::active()
    ->whereIn('ID', $availableConsoleIds)
    ->orderBy('name')
    ->get();
?>

<label class="text-xs font-bold" for="filter-by-console-select">System</label>
<select
    id="filter-by-console-select"
    class="w-full sm:max-w-[240px]"
    onchange="handleSystemChanged(event)"
    autocomplete="off"
>
    <option @if (!$selectedConsoleId) selected @endif value="0">All systems</option>

    @foreach ($systems as $system)
        @if ($selectedConsoleId == $system->id)
            <option selected>{{ $system->name }}</option>
        @else
            <option value="{{ $system->id }}">{{ $system->name }}</option>`
        @endif
    @endforeach
</select>