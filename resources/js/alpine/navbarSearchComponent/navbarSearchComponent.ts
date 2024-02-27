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
  init: (formEl: ReferenceElement, ulEl: HTMLElement) => void;
  handleClickSearchResult: (label: string) => void;
  handleKeyDown: (e: KeyboardEvent) => void;
  handleKeyUp: (e: KeyboardEvent) => Promise<void>;
  handleNavigationKeys: (key: string, optionsCount: number) => void
  performSearch: () => Promise<void>;
}

export function navbarSearchComponent(): NavbarSearchComponentProps {
  return {
    showSearchResults: false,
    searchText: '',
    results: [],
    selectedIndex: -1,

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

    get activeDescendentId() {
      if (this.selectedIndex !== -1) {
        return this.results[this.selectedIndex - 2].mylink.slice(1).replace('/', '-');
      }
      return '';
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

    handleKeyDown(e:KeyboardEvent) {
      if (e.key === 'ArrowUp' || e.key === 'Enter') {
        e.preventDefault();
      }
    },

    async handleKeyUp(e:KeyboardEvent) {
      const ignoredKeys = ['ArrowLeft', 'ArrowRight', 'Shift', 'Control', 'Alt', 'Meta'];
      if (ignoredKeys.includes(e.key)) {
        return;
      }

      if (this.searchText.length < 2) {
        this.showSearchResults = false;
        this.selectedIndex = -1;
      }

      const searchBoxDropdownEl = document.querySelector('#search-listbox');
      const optionsCount = searchBoxDropdownEl ? searchBoxDropdownEl.childNodes.length : 0;

      if (['ArrowUp', 'ArrowDown', 'Enter', 'Escape'].includes(e.key)) {
        this.handleNavigationKeys(e.key, optionsCount);
        return;
      }

      await this.performSearch();
    },

    handleNavigationKeys(key: string, optionsCount: number) {
      switch (key) {
        case 'ArrowUp':
          if (this.showSearchResults) {
            if (this.selectedIndex === -1 || this.selectedIndex === 2) {
              this.selectedIndex = optionsCount - 2;
            } else {
              this.selectedIndex--;
            }
          }
          break;
        case 'ArrowDown':
          if (this.showSearchResults) {
            if (this.selectedIndex === -1 || this.selectedIndex === optionsCount - 2) {
              this.selectedIndex = 2;
            } else {
              this.selectedIndex++;
            }
          } else {
            this.showSearchResults = true;
          }
          break;
        case 'Enter':
          if (this.showSearchResults) {
            this.showSearchResults = false;

            if (this.selectedIndex !== -1) {
              this.searchText = this.results[this.selectedIndex - 2].label;
              window.location.href = this.results[this.selectedIndex - 2].mylink;
            }
          } else {
            document.querySelector<HTMLFormElement>('.searchbox-top')?.requestSubmit();
          }
          break;
        case 'Escape':
          if (this.showSearchResults) {
            this.showSearchResults = false;
          } else {
            this.searchText = '';
          }
          break;
        default:
      }
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
