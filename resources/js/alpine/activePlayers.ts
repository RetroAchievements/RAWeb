import type { AlpineComponent } from 'alpinejs';

const rememberFiltersKey = 'rememberFilters';
const filterStringKey = 'filterString';

export function activePlayers(activePlayers: unknown[]): AlpineComponent {
  return {
    activePlayers,
    isSearchMenuOpen: false,
    searchTerm: '',
    originalPlayerCount: activePlayers.length,
    filteredPlayerCount: activePlayers.length,
    shouldRememberFilters: localStorage.getItem(rememberFiltersKey) === 'true',

    filterPlayers() {
      let count = 0; // reset filtered count

      const lowerSearchTerm = (this.searchTerm ?? '').toLowerCase();
      this.$refs.playerTable.querySelectorAll('tr').forEach((row) => {
        const content = row.textContent ? row.textContent.toLowerCase() : '';
        if (content.includes(lowerSearchTerm)) {
          row.style.display = '';
          count += 1;
        } else {
          row.style.display = 'none';
        }

        this.filteredPlayerCount = count;
      });
    },

    buildLastUpdatedTime(): string {
      return (
        // prettier-ignore
        'Last updated at '
        + new Intl.DateTimeFormat(navigator.language || navigator.languages[0], { timeStyle: 'short' }).format(
          this.updated,
        )
      );
    },

    persistFilterString() {
      localStorage.setItem(filterStringKey, this.searchTerm);
    },

    toggleRememberFilter(event: MouseEvent) {
      const targetEl = event.target as HTMLInputElement;
      if (targetEl.checked) {
        localStorage.setItem(rememberFiltersKey, 'true');
        this.persistFilterString();
      } else {
        localStorage.removeItem(rememberFiltersKey);
        localStorage.removeItem(filterStringKey);
      }
    },

    init() {
      this.$watch('searchTerm', () => {
        if (this.shouldRememberFilters) {
          this.persistFilterString();
        }
      });

      if (this.shouldRememberFilters) {
        this.searchTerm = localStorage.getItem(filterStringKey);
        this.filterPlayers();
      }
    },
  };
}
