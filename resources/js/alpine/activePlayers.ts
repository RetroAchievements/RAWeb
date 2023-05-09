import type { AlpineComponent } from 'alpinejs';

const rememberFiltersKey = 'rememberFilters';
const filterStringKey = 'filterString';

interface ActivePlayerEntity {
  User: string;
  GameTitle: string;
  RichPresenceMsg: string;
}

export function activePlayers(activePlayers: ActivePlayerEntity[]): AlpineComponent {
  return {
    activePlayers,
    isSearchMenuOpen: false,
    searchTerm: '',
    originalPlayerCount: activePlayers.length,
    filteredPlayerCount: activePlayers.length,
    shouldRememberFilters: localStorage.getItem(rememberFiltersKey) === 'true',

    filteredPlayers: function () {
      const lowerSearchTerm = (this.searchTerm ?? '').toLowerCase();
      const filtered = this.activePlayers.filter(
        // prettier-ignore
        (activePlayer: ActivePlayerEntity) => activePlayer.User.toLowerCase().includes(lowerSearchTerm)
          || activePlayer.GameTitle.toLowerCase().includes(lowerSearchTerm)
          || activePlayer.RichPresenceMsg.toLowerCase().includes(lowerSearchTerm),
      );

      this.filteredPlayerCount = filtered.length;

      return filtered;
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
      }
    },
  };
}
