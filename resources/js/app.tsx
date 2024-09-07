import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot, hydrateRoot } from 'react-dom/client';

import { AppProviders } from './common/components/AppProviders';

const appName = import.meta.env.APP_NAME || 'RetroAchievements';

createInertiaApp({
  title: (title) => `${title} · ${appName}`,

  resolve: (name) =>
    resolvePageComponent(`./pages/${name}.tsx`, import.meta.glob('./pages/**/*.tsx')),

  setup({ el, App, props }) {
    if (import.meta.env.DEV) {
      createRoot(el).render(
        <AppProviders>
          <App {...props} />
        </AppProviders>,
      );

      return;
    }

    hydrateRoot(
      el,
      <AppProviders>
        <App {...props} />
      </AppProviders>,
    );
  },

  progress: {
    delay: 250,
    color: '#29d',
  },
});
