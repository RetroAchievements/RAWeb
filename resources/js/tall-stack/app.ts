// eslint-disable-next-line @typescript-eslint/ban-ts-comment -- this file has known type issues that are safe and part of the official Livewire docs
// @ts-nocheck

// import { livewire_hot_reload } from 'virtual:livewire-hot-reload';

import { getStringByteCount } from '@/common/utils/getStringByteCount';

import { Alpine, Livewire } from '../../../vendor/livewire/livewire/dist/livewire.esm';
import {
  linkifyDirective,
  modalComponent,
  toggleAchievementRowsComponent,
  tooltipComponent,
} from './alpine';
import { lazyLoadModuleOnIdFound } from './lazyLoadModuleOnIdFound';
import {
  autoExpandTextInput,
  copyToClipboard,
  deleteCookie,
  fetcher,
  getCookie,
  handleLeaderboardTabClick,
  initializeTextareaCounter,
  setCookie,
  themeChange,
  toggleUserCompletedSetsVisibility,
  updateUrlParameter,
} from './utils';

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
window.setCookie = setCookie;
window.toggleUserCompletedSetsVisibility = toggleUserCompletedSetsVisibility;
window.updateUrlParameter = updateUrlParameter;

// Alpine.js Components
window.modalComponent = modalComponent;
window.toggleAchievementRowsComponent = toggleAchievementRowsComponent;
window.tooltipComponent = tooltipComponent;

// Alpine.js Directives
Alpine.directive('linkify', linkifyDirective);

Livewire.start();

// TODO if you add another one of these, move them to a module
// Livewire
// eslint-disable-next-line @typescript-eslint/no-explicit-any -- custom events
(window as any).addEventListener('flash-success', (event: CustomEvent<{ message: string }>) => {
  showStatusSuccess(event.detail.message);
});
// Livewire
// eslint-disable-next-line @typescript-eslint/no-explicit-any -- custom events
(window as any).addEventListener('flash-error', (event: CustomEvent<{ message: string }>) => {
  showStatusFailure(event.detail.message);
});

themeChange();
