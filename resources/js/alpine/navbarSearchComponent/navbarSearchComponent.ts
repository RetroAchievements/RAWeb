import { fetcher } from '@/utils';
import { computePosition, autoUpdate, ReferenceElement } from '@floating-ui/dom';

interface SearchResult {
  label: string,
  mylink: string,
}

interface NavbarSearchComponentProps {
  showSearchResults: boolean;
  searchText: string;
  results: SearchResult[];
  selectedIndex: number;
  activeDescendentId: string;
  optionsCount: number;
  init: (formEl: ReferenceElement, ulEl: HTMLElement) => void;
  handleClickSearchResult: (label: string) => void;
  handleEnter: () => void;
  handleEscape: () => void;
  handleUp: () => void;
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
        return this.results[this.selectedIndex - 2].mylink.slice(1).replace('/', '-');
      }
      return '';
    },

    get optionsCount() {
      const searchBoxDropdownEl = document.querySelector('#search-listbox');
      return searchBoxDropdownEl ? searchBoxDropdownEl.childNodes.length : 0;
    },

    init(formEl: ReferenceElement, ulEl: HTMLElement) {
      if (!formEl || !ulEl) return;
      autoUpdate(formEl, ulEl, () => {
        computePosition(formEl, ulEl, {
          placement: 'bottom-start'
        }).then(({
          x,
          y
        }) => {
          Object.assign(ulEl.style, {
            left: `${x}px`,
            top: `${y}px`,
          });
        });
      });
    },

    handleClickSearchResult(label: string) {
      const inputEl = document.querySelector<HTMLInputElement>('.searchboxinput');
      this.searchText = label;
      if (inputEl) {
        inputEl.value = label;
        this.showSearchResults = false;
        inputEl.scrollLeft = inputEl.scrollWidth * -1;
      }
    },

    handleEnter() {
      if (this.showSearchResults) {
        this.showSearchResults = false;

        if (this.selectedIndex !== -1) {
          this.searchText = this.results[this.selectedIndex - 2].label;
          window.location.href = this.results[this.selectedIndex - 2].mylink;
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
      }
    },

    handleUp() {
      if (this.showSearchResults) {
        if (this.selectedIndex === -1 || this.selectedIndex === 2) {
          this.selectedIndex = this.optionsCount - 2;
        } else {
          this.selectedIndex--;
        }
      }
    },

    handleDown() {
      if (this.showSearchResults) {
        if (this.selectedIndex === -1 || this.selectedIndex === this.optionsCount - 2) {
          this.selectedIndex = 2;
        } else {
          this.selectedIndex++;
        }
      } else {
        this.showSearchResults = true;
      }
    },

    async handleKeyUp(e:KeyboardEvent) {
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
        'Meta'];

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
    }
  };
}
