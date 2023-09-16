@props([
    'activePlayersCount' => 0,
    'initialActivePlayers' => [],
    'initialSearch' => null,
    'totalActivePlayers' => 0,
    'trendingGames' => [],
])

<script>
function activePlayersComponent() {
    const cookieName = 'active_players_search';

    return {
        canShowEmptyState: {{ $totalActivePlayers === 0 ? 'true' : 'false' }},
        hasFetchedFullList: false,
        hasSavedSearchInput: {{ $initialSearch ? 'true' : 'false' }},
        isMenuOpen: {{ $initialSearch ? 'true' : 'false' }},
        isRememberingSearch: {{ $initialSearch ? 'true' : 'false' }},
        lastUpdatedAtLabel: 'Last updated now',
        searchInput: "{{ $initialSearch ?? '' }}",

        init() {
            const FIVE_MINUTES = 300000; // milliseconds
            setInterval(() => {
                this.refreshActivePlayers(this.hasFetchedFullList);
                this.lastUpdatedAtLabel = this.buildLastUpdatedAtLabel();
            }, FIVE_MINUTES)

            this.lastUpdatedAtLabel = this.buildLastUpdatedAtLabel();

            // Migrate saved searches from the old Knockout.js Active Players widget
            // to this new Alpine.js component.
            if (localStorage.getItem('rememberFilters') === 'true' && localStorage.getItem('filterString')) {
                const legacyFilterString = localStorage.getItem('filterString');
                
                window.setCookie(cookieName, legacyFilterString);
                this.searchInput = legacyFilterString;
                this.isRememberingSearch = true;
                this.isMenuOpen = true;
                this.hasSavedSearchInput = true;
                
                localStorage.removeItem('rememberFilters');
                localStorage.removeItem('filterString');

                this.refreshActivePlayers();
            }

            this.$watch('searchInput', (newValue) => {
                if (this.searchInput.length >= 3 && this.isRememberingSearch) {
                    window.setCookie(cookieName, this.searchInput);
                } else if (this.searchInput.length === 0 && this.isRememberingSearch) {
                    window.deleteCookie(cookieName);
                }

                if (this.searchInput.length < 3) {
                    this.hasFetchedFullList = false;
                }

                this.refreshActivePlayers();
            });
        },

        buildLastUpdatedAtLabel() {
            const now = new Date();
            let hours = now.getHours();
            const minutes = now.getMinutes();
            const amPm = hours >= 12 ? 'pm' : 'am';

            // Convert hours to 12-hour format.
            hours = hours % 12;

            // 12-hour format should treat 0 as 12.
            hours = hours ?? 12;

            // Pad the minutes with a 0 if necessary.
            const minutesLabel = minutes < 10 ? `0${minutes}` : minutes;

            return `Last updated at ${hours}:${minutesLabel}${amPm}`;
        },

        handleScroll() {
            if (!this.searchInput) {
                const scrollAreaEl = document.getElementById('active-players-scroll-area');

                if (scrollAreaEl.scrollTop > 600 && !this.hasFetchedFullList) {
                    this.hasFetchedFullList = true;
                    this.refreshActivePlayers(true);
                }
            }
        },

        async refreshActivePlayers(getFullList = false) {
            let requestUrl = '/request/user/list-currently-active.php';
            const params = new URLSearchParams();

            if (this.searchInput.length >= 3) {
                params.set('search', this.searchInput);
            }

            if (getFullList) {
                params.set('all', true);
            }

            if (params.size > 0) {
                requestUrl = `${requestUrl}?${params}`;
            }

            const activePlayers = await window.fetcher(
                requestUrl,
                { method: 'POST' }
            );

            this.updateCounts(activePlayers.count, activePlayers.total);
            this.updateTable(activePlayers.records);
        },

        toggleSavedSearch() {
            this.isRememberingSearch = !this.isRememberingSearch;

            if (this.hasSavedSearchInput) {
                window.deleteCookie(cookieName);
                this.searchInput = '';
            } else if (this.searchInput.length >= 3) {
                window.setCookie(cookieName, this.searchInput);
            }
        },

        /**
         * @param {number} viewing
         * @param {number} total
         */
        updateCounts(viewing, total) {
            const viewingCountEl = document.getElementById('active-players-viewing');
            const totalCountEl = document.getElementById('active-players-total');

            viewingCountEl.innerHTML = viewing.toLocaleString();
            totalCountEl.innerHTML = total.toLocaleString();

            this.canShowEmptyState = (viewing === 0 || total === 0);
        },

        /**
         * @param {unknown[]} newActivePlayers
         */
        updateTable(newActivePlayers) {
            const tbodyEl = document.getElementById('active-players-tbody');

            const fragment = document.createDocumentFragment();

            // Populate the new rows.
            newActivePlayers.forEach(player => {
                const tr = document.createElement('tr');

                const td1 = document.createElement('td');
                td1.setAttribute('width', '44');
                td1.innerHTML = `
                    <span class="inline whitespace-nowrap" x-data="tooltipComponent($el, { dynamicType: 'user', dynamicId: '${player.User}', dynamicContext: '' })" 
                          @mouseover="showTooltip($event)" 
                          @mouseleave="hideTooltip" 
                          @mousemove="trackMouseMovement($event)">
                        <a class="inline-block" href="/user/${player.User}">
                            <img loading="lazy" width="32" height="32" src="${mediaAssetUrl}/UserPic/${player.User}.png" alt="" class="badgeimg"> 
                        </a>
                    </span>`;
                
                const td2 = document.createElement('td');
                td2.setAttribute('width', '44');
                td2.innerHTML = `
                    <div x-data="tooltipComponent($el, {dynamicType: 'game', dynamicId: '${player.GameID}'})" 
                         @mouseover="showTooltip($event)" 
                         @mouseleave="hideTooltip" 
                         @mousemove="trackMouseMovement($event)">
                        <a href="/game/${player.GameID}">
                            <img src="${mediaAssetUrl}${player.GameIcon}" alt="${player.GameTitle} game badge" width="32" height="32" loading="lazy" decoding="async" class="badgeimg">
                        </a>
                    </div>`;
                
                const td3 = document.createElement('td');
                if (player.RichPresenceMsg.includes('Unknown macro')) {
                    td3.setAttribute('class', 'cursor-help');
                    td3.setAttribute('title', player.RichPresenceMsg);

                    td3.innerHTML = `⚠️ Playing ${player.GameTitle}`;
                } else {
                    td3.textContent = player.RichPresenceMsg;
                }

                tr.appendChild(td1);
                tr.appendChild(td2);
                tr.appendChild(td3);
                
                fragment.appendChild(tr);
            });

            // Clear existing rows.
            tbodyEl.innerHTML = '';

            // Append the fragment, which results in only a single re-render.
            tbodyEl.appendChild(fragment);
        }
    }
}
</script>

<section class="component" x-data="activePlayersComponent()">
    <h3>Active Players</h3>

    <div class="mb-2">
        <div class="flex w-full justify-between items-center">
            <x-active-players.active-players-count-label
                :activePlayersCount="$activePlayersCount"
                :totalActivePlayers="$totalActivePlayers"
            />

            <button
                class="btn transition-all duration-200 lg:active:scale-95 -mb-px"
                :class="{
                    '!border-b-transparent !rounded-b-none': isMenuOpen
                }"
                aria-label="Open search and filter menu"
                @click="isMenuOpen = !isMenuOpen"
            >
                <x-fas-bars />
            </button>
        </div>

        <x-active-players.active-players-search-filter-panel
            :initialSearch="$initialSearch"
        />
    </div>

    <div
        id="active-players-empty-state"
        @if ($activePlayersCount !== 0) class="hidden" @endif
        :class="{'!block': canShowEmptyState}"
    >
        <x-active-players.active-players-empty-state />
    </div>

    <div
        id="active-players-scroll-area"
        class="h-[325px] max-h-[325px] overflow-y-auto border border-embed-highlight rounded {{ $activePlayersCount === 0 ? 'hidden' : '' }}"
        @scroll="handleScroll()"
        x-show="!canShowEmptyState"
    >
        <x-active-players.active-players-table
            :activePlayers="$initialActivePlayers"
        />
    </div>

    <div class="mt-0.5 flex w-full justify-end">
        <p class="text-2xs" id="active-players-last-updated-text" x-text="lastUpdatedAtLabel">
            {{-- Use a placeholder time before hydration kicks in. --}}
            Last updated at 9:00am
        </p>
    </div>

    @if (!empty($trendingGames) && count($trendingGames) === 4)
        <x-active-players.active-players-trending-games
            :trendingGames="$trendingGames"
        />
    @endif
</section>
