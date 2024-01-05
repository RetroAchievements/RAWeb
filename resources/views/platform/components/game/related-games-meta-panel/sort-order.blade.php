@props([
    'selectedSortOrder' => 'console',
    'showTickets' => false,
])

<label class="text-xs font-bold" for="sort-order-field">Sort by</label>
<select
    id="sort-order-field"
    class="w-full sm:max-w-[240px]"
    onchange="handleSortOrderChanged(event)"
    autocomplete="off"
>
    <option value="title" @if ($selectedSortOrder === 'title') selected @endif>
        Title
    </option>

    <option value="-achievements" @if ($selectedSortOrder === '-achievements') selected @endif>
        Most achievements
    </option>

    <option value="-points" @if ($selectedSortOrder === '-points') selected @endif>
        Most points
    </option>

    <option value="-retroratio" @if ($selectedSortOrder === '-retroratio') selected @endif>
        Highest RetroRatio
    </option>

    <option value="-leaderboards" @if ($selectedSortOrder === '-leaderboards') selected @endif>
        Most leaderboards
    </option>

    <option value="-players" @if ($selectedSortOrder === '-players') selected @endif>
        Most players
    </option>

    @if ($showTickets)
    <option value="-tickets" @if ($selectedSortOrder === '-tickets') selected @endif>
        Most tickets
    </option>
    @endif

    <option value="-progress" @if ($selectedSortOrder === '-progress') selected @endif>
        Most progress
    </option>
</select>