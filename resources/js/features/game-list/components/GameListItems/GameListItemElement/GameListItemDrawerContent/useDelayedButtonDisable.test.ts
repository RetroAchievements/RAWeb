import { act, renderHook } from '@/test';

import { useDelayedButtonDisable } from './useDelayedButtonDisable';

describe('Hook: useDelayedButtonDisable', () => {
  beforeEach(() => {
    vi.useFakeTimers();
  });

  afterEach(() => {
    vi.useRealTimers();
  });

  it('renders without crashing', () => {
    // ARRANGE
    const { result } = renderHook(() => useDelayedButtonDisable(false));

    // ASSERT
    expect(result).toBeTruthy();
  });

  it('returns a boolean indicating if the button should be disabled', () => {
    // ARRANGE
    const { result } = renderHook(() => useDelayedButtonDisable(false));

    // ASSERT
    expect(typeof result.current).toBe('boolean');
  });

  it('disables the button immediately when the isPending argument becomes truthy', () => {
    // ARRANGE
    const { result, rerender } = renderHook(({ isPending }) => useDelayedButtonDisable(isPending), {
      initialProps: { isPending: false },
    });

    // ACT
    rerender({ isPending: true });

    // ASSERT
    expect(result.current).toBeTruthy();
  });

  it('keeps the button disabled for the specified delay after isPending becomes falsy', async () => {
    // ARRANGE
    const delay = 1000;
    const { result, rerender } = renderHook(
      ({ isPending }) => useDelayedButtonDisable(isPending, delay),
      {
        initialProps: { isPending: true },
      },
    );

    rerender({ isPending: false });

    // ACT
    // Fast-forward time by delay minus 1ms.
    act(() => {
      vi.advanceTimersByTime(delay - 1);
    });
    expect(result.current).toBe(true);

    // Fast-forward remaining time.
    act(() => {
      vi.advanceTimersByTime(1);
    });

    // ASSERT
    expect(result.current).toBeFalsy();
  });
});
