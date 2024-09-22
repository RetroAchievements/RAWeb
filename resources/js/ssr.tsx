// eslint-disable-next-line @typescript-eslint/ban-ts-comment -- this file has known type issues that are safe and part of the official Inertia.js docs
// @ts-nocheck

import { createInertiaApp } from '@inertiajs/react';
import createServer from '@inertiajs/react/server';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import ReactDOMServer from 'react-dom/server';
import type { RouteName, RouteParams } from 'ziggy-js';

import { route } from '../../vendor/tightenco/ziggy';
import { AppProviders } from './common/components/AppProviders';

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

      setup: ({ App, props }) => {
        global.route<RouteName> = (name, params, absolute) =>
          route(name, params as RouteParams<string & object>, absolute, {
            ...page.props.ziggy,
            location: new URL(page.props.ziggy.location),
          });

        return (
          <AppProviders>
            <App {...props} />
          </AppProviders>
        );
      },
    }),

  inertiaDaemonPort,
);
