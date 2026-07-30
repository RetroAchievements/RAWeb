import tailwindcss from '@tailwindcss/vite';
import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vite';

export default defineConfig({
  plugins: [
    laravel({
      hotFile: 'public/filament.hot',
      buildDirectory: 'fi-build',
      input: ['resources/filament/css/theme.css'],
      refresh: true,
    }),

    tailwindcss(),
  ],
});
