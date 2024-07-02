/* eslint-disable @typescript-eslint/ban-ts-comment */

import { createInertiaApp } from '@inertiajs/react';
import createServer from '@inertiajs/react/server';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import ReactDOMServer from 'react-dom/server';
import type { RouteName, RouteParams } from 'ziggy-js';

import { route } from '../../vendor/tightenco/ziggy';

const appName = import.meta.env.APP_NAME || 'RetroAchievements';

createServer((page) =>
  createInertiaApp({
    page,

    render: ReactDOMServer.renderToString,

    title: (title) => `${title} · ${appName}`,

    resolve: (name) =>
      resolvePageComponent(`./pages/${name}.tsx`, import.meta.glob('./pages/**/*.tsx')),

    setup: ({ App, props }) => {
      global.route<RouteName> = (name, params, absolute) =>
        route(name, params as RouteParams<string & object>, absolute, {
          // @ts-expect-error
          ...page.props.ziggy,
          // @ts-expect-error
          location: new URL(page.props.ziggy.location),
        });

      return <App {...props} />;
    },
  }),
);
