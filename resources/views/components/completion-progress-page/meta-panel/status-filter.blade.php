@props([
    'selectedStatus' => '',
])

<?php
$statuses = [
    'all' => 'All games',
    'unawarded' => 'Unfinished',
    'eq-beaten-softcore' => 'Beaten (casual)',
    'eq-beaten-hardcore' => 'Beaten',
    'eq-completed' => 'Completed',
    'eq-mastered' => 'Mastered',
    'any-beaten' => 'Beaten, either hardcore or casual',
    'gte-completed' => 'Completed or mastered',
    'missing-unlocks' => 'Games without 100% completion',
    'awarded' => 'Games with any award',
    'eq-revised' => 'Games with awards for revised sets',
    'gte-beaten-softcore' => 'Games with casual-mode awards',
    'gte-beaten-hardcore' => 'Games with hardcore awards',
    'any-softcore' => 'Games with any casual progress',
    'any-hardcore' => 'Games with any hardcore progress',
];
?>

<label class="text-xs font-bold" for="filter-by-award-status">Status</label>
<select
    id="filter-by-award-status"
    class="w-full sm:max-w-[340px]"
    onchange="handleStatusChanged(event)"
    autocomplete="off"
>
    @foreach ($statuses as $value => $label)
        <option value="{{ $value }}" @if ($selectedStatus === $value) selected @endif>
            {{ $label }}
        </option>
    @endforeach
</select>
