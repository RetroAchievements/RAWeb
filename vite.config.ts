/// <reference types="vitest" />

import react from '@vitejs/plugin-react';
import { existsSync, readFileSync } from 'fs';
import laravel from 'laravel-vite-plugin';
import { homedir } from 'os';
import { resolve } from 'path';
import { defineConfig, loadEnv } from 'vite';

export default defineConfig(({ mode, isSsrBuild }) => {
  const env = loadEnv(mode, process.cwd(), '');

  if (!env.VITE_BUILD_PATH) {
    throw Error('VITE_BUILD_PATH not set');
  }

  if (!env.APP_URL) {
    throw Error('APP_URL not set');
  }

  return {
    // Required for SSR assets to load properly
    base: '/assets/build',

    // https://vitejs.dev/config/#build-options
    build: {
      outDir: isSsrBuild ? 'bootstrap/ssr' : `public/${env.VITE_BUILD_PATH}`,
      assetsDir: '',
      assetsInlineLimit: 4096,
    },

    // https://vitejs.dev/config/#plugins
    plugins: [
      laravel({
        input: ['resources/css/app.css', 'resources/js/tall-stack/app.ts', 'resources/js/app.tsx'],
        ssr: 'resources/js/ssr.tsx',
        refresh: ['resources/views/**'],
      }),
      react(),
    ],

    ssr: {
      noExternal: ['react-use'],
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

    test: {
      environment: 'jsdom',
      setupFiles: ['resources/js/setupTests.ts'],
      include: ['resources/js/**/*.{test,spec}.{ts,tsx}'],
      globals: true,

      coverage: {
        provider: 'v8',
        reporter: ['text', 'html'],
        include: [
          /*
           * Disregard coverage for Alpine.js stuff, mounting code, and /pages.
           *  - Alpine.js stuff will be removed.
           *  - Covering mounting code would just test the framework.
           *  - /pages should be covered by controller tests.
           */
          'resources/js/common',
          'resources/js/features',
          'resources/js/utils',
        ],
        exclude: [
          'resources/js/common/components/+vendor', // shadcn/ui lib code
          '**/index.ts',
          '**/*.model.ts',
          '**/*.test.ts',
          '**/*.test.tsx',
          '**/*.spec.ts',
          '**/*.spec.tsx',
        ],
      },
    },

    // @ see https://vitejs.dev/config/#server-options
    server: detectServerConfig(env),
  };
});

function detectServerConfig(env) {
  const watch = {
    // Explicitly ignore large volume directories to prevent running into system-level limits
    // See https://vitejs.dev/config/server-options.html#server-watch
    ignored: ['**/public/**', '**/storage/**', '**/vendor/**'],
  };

  const { host } = new URL(env.APP_URL);
  const keyPath = resolve(homedir(), `.config/valet/Certificates/${host}.key`);
  const certificatePath = resolve(homedir(), `.config/valet/Certificates/${host}.crt`);

  if (!existsSync(keyPath) || !existsSync(certificatePath)) {
    // NOTE do not set host, it defaults to either localhost or 0.0.0.0 for Docker
    return {
      port: env.VITE_PORT,
      watch,
    };
  }

  return {
    hmr: { host },
    host,
    port: env.VITE_PORT,
    watch,
    https: {
      key: readFileSync(keyPath),
      cert: readFileSync(certificatePath),
    },
  };
}
