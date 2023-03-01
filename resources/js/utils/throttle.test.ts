import {
  afterEach,
  beforeEach,
  describe,
  expect,
  it,
  vi
} from 'vitest';

import { throttle } from './throttle';

describe('Util: throttle', () => {
  beforeEach(() => {
    vi.useFakeTimers();
  });

  afterEach(() => {
    vi.useRealTimers();
  });

  it('is defined #sanity', () => {
    expect(throttle).toBeDefined();
  });

  it('given a function, still allows that function to be called', () => {
    const fn = vi.fn();
    const throttledFn = throttle(fn, 100);

    throttledFn();
    vi.advanceTimersByTime(100);

    expect(fn).toHaveBeenCalledTimes(1);
  });

  it('given a function, only calls the function once within the throttle time window', () => {
    const fn = vi.fn();
    const throttledFn = throttle(fn, 100);

    throttledFn();
    throttledFn();
    throttledFn();
    vi.advanceTimersByTime(100);

    expect(fn).toHaveBeenCalledTimes(1);
  });

  it('correctly passes arguments to the original function', () => {
    const fn = vi.fn();
    const throttledFn = throttle(fn, 100);

    throttledFn('a', 'b', 'c');
    vi.advanceTimersByTime(100);

    expect(fn).toHaveBeenNthCalledWith(1, 'a', 'b', 'c');
  });

  it('should not block subsequent function calls after the throttle time window has elapsed', () => {
    const fn = vi.fn();
    const throttledFn = throttle(fn, 100);

    throttledFn();
    vi.advanceTimersByTime(100);
    throttledFn();
    vi.advanceTimersByTime(100);

    expect(fn).toHaveBeenCalledTimes(2);
  });
});
