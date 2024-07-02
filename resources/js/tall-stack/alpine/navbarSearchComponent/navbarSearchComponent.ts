import { autoUpdate, computePosition, type ReferenceElement } from '@floating-ui/dom';

import { fetcher } from '@/utils';

interface SearchResult {
  label: string;
  mylink: string;
}

interface NavbarSearchComponentProps {
  showSearchResults: boolean;
  searchText: string;
  results: SearchResult[];
  selectedIndex: number;
  activeDescendentId: string;
  optionsCount: number;
  init: (formEl: ReferenceElement, ulEl: HTMLElement) => void;
  getOptionId: (result: SearchResult) => string;
  handleClickSearchResult: (label: string, url: string) => void;
  handleEnter: () => void;
  handleEscape: () => void;
  handleUp: (e: Event) => void;
  handleDown: () => void;
  handleKeyUp: (e: KeyboardEvent) => Promise<void>;
  performSearch: () => Promise<void>;
}

export function navbarSearchComponent(): NavbarSearchComponentProps {
  return {
    showSearchResults: false,
    searchText: '',
    results: [],
    selectedIndex: -1,

    get activeDescendentId() {
      if (this.selectedIndex !== -1) {
        return this.getOptionId(this.results[this.selectedIndex]);
      }
      return '';
    },

    get optionsCount() {
      const searchBoxDropdownEl = document.querySelector('#search-listbox');
      return searchBoxDropdownEl && searchBoxDropdownEl.childNodes.length > 3
        ? searchBoxDropdownEl.childNodes.length - 3
        : 0;
    },

    init(formEl?: ReferenceElement, ulEl?: HTMLElement) {
      if (!formEl || !ulEl) return;
      autoUpdate(formEl, ulEl, () => {
        computePosition(formEl, ulEl, {
          placement: 'bottom-start',
        }).then(({ x, y }) => {
          Object.assign(ulEl.style, {
            left: `${x}px`,
            top: `${y}px`,
          });
        });
      });
    },

    // Generates the option id based off of the link
    // '/game/234' -> 'game-234'
    getOptionId(result: SearchResult) {
      return result.mylink.slice(1).replace('/', '-');
    },

    handleClickSearchResult(label: string, url: string) {
      this.searchText = label;

      const inputEl = document.querySelector<HTMLInputElement>('.searchboxinput');
      if (inputEl) {
        inputEl.value = label;
        this.showSearchResults = false;
        inputEl.scrollLeft = inputEl.scrollWidth * -1;

        // Force the URL to change, even if the cursor misses an anchor tag.
        window.location.href = url;
      }
    },

    handleEnter() {
      if (this.showSearchResults) {
        this.showSearchResults = false;

        if (this.selectedIndex !== -1) {
          this.searchText = this.results[this.selectedIndex].label;
          window.location.href = this.results[this.selectedIndex].mylink;
        }
      } else {
        document.querySelector<HTMLFormElement>('.searchbox-top')?.requestSubmit();
      }
    },

    handleEscape() {
      if (this.showSearchResults) {
        this.showSearchResults = false;
      } else {
        this.searchText = '';
        this.results = [];
        this.selectedIndex = -1;
      }
    },

    handleUp(e) {
      e.preventDefault();
      if (this.showSearchResults) {
        if (this.selectedIndex === -1 || this.selectedIndex === 0) {
          this.selectedIndex = this.optionsCount - 1;
        } else {
          this.selectedIndex--;
        }
        this.searchText = this.results[this.selectedIndex].label;
      }
    },

    handleDown() {
      if (this.showSearchResults) {
        if (this.selectedIndex === -1 || this.selectedIndex === this.optionsCount - 1) {
          this.selectedIndex = 0;
        } else {
          this.selectedIndex++;
        }
        this.searchText = this.results[this.selectedIndex].label;
      } else if (this.results.length) {
        this.showSearchResults = true;
      }
    },

    async handleKeyUp(e: KeyboardEvent) {
      const ignoredKeys = [
        'ArrowUp',
        'ArrowDown',
        'ArrowLeft',
        'ArrowRight',
        'Enter',
        'Escape',
        'Shift',
        'Control',
        'Alt',
        'Meta',
      ];

      if (ignoredKeys.includes(e.key)) {
        return;
      }

      if (this.searchText.length < 2) {
        this.showSearchResults = false;
        this.selectedIndex = -1;
        return;
      }

      await this.performSearch();
    },

    async performSearch() {
      this.selectedIndex = -1;

      const formData = new FormData();
      formData.append('term', this.searchText);

      const response = await fetcher<SearchResult[]>('/request/search.php', {
        method: 'POST',
        body: `term=${this.searchText}`,
      });

      this.results = response;
      this.showSearchResults = this.results.length > 0;
    },
  };
}
