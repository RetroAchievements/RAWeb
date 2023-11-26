/// <reference types="vitest" />

import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';
import { existsSync, readFileSync } from 'fs';
import { homedir } from 'os';
import { resolve } from 'path';

export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), '');

  if (!env.VITE_BUILD_PATH) {
    throw Error('VITE_BUILD_PATH not set');
  }

  if (!env.APP_URL) {
    throw Error('APP_URL not set');
  }

  return {
    // https://vitejs.dev/config/#build-options
    build: {
      outDir: `public/${env.VITE_BUILD_PATH}`,
      assetsDir: '',
      assetsInlineLimit: 4096
    },
    // https://vitejs.dev/config/#plugins
    plugins: [
      laravel({
        input: [
          'resources/css/app.css',
          'resources/js/app.ts',
        ],
        refresh: [
          'resources/views/**'
        ]
      }),
    ],
    resolve: {
      alias: {
        '@': resolve(__dirname, './resources/js'),
        livewire: resolve(__dirname, './vendor/livewire/livewire/dist'),
      },
    },
    test: {
      environment: 'jsdom',
      setupFiles: 'resources/js/setupTests.ts',
      include: ['resources/js/**/*.{test,spec}.ts']
    },
    // @ see https://vitejs.dev/config/#server-options
    server: detectServerConfig(env),
  };
});

function detectServerConfig(env) {
  const watch = {
    // Explicitly ignore large volume directories to prevent running into system-level limits
    // See https://vitejs.dev/config/server-options.html#server-watch
    ignored: [
      '**/public/**',
      '**/storage/**',
      '**/vendor/**',
    ],
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
