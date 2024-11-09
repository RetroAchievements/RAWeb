// eslint-disable-next-line @typescript-eslint/ban-ts-comment -- this file has known type issues that are safe and part of the official Inertia.js docs
// @ts-nocheck

import { createInertiaApp } from '@inertiajs/react';
import createServer from '@inertiajs/react/server';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import ReactDOMServer from 'react-dom/server';
import type { RouteName, RouteParams } from 'ziggy-js';

import { route } from '../../vendor/tightenco/ziggy';
import { AppProviders } from './common/components/AppProviders';
import type { AppGlobalProps } from './common/models';
import { loadDayjsLocale } from './common/utils/l10n/loadDayjsLocale';
import { createServerI18nInstance } from './i18n-server';

const appName = import.meta.env.APP_NAME ?? 'RetroAchievements';
const inertiaDaemonPort = import.meta.env.VITE_INERTIA_SSR_PORT ?? 13714;

createServer(
  (page) =>
    createInertiaApp({
      page,

      render: ReactDOMServer.renderToString,

      title: (title) => `${title} · ${appName}`,

      resolve: (name) =>
        resolvePageComponent(`./pages/${name}.tsx`, import.meta.glob('./pages/**/*.tsx')),

      async setup({ App, props }) {
        global.route<RouteName> = (name, params, absolute) =>
          route(name, params as RouteParams<string & object>, absolute, {
            ...page.props.ziggy,
            location: new URL(page.props.ziggy.location),
          });

        const globalProps = props.initialPage.props as AppGlobalProps;
        const userLocale = globalProps.auth?.user.locale ?? 'en_US';

        const i18nInstance = await createServerI18nInstance(userLocale);
        await loadDayjsLocale(userLocale);

        return (
          <AppProviders i18n={i18nInstance}>
            <App {...props} />
          </AppProviders>
        );
      },
    }),

  inertiaDaemonPort,
);
