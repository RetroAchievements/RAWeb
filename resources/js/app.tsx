import { createInertiaApp, router } from '@inertiajs/react';
import * as Sentry from '@sentry/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot, hydrateRoot } from 'react-dom/client';

import { AppProviders } from './common/components/AppProviders';
import type { AppGlobalProps } from './common/models';
import { loadDayjsLocale } from './common/utils/l10n/loadDayjsLocale';
import i18n from './i18n-client';
// @ts-expect-error -- this isn't a real ts module
import { Ziggy } from './ziggy';

// @ts-expect-error -- we're injecting this on purpose
globalThis.Ziggy = Ziggy;

const appName = import.meta.env.APP_NAME || 'RetroAchievements';

// Initialize Sentry.
Sentry.init({
  dsn: import.meta.env.VITE_SENTRY_DSN,
  integrations: [
    Sentry.browserTracingIntegration(),
    Sentry.replayIntegration({
      maskAllText: false,
      blockAllMedia: false,
    }),
    Sentry.captureConsoleIntegration({ levels: ['warn', 'error'] }),
  ],
  environment: import.meta.env.APP_ENV,
  release: import.meta.env.APP_VERSION,
  tracesSampleRate: import.meta.env.SENTRY_TRACES_SAMPLE_RATE,
  tracePropagationTargets: ['localhost', /^https:\/\/retroachievements\.org\/internal-api/],
  replaysSessionSampleRate: import.meta.env.SENTRY_REPLAYS_SESSION_SAMPLE_RATE,
  replaysOnErrorSampleRate: 1.0,
});

createInertiaApp({
  title: (title) => (title && title !== appName ? `${title} · ${appName}` : appName),

  resolve: (name) =>
    resolvePageComponent(`./pages/${name}.tsx`, import.meta.glob('./pages/**/*.tsx')),

  // @ts-expect-error -- async setup() breaks type rules, but is actually supported.
  async setup({ el, App, props }) {
    const globalProps = props.initialPage.props as unknown as AppGlobalProps;
    const userLocale = globalProps.auth?.user.locale ?? 'en_US';

    if (globalProps.auth?.user) {
      Sentry.setUser({
        id: globalProps.auth.user.id,
        username: globalProps.auth.user.displayName,
      });
    }

    await Promise.all([i18n.changeLanguage(userLocale), loadDayjsLocale(userLocale)]);

    /**
     * WORKAROUND: Inertia has a major bug with iOS Safari.
     * @see https://github.com/inertiajs/inertia/issues/2402
     * iOS Safari doesn't properly restore Inetia's page component state when using
     * the back/forward buttons. The page URL changes but the content doesn't update.
     * This workaround forces a full page reload on back/forward navigation to solve the issue.
     *
     * TODO Remove this when Inertia fixes iOS Safari history handling (issue #2402).
     */
    if (typeof window !== 'undefined') {
      const isIos = /iPad|iPhone|iPod/.test(navigator.userAgent);
      if (isIos) {
        // Store the current URL when navigating.
        let lastUrl = window.location.href;

        // Listen for popstate (back/forward button).
        window.addEventListener('popstate', () => {
          const currentUrl = window.location.href;
          // If the URL changed (meaning back/forward was pressed).
          if (currentUrl !== lastUrl) {
            // Force a hard reload to the new URL.
            window.location.href = currentUrl;
          }
        });

        // Update the last URL whenever navigation happens.
        router.on('success', () => {
          lastUrl = window.location.href;
        });
      }
    }

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
