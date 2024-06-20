import '@testing-library/jest-dom/vitest';

import { cleanup } from '@testing-library/react';

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
