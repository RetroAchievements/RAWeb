// oxlint-disable typescript/no-explicit-any
/* eslint-disable no-restricted-imports -- test setup can import from @testing-library/react */

import '@testing-library/jest-dom/vitest';

import { cleanup } from '@testing-library/react';
import {
  resetIntersectionMocking,
  setupIntersectionMocking,
} from 'react-intersection-observer/test-utils';

import i18n from './i18n-client';
import { loadFaker } from './test/createFactory';
// @ts-expect-error -- this isn't a real ts module
import { Ziggy } from './ziggy';

// @ts-expect-error -- we're injecting this on purpose
globalThis.Ziggy = Ziggy;
process.env.TZ = 'UTC';

/**
 * Suppress ECONNREFUSED errors from happy-dom's fetch/iframe teardown.
 * These bypass Vitest's onConsoleLog when running in threads pool, so
 * we intercept stderr writes directly.
 */
const originalStderrWrite = process.stderr.write.bind(process.stderr);
process.stderr.write = function (chunk: any, ...args: any[]) {
  const str = typeof chunk === 'string' ? chunk : (chunk?.toString?.() ?? '');

  if (
    str.includes('ECONNREFUSED') ||
    str.includes('AggregateError') ||
    (str.includes('DOMException') && (str.includes('AbortError') || str.includes('NetworkError')))
  ) {
    return true;
  }

  return originalStderrWrite(chunk, ...args);
} as typeof process.stderr.write;

// Mock Inertia globally for all tests.
vi.mock('@inertiajs/react', async (importOriginal) => {
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  const original = (await importOriginal()) as any;
  const React = await import('react');

  return {
    ...original,
    __esModule: true,

    Head: ({ children }: { children: React.ReactNode }) => {
      // React 19 refuses to render <meta /> or <link /> tags into JSDOM.
      // We need to convert them to <span /> tags instead.
      const convertedChildren = React.Children.map(children, (child) => {
        if (React.isValidElement(child) && (child.type === 'meta' || child.type === 'link')) {
          return React.createElement('span', child.props as React.HTMLAttributes<HTMLSpanElement>);
        }

        return child;
      });

      return React.createElement('div', { 'data-testid': 'head-content' }, convertedChildren);
    },

    router: {
      replace: vi.fn(),
      visit: vi.fn(),
      reload: vi.fn(),
      prefetch: vi.fn(),
    },

    usePage: vi.fn(),
  };
});

beforeAll(async () => {
  /**
   * Asynchronously load faker before any tests run. `createFactory()` helpers
   * assume faker is loaded in memory and will throw an error if it's not.
   */
  await loadFaker();

  /**
   * Wait for i18n to be initialized. Without this, tests using useTranslation
   * may fail in CI due to a race condition where the hook runs before i18n is ready.
   */
  if (!i18n.isInitialized) {
    await new Promise<void>((resolve) => {
      i18n.on('initialized', resolve);
    });
  }
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

  /**
   * happy-dom doesn't define window.confirm, which causes
   * vi.spyOn(window, 'confirm') to fail in Vitest 4+.
   */
  if (!window.confirm) {
    window.confirm = () => false;
  }
});

// We'll directly dump all arguments given to Ziggy's route() function.
vi.mock('ziggy-js', () => ({
  // eslint-disable-next-line @typescript-eslint/no-explicit-any -- tests are ok
  route: vi.fn((...args: any[]) => {
    // If called with no arguments, return an object with current() method.
    if (args.length === 0) {
      return {
        current: vi.fn(() => 'some.route'),
        queryParams: {},
      };
    }

    // Otherwise, return the arguments as before for compatibility.
    return args;
  }),
}));

/**
 * Vitest 4's vi.fn() doesn't wrap arrow functions as constructable.
 * The library's setup passes an arrow fn to the mock factory, so we
 * wrap it in a regular function to keep `new IntersectionObserver()` working.
 */
beforeEach(() => {
  // @ts-expect-error -- the library's type signature is narrower than what we need here.
  setupIntersectionMocking((impl: (...args: any[]) => any) =>
    vi.fn(function (this: any, ...args: any[]) {
      return impl.apply(this, args);
    }),
  );
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

  // Tear down the IntersectionObserver mock between tests.
  resetIntersectionMocking();

  // Vitest 4's restoreAllMocks() only restores spied-on functions.
  // clearAllMocks() is needed to also reset standalone vi.fn() call history
  // (eg: the router mock's visit/replace/reload functions).
  vi.clearAllMocks();
  vi.restoreAllMocks();

  // If any fake dates or times are in place, remove them after each test.
  vi.useRealTimers();
});
