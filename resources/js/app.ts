import Alpine from 'alpinejs';
// eslint-disable-next-line camelcase,import/no-unresolved
// import { livewire_hot_reload } from 'virtual:livewire-hot-reload';

import { lazyLoadModuleOnIdFound } from './lazyLoadModuleOnIdFound';
import { clipboard, mobileSafeTipEvents, themeChange } from './utils';

// livewire_hot_reload();

lazyLoadModuleOnIdFound({
  elementId: 'reorder-site-awards-header',
  codePath: './reorderSiteAwards',
  moduleName: 'reorderSiteAwards',
});

window.mobileSafeTipEvents = mobileSafeTipEvents;

window.Alpine = Alpine;
Alpine.start();

window.clipboard = clipboard;

themeChange();
