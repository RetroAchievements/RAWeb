import { createInertiaApp } from '@inertiajs/react';
import dayjs from 'dayjs';
import { LaravelReactI18nProvider } from 'laravel-react-i18n';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot, hydrateRoot } from 'react-dom/client';

import { AppProviders } from './common/components/AppProviders';
import type { AppGlobalProps } from './common/models';

const appName = import.meta.env.APP_NAME || 'RetroAchievements';

createInertiaApp({
  title: (title) => `${title} · ${appName}`,

  resolve: (name) =>
    resolvePageComponent(`./pages/${name}.tsx`, import.meta.glob('./pages/**/*.tsx')),

  // @ts-expect-error -- async setup() breaks type rules, but is actually supported.
  async setup({ el, App, props }) {
    const globalProps = props.initialPage.props as unknown as AppGlobalProps;
    const userLocale = globalProps.auth?.user.locale ?? 'en_US';

    /**
     * TODO if more locales are added, break this out into a util
     */
    if (userLocale === 'pt_BR') {
      try {
        await import('dayjs/locale/pt-br.js');
        dayjs.locale('pt-br');
      } catch (err) {
        console.warn('Unable to load Day.js locale for pt_BR.', err);
      }
    }

    const appElement = (
      <LaravelReactI18nProvider
        locale={userLocale}
        fallbackLocale="en_US"
        files={import.meta.glob('/lang/*.json', { eager: true })}
      >
        <AppProviders>
          <App {...props} />
        </AppProviders>
      </LaravelReactI18nProvider>
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
