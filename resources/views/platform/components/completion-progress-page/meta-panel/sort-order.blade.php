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
        Unlock Date: Newest
    </option>

    <option value="-unlock_date" @if ($selectedSortOrder === '-unlock_date') selected @endif>
        Unlock Date: Oldest
    </option>

    <option value="pct_won" @if ($selectedSortOrder === 'pct_won') selected @endif>
        Achievements Won: Most
    </option>

    <option value="-pct_won" @if ($selectedSortOrder === '-pct_won') selected @endif>
        Achievements Won: Least
    </option>

    <option value="game_title" @if ($selectedSortOrder === 'game_title') selected @endif>
        Game Title: A - Z
    </option>

    <option value="-game_title" @if ($selectedSortOrder === '-game_title') selected @endif>
        Game Title: Z - A
    </option>
</select>