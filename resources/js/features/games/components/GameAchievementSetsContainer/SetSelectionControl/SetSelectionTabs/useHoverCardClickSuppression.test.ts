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
      result.current.handleTabClick(0);
    });

    // ASSERT
    expect(result.current.openHoverCard).toEqual(null);
  });

  it('given a tab is clicked, suppresses that tab from reopening', () => {
    // ARRANGE
    const { result } = renderHook(() => useHoverCardClickSuppression());

    // ... click the tab to suppress it ...
    act(() => {
      result.current.handleTabClick(1);
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

  it('given a tab is clicked and then the pointer leaves, allows the hover card to reopen', () => {
    // ARRANGE
    const { result } = renderHook(() => useHoverCardClickSuppression());

    // ... click the tab to suppress it ...
    act(() => {
      result.current.handleTabClick(2);
    });

    // ... verify it's suppressed ...
    act(() => {
      result.current.handleHoverCardOpenChange(2, true);
    });
    expect(result.current.openHoverCard).toEqual(null);

    // ACT
    // ... the pointer now leaves the tab ...
    act(() => {
      result.current.handlePointerLeave(2);
    });

    // ... try to open the hover card again ...
    act(() => {
      result.current.handleHoverCardOpenChange(2, true);
    });

    // ASSERT
    // ... now it should open ...
    expect(result.current.openHoverCard).toEqual(2);
  });
});
