<div class="mb-5 flex flex-col w-full">
    <div class="mb-1">
        <label for="active-players-search-input" class="sr-only">Search for players</label>
        <input class="w-full" id="active-players-search-input" x-model.debounce="searchTerm" placeholder="Search by player, game, console, or Rich Presence...">
    </div>

    <label class="flex items-center gap-x-1">
        <input type="checkbox" @change="toggleRememberFilter" x-model="shouldRememberFilters">
        Remember my filter
    </label>
</div>