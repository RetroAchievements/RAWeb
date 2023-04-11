import Alpine from 'alpinejs';
// eslint-disable-next-line camelcase,import/no-unresolved
// import { livewire_hot_reload } from 'virtual:livewire-hot-reload';

import { attachTooltipToElement } from './tooltip';
import {
  copyToClipboard,
  handleLeaderboardTabClick,
  injectShortcode,
  themeChange
} from './utils';
import { lazyLoadModuleOnIdFound } from './lazyLoadModuleOnIdFound';

// livewire_hot_reload();

lazyLoadModuleOnIdFound({
  elementId: 'reorder-site-awards-header',
  codeFileName: 'reorderSiteAwards',
  moduleName: 'reorderSiteAwards',
});

window.copyToClipboard = copyToClipboard;
window.handleLeaderboardTabClick = handleLeaderboardTabClick;
window.injectShortcode = injectShortcode;
window.attachTooltipToElement = attachTooltipToElement;

// Alpine needs to be placed after all `window` injection
// or race conditions could occur.
window.Alpine = Alpine;
Alpine.start();

themeChange();
