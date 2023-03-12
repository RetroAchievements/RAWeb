import Alpine from 'alpinejs';
// eslint-disable-next-line camelcase,import/no-unresolved
// import { livewire_hot_reload } from 'virtual:livewire-hot-reload';

import { attachTooltipToElement } from './tooltip';
import { clipboard, mobileSafeTipEvents, themeChange } from './utils';

// livewire_hot_reload();

window.attachTooltipToElement = attachTooltipToElement;
window.mobileSafeTipEvents = mobileSafeTipEvents;

window.Alpine = Alpine;
Alpine.start();

window.clipboard = clipboard;

themeChange();
