import react from '@vitejs/plugin-react';
import { resolve } from 'path';
import { defineConfig } from 'vitest/config';

export default defineConfig({
  plugins: [react()],

  resolve: {
    alias: {
      '@': resolve(__dirname, './resources/js'),
    },
  },

  test: {
    environment: 'happy-dom',
    setupFiles: ['resources/js/setupTests.ts'],
    include: ['resources/js/**/*.{test,spec}.{ts,tsx}'],
    globals: true,

    pool: 'forks',

    // Filter out harmless happy-dom errors from stderr.
    onConsoleLog(log, type) {
      if (type === 'stderr') {
        // Filter DOMException errors from iframes.
        if (
          log.includes('DOMException') &&
          (log.includes('AbortError') || log.includes('NetworkError')) &&
          (log.includes('Fetch') ||
            log.includes('iframe') ||
            log.includes('youtube') ||
            log.includes('twitch'))
        ) {
          return false;
        }

        // Filter ECONNREFUSED network errors.
        if (
          log.includes('ECONNREFUSED') ||
          log.includes('AggregateError') ||
          log.includes('internalConnectMultiple') ||
          log.includes('afterConnectMultiple') ||
          log.includes('createConnectionError') ||
          log.includes('[errors]:') ||
          log.includes('errno:') ||
          log.includes('syscall:') ||
          (log.includes('::1:') && log.includes('connect')) ||
          (log.includes('127.0.0.1:') && log.includes('connect'))
        ) {
          return false;
        }
      }
    },

    coverage: {
      provider: 'v8',
      reporter: [['text', { skipFull: true }], 'html'],
      include: [
        /*
         * Disregard coverage for Alpine.js stuff, mounting code, /pages, and /tools.
         *  - Alpine.js stuff will be removed.
         *  - Covering mounting code would just test the framework.
         *  - /tools is internal and not user-facing.
         *  - /pages should be covered by controller tests.
         */
        'resources/js/common',
        'resources/js/features',
        'resources/js/utils',
      ],
      exclude: [
        'resources/js/common/components/+vendor', // shadcn/ui lib code
        'resources/js/common/components/GlobalSearchProvider', // has to pierce the global window context
        'resources/js/common/hooks/useIsHydrated.ts', // the 3rd arg is not coverable under test
        'resources/js/common/utils/+vendor', // 3rd party utils
        'resources/js/tools/eslint-rules', // custom ESLint rules
        '**/index.ts',
        '**/*.model.ts',
        '**/*.test.ts',
        '**/*.test.tsx',
        '**/*.spec.ts',
        '**/*.spec.tsx',
      ],
      thresholds: {
        lines: 99,
        functions: 100,
        branches: 99.5,
        statements: 99,
      },
    },
  },
});
