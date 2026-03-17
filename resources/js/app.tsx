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

/**
 * When Cloudflare (or anything else) intercepts an Inertia XHR and returns
 * a non-Inertia response, suppress the dev-only modal and do a full page
 * reload instead so the browser can handle the challenge/response natively.
 * @see https://inertiajs.com/docs/v2/advanced/events#invalid
 */
router.on('invalid', (event) => {
  event.preventDefault();
  window.location.reload();
});

const appName = import.meta.env.APP_NAME || 'RetroAchievements';

/**
 * Google Translate modifies the DOM by injecting <font> tags, which
 * causes React hydration mismatches. Detect this so we can suppress
 * the resulting non-actionable Sentry errors.
 */
function isGoogleTranslateActive(): boolean {
  const { classList } = document.documentElement;

  return classList.contains('translated-ltr') || classList.contains('translated-rtl');
}

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

  beforeSend(event) {
    if (event.message?.includes('Hydration') && isGoogleTranslateActive()) {
      return null;
    }

    return event;
  },
});

createInertiaApp({
  title: (title) => (title && title !== appName ? `${title} · ${appName}` : appName),

  resolve: (name) =>
    resolvePageComponent(`./pages/${name}.tsx`, import.meta.glob('./pages/**/*.tsx')),

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
