import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot, hydrateRoot } from 'react-dom/client';

import { AppProviders } from './common/components/AppProviders';
import type { AppGlobalProps } from './common/models';
import { loadDayjsLocale } from './common/utils/l10n/loadDayjsLocale';
import i18n from './i18n-client';

const appName = import.meta.env.APP_NAME || 'RetroAchievements';

createInertiaApp({
  title: (title) => `${title} · ${appName}`,

  resolve: (name) =>
    resolvePageComponent(`./pages/${name}.tsx`, import.meta.glob('./pages/**/*.tsx')),

  // @ts-expect-error -- async setup() breaks type rules, but is actually supported.
  async setup({ el, App, props }) {
    const globalProps = props.initialPage.props as unknown as AppGlobalProps;
    const userLocale = globalProps.auth?.user.locale ?? 'en_US';

    await Promise.all([i18n.changeLanguage(userLocale), loadDayjsLocale(userLocale)]);

    const appElement = (
      <AppProviders i18n={i18n}>
        <App {...props} />
      </AppProviders>
    );

    if (import.meta.env.DEV) {
      createRoot(el).render(appElement);

      return;
    }

    hydrateRoot(el, appElement);
  },

  progress: {
    delay: 250,
    color: '#29d',
  },
});
