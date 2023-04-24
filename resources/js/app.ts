import Alpine from 'alpinejs';
// eslint-disable-next-line camelcase,import/no-unresolved
// import { livewire_hot_reload } from 'virtual:livewire-hot-reload';

import { newsCarousel } from './carousel/newsCarousel';
import {
  copyToClipboard,
  handleLeaderboardTabClick,
  injectShortcode,
  mobileSafeTipEvents,
  themeChange,
} from './utils';
import { lazyLoadModuleOnIdFound } from './lazyLoadModuleOnIdFound';

// livewire_hot_reload();

lazyLoadModuleOnIdFound({
  elementId: 'reorder-site-awards-header',
  codeFileName: 'reorderSiteAwards',
  moduleNameToAttachToWindow: 'reorderSiteAwards',
});

lazyLoadModuleOnIdFound({
  elementId: 'hide-user-completed-sets-checkbox',
  codeFileName: 'toggleUserCompletedSetsVisibility',
  moduleNameToAttachToWindow: 'toggleUserCompletedSetsVisibility',
});

window.copyToClipboard = copyToClipboard;
window.handleLeaderboardTabClick = handleLeaderboardTabClick;
window.injectShortcode = injectShortcode;
window.mobileSafeTipEvents = mobileSafeTipEvents;
window.newsCarousel = newsCarousel;

// Alpine needs to be placed after all `window` injection
// or race conditions could occur.
window.Alpine = Alpine;
Alpine.start();

themeChange();
