import Alpine from 'alpinejs';
import focus from '@alpinejs/focus';
// eslint-disable-next-line camelcase,import/no-unresolved
// import { livewire_hot_reload } from 'virtual:livewire-hot-reload';

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
  getStringByteCount,
  handleLeaderboardTabClick,
  initializeTextareaCounter,
  injectShortcode,
  loadPostPreview,
  setCookie,
  themeChange,
  toggleUserCompletedSetsVisibility,
} from './utils';
import { lazyLoadModuleOnIdFound } from './lazyLoadModuleOnIdFound';

// livewire_hot_reload();

lazyLoadModuleOnIdFound({
  elementId: 'reorder-site-awards-header',
  codeFileName: 'reorderSiteAwards',
  moduleNameToAttachToWindow: 'reorderSiteAwards',
});

// Utils
window.autoExpandTextInput = autoExpandTextInput;
window.copyToClipboard = copyToClipboard;
window.deleteCookie = deleteCookie;
window.fetcher = fetcher;
window.getStringByteCount = getStringByteCount;
window.handleLeaderboardTabClick = handleLeaderboardTabClick;
window.initializeTextareaCounter = initializeTextareaCounter;
window.injectShortcode = injectShortcode;
window.loadPostPreview = loadPostPreview;
window.setCookie = setCookie;
window.toggleUserCompletedSetsVisibility = toggleUserCompletedSetsVisibility;

// Alpine.js Components
window.hideEarnedCheckboxComponent = hideEarnedCheckboxComponent;
window.modalComponent = modalComponent;
window.newsCarouselComponent = newsCarouselComponent;
window.tooltipComponent = tooltipComponent;

// Alpine needs to be placed after all `window` injection
// or race conditions could occur.
document.addEventListener('DOMContentLoaded', () => {
  window.Alpine = Alpine;
  Alpine.plugin(focus);
  Alpine.start();
});

themeChange();
