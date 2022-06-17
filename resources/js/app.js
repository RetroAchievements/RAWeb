import Alpine from 'alpinejs';
// eslint-disable-next-line camelcase,import/no-unresolved
// import { livewire_hot_reload } from 'virtual:livewire-hot-reload';
import { themeChange } from './theme';
import { clipboard } from './helpers';

// livewire_hot_reload();

window.Alpine = Alpine;
Alpine.start();

window.clipboard = clipboard;

themeChange();
