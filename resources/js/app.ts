// eslint-disable-next-line camelcase,import/no-unresolved
// import { livewire_hot_reload } from 'virtual:livewire-hot-reload';

// eslint-disable-next-line import/no-unresolved
import { Livewire } from 'livewire';
import {
  modalComponent,
  newsCarouselComponent,
  toggleAchievementRowsComponent,
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
window.modalComponent = modalComponent;
window.newsCarouselComponent = newsCarouselComponent;
window.toggleAchievementRowsComponent = toggleAchievementRowsComponent;
window.tooltipComponent = tooltipComponent;

// https://livewire.laravel.com/docs/alpine#manually-bundling-alpine-in-your-javascript-build
// Alpine.directive('clipboard', (el) => {
//   const text = el.textContent;
//
//   el.addEventListener('click', () => {
//     navigator.clipboard.writeText(text);
//   });
// });

Livewire.start();

themeChange();
