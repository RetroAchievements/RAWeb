<script>
const rememberFiltersKey = 'rememberFilters';
const filterStringKey = 'filterString';

function activePlayersComponent(activePlayers) {
    return {
        isSearchMenuOpen: false,
        searchTerm: '',
        originalPlayerCount: activePlayers.length,
        filteredPlayerCount: activePlayers.length,
        shouldRememberFilters: localStorage.getItem(rememberFiltersKey) === 'true',
        activePlayers,

        filterPlayers() {
            let count = 0; // reset filtered count

            const lowerSearchTerm = (this.searchTerm ?? '').toLowerCase();
            this.$refs.playerTable.querySelectorAll('tr').forEach(row => {
                const content = row.textContent ? row.textContent.toLowerCase() : '';
                if (content.includes(lowerSearchTerm)) {
                    row.style.display = "";
                    count += 1;
                } else {
                    row.style.display = "none";
                }

                this.filteredPlayerCount = count;
            });
        },

        persistFilterString() {
            localStorage.setItem(filterStringKey, this.searchTerm);
        },

        toggleRememberFilter(event) {
            if (event.target.checked) {
                localStorage.setItem(rememberFiltersKey, 'true');
                this.persistFilterString();
            } else {
                localStorage.removeItem(rememberFiltersKey);
                localStorage.removeItem(filterStringKey);
            }
        },

        init() {
            this.$watch('searchTerm', (value) => {
                if (this.shouldRememberFilters) {
                    this.persistFilterString();
                }
            });

            if (this.shouldRememberFilters) {
                this.searchTerm = localStorage.getItem(filterStringKey);
                this.filterPlayers();
            }
        }
    };
}
</script>

<div 
    class="component" 
    wire:poll.5.minutes="updateActivePlayers"
    x-data="activePlayersComponent({{ json_encode($activePlayers) }})"
>
    <h3>Active Players</h3>
    <div class="flex justify-between mb-2">
        @if (!$hasError)
            <p x-show="searchTerm.length === 0">
                There are <span class="font-bold">{{ localized_number(count($activePlayers )) }}</span> active players.
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
    </div>

    <template x-if="isSearchMenuOpen">
        <div class="mb-5 flex flex-col w-full">
            <div class="mb-1">
                <label for="active-players-search-input" class="sr-only">Search for players</label>
                <input class="w-full" id="active-players-search-input" x-model="searchTerm" @input="filterPlayers" placeholder="Search by player, game, console, or Rich Presence...">
            </div>

            <label class="flex items-center gap-x-1">
                <input type="checkbox" @change="toggleRememberFilter" x-model="shouldRememberFilters">
                Remember my filter
            </label>
        </div>
    </template>

    <div class="min-h-[54px] h-80 max-h-80 overflow-y-auto mb-2">
    @if ($hasError)
        <div class="flex w-full h-full justify-center items-center">
            <p>An error has occurred while loading players.</p>
        </div>
    @else
        <table class="table-highlight">
            <tbody x-ref="playerTable">
                @foreach ($activePlayers as $activePlayer)
                    <tr>
                        <td class="w-[52px]">
                            {!! userAvatar($activePlayer['User'], iconSize: 32, label: false) !!}
                            <span class="hidden">{{ $activePlayer['User'] }}
                        </td>

                        <td class="w-[52px]">
                            {!! gameAvatar(['ID' => $activePlayer['GameID'], 'ImageIcon' => $activePlayer['GameIcon']], iconSize: 32, label: false) !!}
                            <span class="hidden">{{ $activePlayer['GameTitle'] }}</span>
                        </td>

                        <td>{{ $activePlayer['RichPresenceMsg'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
    </div>


    <p
        class="w-full flex justify-end text-2xs"
        x-data="{ updated: new Date() }"
        x-text="'Last updated at ' + new Intl.DateTimeFormat(navigator.language || navigator.languages[0], { timeStyle: 'short' }).format(updated)"
        x-on:poll.window="updated = new Date()"
    >
        Last updated at X:XX XX
    </p>
</div>