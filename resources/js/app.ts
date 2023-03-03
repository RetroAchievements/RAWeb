import Alpine from 'alpinejs';
// eslint-disable-next-line camelcase,import/no-unresolved
// import { livewire_hot_reload } from 'virtual:livewire-hot-reload';

import { badgeGroup, clipboard, themeChange } from './utils';

// livewire_hot_reload();

window.clipboard = clipboard;
window.badgeGroup = badgeGroup;

themeChange();

// Alpine needs to come after all other `window` injection,
// otherwise race conditions are possible.
window.Alpine = Alpine;
Alpine.start();
