@props([
    'minAllowedDate' => '2014-08-22',
    'selectedDate' => null,
])

<?php
$maxAllowedDate = date("Y-m-d");
?>

<form onsubmit="handleDateSubmitted(event)">
    <div class="grid gap-y-1 sm:pr-8">
        <label class="text-xs font-bold" for="filter-by-date">Date</label>
        
        <div class="sm:flex sm:items-center gap-x-1">
            <input
                id="filter-by-date"
                class="w-full sm:min-w-[130px] min-h-[30px]"
                type="date"
                min="{{ $minAllowedDate }}"
                max="{{ $maxAllowedDate }}"
                value="{{ $selectedDate }}"
            >

            <div class="flex w-full justify-end sm:block mt-1 sm:mt-0">
                <button class="btn h-full">Go to Date</button>
            </div>
        </div>
    </div>
</form>
