import { act, renderHook } from '@/test';

import { useTabIndicator } from './useTabIndicator';

describe('Hook: useTabIndicator', () => {
  beforeEach(() => {
    vi.useFakeTimers();
    vi.clearAllTimers();
  });

  afterEach(() => {
    vi.runOnlyPendingTimers();
  });

  it('initializes without crashing', () => {
    // ARRANGE
    const { result } = renderHook(() => useTabIndicator(0));

    // ASSERT
    expect(result.current).toBeTruthy();
  });

  it('given an initial index of 2, sets the active index to 2', () => {
    // ARRANGE
    const { result } = renderHook(() => useTabIndicator(2));

    // ASSERT
    expect(result.current.activeIndex).toEqual(2);
  });

  it('given a negative initial index, sets the active index to 0', () => {
    // ARRANGE
    const { result } = renderHook(() => useTabIndicator(-5));

    // ASSERT
    expect(result.current.activeIndex).toEqual(0);
  });

  it('given the hook is initialized, returns an empty tabRefs array', () => {
    // ARRANGE
    const { result } = renderHook(() => useTabIndicator(0));

    // ASSERT
    expect(result.current.tabRefs.current).toEqual([]);
  });

  it('given the hook is initialized, starts with animation not ready', () => {
    // ARRANGE
    const { result } = renderHook(() => useTabIndicator(0));

    // ASSERT
    expect(result.current.isAnimationReady).toEqual(false);
  });

  it('given the hook is initialized, returns initial indicator styles with zero opacity', () => {
    // ARRANGE
    const { result } = renderHook(() => useTabIndicator(0));

    // ASSERT
    expect(result.current.indicatorStyles).toEqual({
      transform: 'translateX(0px) translateY(0px)',
      width: '0px',
      opacity: 0,
      contain: 'layout',
    });
  });

  it('given the user changes the active index, updates the active index state', () => {
    // ARRANGE
    const { result } = renderHook(() => useTabIndicator(0));

    // ACT
    act(() => {
      result.current.setActiveIndex(3);
    });

    // ASSERT
    expect(result.current.activeIndex).toEqual(3);
  });

  it('given the active tab element is not available, does not update indicator styles', () => {
    // ARRANGE
    const { result } = renderHook(() => useTabIndicator(0));

    const initialStyles = result.current.indicatorStyles;

    // ACT
    act(() => {
      result.current.setActiveIndex(0);
    });

    // ASSERT
    expect(result.current.indicatorStyles).toEqual(initialStyles);
  });

  it('given there are multiple tab elements, positions the indicator correctly for different active indices', () => {
    // ARRANGE
    const { result } = renderHook(() => useTabIndicator(0));

    const mockElements = [
      { offsetLeft: 0, offsetWidth: 60 } as HTMLDivElement,
      { offsetLeft: 60, offsetWidth: 80 } as HTMLDivElement, // !!
      { offsetLeft: 140, offsetWidth: 100 } as HTMLDivElement,
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
    expect(result.current.indicatorStyles.transform).toContain('translateX(60px)');
    expect(result.current.indicatorStyles.width).toEqual('80px');
  });

  it('given no width is available, sets opacity to 0', () => {
    // ARRANGE
    const { result } = renderHook(() => useTabIndicator(0));

    const mockElement = {
      offsetLeft: 50,
      offsetWidth: 0, // !!
    } as HTMLDivElement;

    // ACT
    act(() => {
      result.current.tabRefs.current[0] = mockElement;
      result.current.setActiveIndex(0);
    });

    // ASSERT
    expect(result.current.indicatorStyles.opacity).toEqual(0);
    expect(result.current.indicatorStyles.width).toEqual('0px');
  });
});
