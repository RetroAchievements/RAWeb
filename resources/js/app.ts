import Alpine from 'alpinejs';
// eslint-disable-next-line camelcase,import/no-unresolved
// import { livewire_hot_reload } from 'virtual:livewire-hot-reload';

import { hideEarnedCheckboxComponent } from './alpine';
import {
  autoExpandTextInput,
  copyToClipboard,
  handleLeaderboardTabClick,
  injectShortcode,
  mobileSafeTipEvents,
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

window.autoExpandTextInput = autoExpandTextInput;
window.copyToClipboard = copyToClipboard;
window.handleLeaderboardTabClick = handleLeaderboardTabClick;
window.hideEarnedCheckboxComponent = hideEarnedCheckboxComponent;
window.injectShortcode = injectShortcode;
window.mobileSafeTipEvents = mobileSafeTipEvents;
window.toggleUserCompletedSetsVisibility = toggleUserCompletedSetsVisibility;

// Alpine needs to be placed after all `window` injection
// or race conditions could occur.
document.addEventListener('DOMContentLoaded', () => {
  window.Alpine = Alpine;
  Alpine.start();
});

themeChange();
