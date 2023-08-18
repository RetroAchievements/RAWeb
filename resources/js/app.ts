// eslint-disable-next-line camelcase,import/no-unresolved
// import { livewire_hot_reload } from 'virtual:livewire-hot-reload';

// import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';
// Alpine.plugin(yourCustomPlugin);
// Livewire.start();

import {
    hideEarnedCheckboxComponent,
    modalComponent,
    newsCarouselComponent,
    tooltipComponent,
} from './alpine';
import {
  autoExpandTextInput,
  copyToClipboard,
  deleteCookie,
  fetcher,
  getCookie,
  getStringByteCount,
  handleLeaderboardTabClick,
  initializeTextareaCounter,
  injectShortcode,
  loadPostPreview,
  setCookie,
  themeChange,
  toggleUserCompletedSetsVisibility,
  updateUrlParameter,
} from './utils';
import { lazyLoadModuleOnIdFound } from './lazyLoadModuleOnIdFound';

// livewire_hot_reload();

lazyLoadModuleOnIdFound({
  elementId: 'reorder-site-awards-header',
  codeFileName: 'reorderSiteAwards',
  moduleNameToAttachToWindow: 'reorderSiteAwards',
});

// Global Utils
window.autoExpandTextInput = autoExpandTextInput;
window.copyToClipboard = copyToClipboard;
window.deleteCookie = deleteCookie;
window.fetcher = fetcher;
window.getCookie = getCookie;
window.getStringByteCount = getStringByteCount;
window.handleLeaderboardTabClick = handleLeaderboardTabClick;
window.initializeTextareaCounter = initializeTextareaCounter;
window.injectShortcode = injectShortcode;
window.loadPostPreview = loadPostPreview;
window.setCookie = setCookie;
window.toggleUserCompletedSetsVisibility = toggleUserCompletedSetsVisibility;
window.updateUrlParameter = updateUrlParameter;

// Alpine.js Components
window.hideEarnedCheckboxComponent = hideEarnedCheckboxComponent;
window.modalComponent = modalComponent;
window.newsCarouselComponent = newsCarouselComponent;
window.tooltipComponent = tooltipComponent;

themeChange();
