import Alpine from 'alpinejs';
// eslint-disable-next-line camelcase,import/no-unresolved
// import { livewire_hot_reload } from 'virtual:livewire-hot-reload';

import { clipboard, mobileSafeTipEvents, themeChange } from './utils';

// livewire_hot_reload();

window.mobileSafeTipEvents = mobileSafeTipEvents;

window.Alpine = Alpine;
Alpine.start();

window.clipboard = clipboard;

themeChange();
