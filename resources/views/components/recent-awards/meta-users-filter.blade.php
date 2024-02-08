@props([
    'selectedUsers' => 'all',
])

<?php
$radioName = "visible-users";
?>

<div class="grid gap-y-1 sm:px-8 sm:pr-4 md:pr-8">
    <label class="text-xs font-bold sm:-mb-6">Users</label>
    <div class="flex gap-x-4" id="filter-by-users">
        <x-recent-awards.meta-filter-radio name="{{ $radioName }}" value="all" selectedValue="{{ $selectedUsers }}">
            All
        </x-recent-awards.meta-filter-radio>

        <x-recent-awards.meta-filter-radio name="{{ $radioName }}" value="followed" selectedValue="{{ $selectedUsers }}">
            Followed
        </x-recent-awards.meta-filter-radio>
    </div>
</div>