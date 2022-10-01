import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';
import livewire from '@defstudio/vite-livewire-plugin';
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
          'resources/js/app.js',
        ],
        // https://laravel.com/docs/vite#working-with-blade-and-routes
        // refresh: [{
        //   paths: [
        //     'public/css/**',
        //     'public/js/**',
        //   ],
        //   config: { delay: 300 }
        // }],
        refresh: false
      }),
      // livewire({
      //   refresh: ['resources/css/app.css'],
      // }),
      // https://vitejs.dev/guide/build.html#chunking-strategy
      // splitVendorChunkPlugin(),
    ],
    resolve: {
      alias: {
        '@': resolve(__dirname, './resources/js'),
      },
    },
    // @ see https://vitejs.dev/config/#server-options
    server: detectServerConfig(env),
  };
});

// https://freek.dev/2276-making-vite-and-valet-play-nice-together
function detectServerConfig(env) {
  const appUrl = new URL(env.APP_URL);
  let { host } = appUrl;

  // remove port - vite uses its own
  if (host.startsWith('localhost')) {
    host = 'localhost';

    return {
      hmr: { host },
      host,
      // port: env.VITE_PORT
    };
  }

  const keyPath = resolve(homedir(), `.config/valet/Certificates/${host}.key`);
  const certificatePath = resolve(homedir(), `.config/valet/Certificates/${host}.crt`);

  const config = {
    hmr: { host },
    host,
    port: env.VITE_PORT
  };

  if (!existsSync(keyPath) || !existsSync(certificatePath)) {
    return config;
  }

  config.https = {
    key: readFileSync(keyPath),
    cert: readFileSync(certificatePath),
  };

  return config;
}
