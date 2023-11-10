@props([
    'selectedAwardType' => null,
])

<div class="grid gap-y-1 sm:pl-[30px] sm:pr-8">
    <label class="text-xs font-bold" for="filter-by-award-kind">Award kind</label>

    <select id="filter-by-award-kind" class="w-full" onchange="handleAwardTypeChanged(event)">
        <option value="all" @if (!$selectedAwardType) selected @endif>All</option>
        <option value="beaten-softcore" @if ($selectedAwardType === 'beaten-softcore') selected @endif>Beaten (softcore)</option>
        <option value="beaten-hardcore" @if ($selectedAwardType === 'beaten-hardcore') selected @endif>Beaten</option>
        <option value="completed" @if ($selectedAwardType === 'completed') selected @endif>Completed</option>
        <option value="mastered" @if ($selectedAwardType === 'mastered') selected @endif>Mastered</option>
    </select>
</div>