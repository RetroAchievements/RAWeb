@props([
    'selectedStatus' => '',
])

<?php
$statuses = [
    'all' => 'All games',
    'unawarded' => 'Unfinished',
    'eq-beaten-softcore' => 'Beaten (softcore)',
    'eq-beaten-hardcore' => 'Beaten',
    'eq-completed' => 'Completed',
    'eq-mastered' => 'Mastered',
    'any-beaten' => 'Beaten, either hardcore or softcore',
    'gte-completed' => 'Completed or mastered',
    'awarded' => 'Games with any award',
    'eq-revised' => 'Games with awards for revised sets',
    'gte-beaten-softcore' => 'Games with softcore awards',
    'gte-beaten-hardcore' => 'Games with hardcore awards',
    'any-softcore' => 'Games with any softcore progress',
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
