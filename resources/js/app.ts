import Alpine from 'alpinejs';
// eslint-disable-next-line camelcase,import/no-unresolved
// import { livewire_hot_reload } from 'virtual:livewire-hot-reload';

import { copyToClipboard, handleLeaderboardTabClick, themeChange } from './utils';

// livewire_hot_reload();

window.copyToClipboard = copyToClipboard;
window.handleLeaderboardTabClick = handleLeaderboardTabClick;

// Alpine needs to be placed after all `window` injection
// or race conditions could occur.
window.Alpine = Alpine;
Alpine.start();

themeChange();
