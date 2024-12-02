import {
  // @prettier-ignore
  afterAll,
  beforeAll,
  describe,
  expect,
  it,
  vi,
} from 'vitest';

import { safeRequestIdleCallback } from './safeRequestIdleCallback';

describe('Util: safeRequestIdleCallback', () => {
  let originalWindow: typeof window;

  beforeAll(() => {
    originalWindow = window;
  });

  afterAll(() => {
    global.window = originalWindow;
  });

  it('is defined #sanity', () => {
    expect(safeRequestIdleCallback).toBeDefined();
  });

  it('given requestIdleCallback is available, uses requestIdleCallback', () => {
    // ARRANGE
    const mockRequestIdleCallback = vi.fn();
    global.requestIdleCallback = mockRequestIdleCallback;

    const mockCallback = vi.fn();

    // ACT
    safeRequestIdleCallback(mockCallback);

    // ASSERT
    expect(mockRequestIdleCallback).toHaveBeenCalledWith(mockCallback);
  });

  it('given requestIdleCallback is unavailable, should invoke the callback directly', () => {
    // ARRANGE
    global.window = { ...originalWindow } as any;
    delete (window as any).requestIdleCallback;

    const mockCallback = vi.fn();

    // ACT
    safeRequestIdleCallback(mockCallback);

    // ASSERT
    expect(mockCallback).toHaveBeenCalled();
  });
});
