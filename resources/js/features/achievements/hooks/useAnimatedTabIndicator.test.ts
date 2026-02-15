import { act, renderHook } from '@/test';

import { useAnimatedTabIndicator } from './useAnimatedTabIndicator';

describe('Hook: useAnimatedTabIndicator', () => {
  beforeEach(() => {
    vi.useFakeTimers();
    vi.clearAllTimers();
  });

  afterEach(() => {
    vi.runOnlyPendingTimers();
    vi.useRealTimers();
  });

  it('initializes without crashing', () => {
    // ARRANGE
    const { result } = renderHook(() => useAnimatedTabIndicator(0));

    // ASSERT
    expect(result.current).toBeTruthy();
  });

  it('given an initial index of 2, sets the active index to 2', () => {
    // ARRANGE
    const { result } = renderHook(() => useAnimatedTabIndicator(2));

    // ASSERT
    expect(result.current.activeIndex).toEqual(2);
  });

  it('given a negative initial index, sets the active index to 0', () => {
    // ARRANGE
    const { result } = renderHook(() => useAnimatedTabIndicator(-3));

    // ASSERT
    expect(result.current.activeIndex).toEqual(0);
  });

  it('given the hook is initialized, returns an empty tabRefs array', () => {
    // ARRANGE
    const { result } = renderHook(() => useAnimatedTabIndicator(0));

    // ASSERT
    expect(result.current.tabRefs.current).toEqual([]);
  });

  it('given the hook is initialized, starts with animation not ready', () => {
    // ARRANGE
    const { result } = renderHook(() => useAnimatedTabIndicator(0));

    // ASSERT
    expect(result.current.isAnimationReady).toEqual(false);
  });

  it('given the hook is initialized, starts hidden until the first real DOM measurement', () => {
    // ARRANGE
    const { result } = renderHook(() => useAnimatedTabIndicator(0));

    // ASSERT
    expect(result.current.activeIndicatorStyles).toEqual({
      transform: 'translateX(0px) translateY(40px)',
      width: '0px',
      opacity: 0,
      contain: 'layout',
    });
  });

  it('given the hook is initialized, hoveredIndex defaults to null', () => {
    // ARRANGE
    const { result } = renderHook(() => useAnimatedTabIndicator(0));

    // ASSERT
    expect(result.current.hoveredIndex).toEqual(null);
  });

  it('given the user changes the active index, updates the active index state', () => {
    // ARRANGE
    const { result } = renderHook(() => useAnimatedTabIndicator(0));

    // ACT
    act(() => {
      result.current.setActiveIndex(2);
    });

    // ASSERT
    expect(result.current.activeIndex).toEqual(2);
  });

  it('given there are multiple tab elements, positions the active indicator correctly for different active indices', () => {
    // ARRANGE
    const { result } = renderHook(() => useAnimatedTabIndicator(0));

    const mockElements = [
      { offsetLeft: 0, offsetWidth: 70, offsetTop: 0, offsetHeight: 30 } as HTMLElement,
      { offsetLeft: 74, offsetWidth: 110, offsetTop: 0, offsetHeight: 30 } as HTMLElement,
      { offsetLeft: 188, offsetWidth: 85, offsetTop: 0, offsetHeight: 30 } as HTMLElement,
    ];

    act(() => {
      result.current.tabRefs.current = mockElements;
    });

    // ACT
    act(() => {
      result.current.setActiveIndex(1);
    });

    act(() => {
      vi.advanceTimersByTime(50);
    });

    // ASSERT
    expect(result.current.activeIndicatorStyles.transform).toContain('translateX(74px)');
    expect(result.current.activeIndicatorStyles.width).toEqual('110px');
  });

  it('given the user hovers over a tab, updates hoveredIndex immediately', () => {
    // ARRANGE
    const { result } = renderHook(() => useAnimatedTabIndicator(0));

    // ACT
    act(() => {
      result.current.setHoveredIndex(1);
    });

    // ASSERT
    expect(result.current.hoveredIndex).toEqual(1);
  });

  it('given the user stops hovering, debounces the null update', () => {
    // ARRANGE
    const { result } = renderHook(() => useAnimatedTabIndicator(0));

    act(() => {
      result.current.setHoveredIndex(0);
    });

    // ACT
    act(() => {
      result.current.setHoveredIndex(null);
    });

    // ASSERT
    // The null is debounced, so hoveredIndex should still be 0.
    expect(result.current.hoveredIndex).toEqual(0);

    // After the debounce delay, it should become null.
    act(() => {
      vi.advanceTimersByTime(75);
    });

    expect(result.current.hoveredIndex).toEqual(null);
  });

  it('given the user moves between tabs quickly, cancels the pending null debounce', () => {
    // ARRANGE
    const { result } = renderHook(() => useAnimatedTabIndicator(0));

    act(() => {
      result.current.setHoveredIndex(0);
    });

    // ACT
    act(() => {
      result.current.setHoveredIndex(null);
    });

    act(() => {
      result.current.setHoveredIndex(1);
    });

    act(() => {
      vi.advanceTimersByTime(75);
    });

    // ASSERT
    // The null was cancelled by entering tab 1.
    expect(result.current.hoveredIndex).toEqual(1);
  });

  it('given no width is available on the active element, sets active indicator opacity to 0', () => {
    // ARRANGE
    const { result } = renderHook(() => useAnimatedTabIndicator(1));

    const mockElements = [
      { offsetLeft: 0, offsetWidth: 0, offsetTop: 0, offsetHeight: 30 } as HTMLElement,
      { offsetLeft: 74, offsetWidth: 110, offsetTop: 0, offsetHeight: 30 } as HTMLElement,
    ];

    act(() => {
      result.current.tabRefs.current = mockElements;
    });

    // ACT
    act(() => {
      result.current.setActiveIndex(0);
    });

    act(() => {
      vi.advanceTimersByTime(50);
    });

    // ASSERT
    expect(result.current.activeIndicatorStyles.opacity).toEqual(0);
    expect(result.current.activeIndicatorStyles.width).toEqual('0px');
  });

  it('given the active tab element has a nonzero offset, positions the active indicator below it', () => {
    // ARRANGE
    const { result } = renderHook(() => useAnimatedTabIndicator(1));

    const mockElements = [
      { offsetLeft: 10, offsetWidth: 70, offsetTop: 5, offsetHeight: 28 } as HTMLElement,
      { offsetLeft: 84, offsetWidth: 90, offsetTop: 5, offsetHeight: 28 } as HTMLElement,
    ];

    act(() => {
      result.current.tabRefs.current = mockElements;
    });

    // ACT
    act(() => {
      result.current.setActiveIndex(0);
    });

    act(() => {
      vi.advanceTimersByTime(50);
    });

    // ASSERT
    // The active indicator top should be offsetTop + offsetHeight = 5 + 28 = 33.
    expect(result.current.activeIndicatorStyles.transform).toContain('translateY(33px)');
  });
});
