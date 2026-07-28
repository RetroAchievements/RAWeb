import inertia from '@inertiajs/vite';
import babel from '@rolldown/plugin-babel';
import { sentryVitePlugin } from '@sentry/vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import react, { reactCompilerPreset } from '@vitejs/plugin-react';
import { existsSync, readFileSync } from 'fs';
import laravel from 'laravel-vite-plugin';
import { homedir } from 'os';
import { resolve } from 'path';
import { defineConfig, loadEnv, type Plugin } from 'vite';

export default defineConfig(({ mode, isSsrBuild }) => {
  const env = loadEnv(mode, process.cwd(), '');

  if (!env.VITE_BUILD_PATH) {
    throw Error('VITE_BUILD_PATH not set');
  }

  if (!env.APP_URL) {
    throw Error('APP_URL not set');
  }

  /**
   * This ensures all assets use the same domain to prevent duplicate asset loading.
   * Without this, assets get loaded twice - once from ASSET_URL (eg: static.retroachievements.org)
   * and once from APP_URL (eg: retroachievements.org) due to how Vite and Inertia handle
   * dynamic imports.
   *
   * SSR builds always use relative paths since they run server-side.
   * Client builds use the full ASSET_URL.
   */
  const assetUrl = env.ASSET_URL || env.APP_URL;
  const base = assetUrl
    ? new URL(`/${env.VITE_BUILD_PATH}`, assetUrl).href
    : `/${env.VITE_BUILD_PATH}`;
  const shouldUploadSourceMaps = Boolean(env.SENTRY_AUTH_TOKEN);

  return {
    base: isSsrBuild ? `/${env.VITE_BUILD_PATH}` : base,

    // https://vitejs.dev/config/#build-options
    build: {
      outDir: isSsrBuild ? 'bootstrap/ssr' : `public/${env.VITE_BUILD_PATH}`,
      assetsDir: '',
      assetsInlineLimit: 4096,
      sourcemap: shouldUploadSourceMaps,
    },

    // https://vitejs.dev/config/#plugins
    plugins: [
      laravel({
        input: [
          'resources/css/app.css',
          'resources/js/tall-stack/app.ts',
          'resources/js/app.tsx',
          'resources/js/global-search-standalone.tsx',
        ],
        ssr: 'resources/js/ssr.tsx',
        refresh: ['resources/views/**'],
      }),

      tailwindcss(),

      react(),

      inertiaDevSsrBasePath(),

      inertia({
        ssr: {
          entry: 'resources/js/ssr.tsx',
          port: Number(env.VITE_INERTIA_SSR_PORT ?? 13714),
          cluster: env.INERTIA_SSR_CLUSTER === 'true',
        },
      }),

      // @tanstack/react-table uses a mutable API that's incompatible with
      // React Compiler's memoization. The useReactTable hook returns a stable
      // object, so the compiler caches stale results from table/column/row
      // method calls, breaking column visibility toggling and filter labels.
      // @ts-expect-error -- @rolldown/plugin-babel has a type bug where PluginOptions
      // inherits required fields from @types/babel__core that should be optional.
      babel({
        include: [/resources\/js\/.*\.[jt]sx?$/],
        exclude: [/features\/game-list/],
        presets: [reactCompilerPreset()],
      }),

      ...(shouldUploadSourceMaps
        ? [
            sentryVitePlugin({
              org: 'retroachievementsorg',
              project: 'raweb',
              authToken: env.SENTRY_AUTH_TOKEN,
            }),
          ]
        : []),
    ],

    ssr: {
      noExternal: [
        'react-use',
        '@bbob/core',
        '@bbob/html',
        '@bbob/plugin-helper',
        '@bbob/preset-react',
        '@bbob/preset-html5',
        '@bbob/react',
      ],
    },

    optimizeDeps: {
      include: ['react-use'],
    },

    resolve: {
      alias: {
        '@': resolve(__dirname, './resources/js'),
        livewire: resolve(__dirname, './vendor/livewire/livewire/dist/livewire.esm'),
      },
    },

    // @ see https://vitejs.dev/config/#server-options
    server: detectServerConfig(env),
  };
});

/**
 * Allow `pnpm dev` to work with SSR.
 */
function inertiaDevSsrBasePath(): Plugin {
  return {
    name: 'raweb-inertia-dev-ssr-base-path',
    apply: 'serve',

    configureServer(server) {
      const basePath = resolveViteBasePath(server.config.base);

      if (!basePath) {
        return;
      }

      server.middlewares.use((request, _response, next) => {
        if (request.url?.startsWith(`${basePath}/__inertia_ssr`)) {
          request.url = request.url.slice(basePath.length);
        }

        next();
      });
    },
  };
}

function resolveViteBasePath(base: string): string {
  const pathname = base.startsWith('http') ? new URL(base).pathname : base;

  return pathname.replace(/\/$/, '');
}

function detectServerConfig(env: Record<string, string>) {
  const watch = {
    // Explicitly ignore large volume directories to prevent running into system-level limits
    // See https://vitejs.dev/config/server-options.html#server-watch
    ignored: ['**/public/**', '**/storage/**', '**/vendor/**'],
  };

  const { host } = new URL(env.APP_URL);

  const keyPath = resolve(homedir(), `.config/valet/Certificates/${host}.key`);
  const certificatePath = resolve(homedir(), `.config/valet/Certificates/${host}.crt`);
  const useDevSsl = env.VITE_USE_DEV_SSL === 'true';

  if (useDevSsl && existsSync(keyPath) && existsSync(certificatePath)) {
    return {
      host,
      watch,
      hmr: { host },
      https: {
        key: readFileSync(keyPath),
        cert: readFileSync(certificatePath),
      },
      port: Number(env.VITE_PORT),
    };
  }

  return {
    watch,
    cors: true,
    port: Number(env.VITE_PORT),
  };
}
