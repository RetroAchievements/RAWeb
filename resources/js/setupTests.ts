/* eslint-disable no-restricted-imports -- test setup can import from @testing-library/react */

import '@testing-library/jest-dom/vitest';

import { cleanup } from '@testing-library/react';

import { loadFaker } from './test/createFactory';
// @ts-expect-error -- this isn't a real ts module
import { Ziggy } from './ziggy';

// @ts-expect-error -- we're injecting this on purpose
globalThis.Ziggy = Ziggy;
process.env.TZ = 'UTC';

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
  Object.defineProperty(window, 'ResizeObserver', {
    writable: true,
    value: class ResizeObserver {
      observe() {
        // do nothing
      }
      unobserve() {
        // do nothing
      }
      disconnect() {
        // do nothing
      }
    },
  });
});

beforeEach(() => {
  // We'll directly dump all arguments given to Ziggy's route() function.
  vi.mock('ziggy-js', () => ({
    // eslint-disable-next-line @typescript-eslint/no-explicit-any -- tests are ok
    route: vi.fn((...args: any[]) => args),
  }));
});

// window.matchMedia is undefined by default in JSDOM.
beforeEach(() => {
  Object.defineProperty(window, 'matchMedia', {
    writable: true,
    value: vi.fn().mockImplementation((query) => ({
      matches: false,
      media: query,
      onchange: null,
      addListener: vi.fn(), // deprecated
      removeListener: vi.fn(), // deprecated
      addEventListener: vi.fn(),
      removeEventListener: vi.fn(),
      dispatchEvent: vi.fn(),
    })),
  });
});

afterEach(() => {
  // Run garbage collection for any React components that may still be mounted.
  cleanup();

  // Reset global mocks, such as route(), back to a pristine state.
  vi.restoreAllMocks();

  // If any fake dates or times are in place, remove them after each test.
  vi.useRealTimers();
});
