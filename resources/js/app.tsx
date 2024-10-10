import { createInertiaApp } from '@inertiajs/react';
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

  setup({ el, App, props }) {
    const globalProps = props.initialPage.props as unknown as AppGlobalProps;
    const userLocale = globalProps.auth?.user.locale ?? 'en_US';

    if (import.meta.env.DEV) {
      createRoot(el).render(
        <LaravelReactI18nProvider
          locale={userLocale}
          fallbackLocale="en_US"
          files={import.meta.glob('/lang/*.json', { eager: true })}
        >
          <AppProviders>
            <App {...props} />
          </AppProviders>
        </LaravelReactI18nProvider>,
      );

      return;
    }

    hydrateRoot(
      el,
      <LaravelReactI18nProvider
        locale={userLocale}
        fallbackLocale="en_US"
        files={import.meta.glob('/lang/*.json', { eager: true })}
      >
        <AppProviders>
          <App {...props} />
        </AppProviders>
      </LaravelReactI18nProvider>,
    );
  },

  progress: {
    delay: 250,
    color: '#29d',
  },
});
