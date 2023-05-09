@if (!$hasError)
    <p x-show="searchTerm.length === 0">
        There are <span class="font-bold">{{ $activePlayersCount }}</span> active players.
    </p>

    <p x-cloak x-show="searchTerm.length > 0">
        There are
        <span class="font-bold" x-text="filteredPlayerCount"></span>
        filtered active players (out of <span class="font-bold" x-text="originalPlayerCount"></span>)
        active players.
    </p>

    <button 
        @click="isSearchMenuOpen = !isSearchMenuOpen"
        :aria-label="isSearchMenuOpen ? 'Close search menu' : 'Open search menu'"
    >
        <template x-if="isSearchMenuOpen">
            <x-pixelarticons-close />
        </template>
        <template x-if="!isSearchMenuOpen">
            <x-pixelarticons-menu />
        </template>
    </button>
@endif