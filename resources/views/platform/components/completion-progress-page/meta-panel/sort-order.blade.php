@props([
    'selectedSortOrder' => 'unlock_date',
])

<label class="text-xs font-bold" for="sort-order-field">Sort by</label>
<select
    id="sort-order-field"
    class="w-full sm:max-w-[240px]"
    onchange="handleSortOrderChanged(event)"
    autocomplete="off"
>
    <option value="unlock_date" @if ($selectedSortOrder === 'unlock_date') selected @endif>
        Newest unlock
    </option>

    <option value="-unlock_date" @if ($selectedSortOrder === '-unlock_date') selected @endif>
        Oldest unlock 
    </option>

    <option value="pct_won" @if ($selectedSortOrder === 'pct_won') selected @endif>
        Most achievements won
    </option>

    <option value="-pct_won" @if ($selectedSortOrder === '-pct_won') selected @endif>
        Fewest achievements won
    </option>
</select>