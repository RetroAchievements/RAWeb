import '@testing-library/jest-dom/vitest';

import { cleanup } from '@testing-library/react';

import { loadFaker } from './test/createFactory';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore
import { Ziggy } from './ziggy.js';

/**
 * Ziggy depends on being in the global namespace.
 */
(global as any).Ziggy = Ziggy;

beforeAll(async () => {
  /**
   * Asynchronously load faker before any tests run. `createFactory()` helpers
   * assume faker is loaded in memory and will throw an error if it's not.
   */
  await loadFaker();
});

beforeAll(() => {
  /**
   * ResizeObserver is unavailable in NodeJS.
   */
  global.ResizeObserver = class ResizeObserver {
    observe() {
      // do nothing
    }
    unobserve() {
      // do nothing
    }
    disconnect() {
      // do nothing
    }
  };
});

beforeEach(() => {
  // We'll directly dump all arguments given to Ziggy's route() function.

  // eslint-disable-next-line @typescript-eslint/no-explicit-any -- tests are ok
  (global.route as any) = vi.fn((...args: any[]) => args);
});

afterEach(() => {
  // Run garbage collection for any React components that may still be mounted.
  cleanup();

  // Reset global mocks, such as route(), back to a pristine state.
  vi.restoreAllMocks();

  // If any fake dates or times are in place, remove them after each test.
  vi.useRealTimers();
});
