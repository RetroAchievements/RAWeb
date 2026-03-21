import { act, renderHook } from '@/test';

import { useHoverCardClickSuppression } from './useHoverCardClickSuppression';

describe('Hook: useHoverCardClickSuppression', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { result } = renderHook(() => useHoverCardClickSuppression());

    // ASSERT
    expect(result.current).toBeTruthy();
    expect(result.current.openHoverCard).toEqual(null);
    expect(result.current.handleHoverCardOpenChange).toBeInstanceOf(Function);
    expect(result.current.handleTabClick).toBeInstanceOf(Function);
    expect(result.current.handlePointerLeave).toBeInstanceOf(Function);
  });

  it('given handleHoverCardOpenChange is called with isOpen true, opens the hover card', () => {
    // ARRANGE
    const { result } = renderHook(() => useHoverCardClickSuppression());

    // ACT
    act(() => {
      result.current.handleHoverCardOpenChange(2, true);
    });

    // ASSERT
    expect(result.current.openHoverCard).toEqual(2);
  });

  it('given handleHoverCardOpenChange is called with isOpen false, closes the hover card', () => {
    // ARRANGE
    const { result } = renderHook(() => useHoverCardClickSuppression());

    // ... start by opening a hover card ...
    act(() => {
      result.current.handleHoverCardOpenChange(1, true);
    });

    // ACT
    act(() => {
      result.current.handleHoverCardOpenChange(1, false);
    });

    // ASSERT
    expect(result.current.openHoverCard).toEqual(null);
  });

  it('given a tab is clicked, closes any open hover card', () => {
    // ARRANGE
    const { result } = renderHook(() => useHoverCardClickSuppression());

    // ... start by opening a hover card ...
    act(() => {
      result.current.handleHoverCardOpenChange(0, true);
    });
    expect(result.current.openHoverCard).toEqual(0);

    // ACT
    act(() => {
      result.current.handleTabClick(0, {
        ctrlKey: false,
        metaKey: false,
        button: 0,
      } as React.MouseEvent);
    });

    // ASSERT
    expect(result.current.openHoverCard).toEqual(null);
  });

  it('given a tab is clicked, suppresses that tab from reopening', () => {
    // ARRANGE
    const { result } = renderHook(() => useHoverCardClickSuppression());

    // ... click the tab to suppress it ...
    act(() => {
      result.current.handleTabClick(1, {
        ctrlKey: false,
        metaKey: false,
        button: 0,
      } as React.MouseEvent);
    });

    // ACT
    // ... try to open the hover card for that tab ...
    act(() => {
      result.current.handleHoverCardOpenChange(1, true); // same index
    });

    // ASSERT
    // ... the hover card should not open because the tab was clicked ...
    expect(result.current.openHoverCard).toEqual(null);
  });

  it('given a tab is clicked and then the pointer leaves, allows the hover card to reopen after timeout', () => {
    // ARRANGE
    vi.useFakeTimers();
    const { result } = renderHook(() => useHoverCardClickSuppression());

    // ... click the tab to suppress it ...
    act(() => {
      result.current.handleTabClick(2, {
        ctrlKey: false,
        metaKey: false,
        button: 0,
      } as React.MouseEvent);
    });

    // ... verify it's suppressed ...
    act(() => {
      result.current.handleHoverCardOpenChange(2, true);
    });
    expect(result.current.openHoverCard).toEqual(null);

    // ... the pointer now leaves the tab ...
    act(() => {
      result.current.handlePointerLeave(2);
    });

    // ... try to open immediately (should still be suppressed) ...
    act(() => {
      result.current.handleHoverCardOpenChange(2, true);
    });
    expect(result.current.openHoverCard).toEqual(null); // !! still suppressed

    // ACT
    // ... advance time past the timeout ...
    act(() => {
      vi.advanceTimersByTime(500);
    });

    // ... try to open the hover card again ...
    act(() => {
      result.current.handleHoverCardOpenChange(2, true);
    });

    // ASSERT
    // ... now it should open ...
    expect(result.current.openHoverCard).toEqual(2);

    vi.useRealTimers();
  });

  it('given a tab is clicked and pointer leaves immediately, suppresses hover card from reopening', () => {
    // ARRANGE
    vi.useFakeTimers();
    const { result } = renderHook(() => useHoverCardClickSuppression());

    // ACT
    // ... click the tab ...
    act(() => {
      result.current.handleTabClick(1, {
        ctrlKey: false,
        metaKey: false,
        button: 0,
      } as React.MouseEvent);
    });

    // ... pointer leaves immediately ...
    act(() => {
      result.current.handlePointerLeave(1);
    });

    // ... hover card tries to open (simulating BaseHoverCard's delayed open) ...
    act(() => {
      result.current.handleHoverCardOpenChange(1, true);
    });

    // ASSERT
    // ... the hover card should still be suppressed ...
    expect(result.current.openHoverCard).toEqual(null);

    vi.useRealTimers();
  });

  it('given a tab is clicked again before the suppression timeout completes, clears the existing timeout', () => {
    // ARRANGE
    vi.useFakeTimers();
    const { result } = renderHook(() => useHoverCardClickSuppression());

    // ... click the tab ...
    act(() => {
      result.current.handleTabClick(0, {
        ctrlKey: false,
        metaKey: false,
        button: 0,
      } as React.MouseEvent);
    });

    // ... pointer leaves (starts 500ms timeout) ...
    act(() => {
      result.current.handlePointerLeave(0);
    });

    // ACT
    // ... click the same tab again before timeout completes ...
    act(() => {
      result.current.handleTabClick(0, {
        ctrlKey: false,
        metaKey: false,
        button: 0,
      } as React.MouseEvent);
    });

    // ... advance time past what would have been the original timeout ...
    act(() => {
      vi.advanceTimersByTime(500);
    });

    // ASSERT
    // ... the tab should still be suppressed because the new click reset the state ...
    act(() => {
      result.current.handleHoverCardOpenChange(0, true);
    });
    expect(result.current.openHoverCard).toEqual(null); // !! still suppressed

    vi.useRealTimers();
  });

  it('given the user ctrl+clicks a tab, does not suppress the hover card or change tabs', () => {
    // ARRANGE
    const onTabChange = vi.fn();
    const { result } = renderHook(() => useHoverCardClickSuppression({ onTabChange }));

    // ... open a hover card first ...
    act(() => {
      result.current.handleHoverCardOpenChange(0, true);
    });
    expect(result.current.openHoverCard).toEqual(0);

    // ACT
    act(() => {
      result.current.handleTabClick(1, {
        ctrlKey: true,
        metaKey: false,
        button: 0,
      } as React.MouseEvent);
    });

    // ASSERT
    expect(onTabChange).not.toHaveBeenCalled();
    expect(result.current.openHoverCard).toEqual(0);
  });

  it('given the user meta+clicks a tab, does not suppress the hover card or change tabs', () => {
    // ARRANGE
    const onTabChange = vi.fn();
    const { result } = renderHook(() => useHoverCardClickSuppression({ onTabChange }));

    // ACT
    act(() => {
      result.current.handleTabClick(1, {
        ctrlKey: false,
        metaKey: true,
        button: 0,
      } as React.MouseEvent);
    });

    // ASSERT
    expect(onTabChange).not.toHaveBeenCalled();
  });
});
