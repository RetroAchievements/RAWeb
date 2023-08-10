import Alpine from 'alpinejs';
// eslint-disable-next-line camelcase,import/no-unresolved
// import { livewire_hot_reload } from 'virtual:livewire-hot-reload';

import { hideEarnedCheckboxComponent, newsCarouselComponent, tooltipComponent } from './alpine';
import {
  autoExpandTextInput,
  copyToClipboard,
  handleLeaderboardTabClick,
  initializeTextareaCounter,
  injectShortcode,
  loadPostPreview,
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
window.handleLeaderboardTabClick = handleLeaderboardTabClick;
window.initializeTextareaCounter = initializeTextareaCounter;
window.injectShortcode = injectShortcode;
window.loadPostPreview = loadPostPreview;
window.toggleUserCompletedSetsVisibility = toggleUserCompletedSetsVisibility;

// Alpine.js Components
window.hideEarnedCheckboxComponent = hideEarnedCheckboxComponent;
window.newsCarouselComponent = newsCarouselComponent;
window.tooltipComponent = tooltipComponent;

// Alpine needs to be placed after all `window` injection
// or race conditions could occur.
document.addEventListener('DOMContentLoaded', () => {
  window.Alpine = Alpine;
  Alpine.start();
});

themeChange();
