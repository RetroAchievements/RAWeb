import { router } from '@inertiajs/react';

import { act, renderHook } from '@/test';

import { currentTabAtom } from '../state/games.atoms';
import { useGameShowTabs } from './useGameShowTabs';

describe('Hook: useGameShowTabs', () => {
  let originalLocation: Location;

  beforeEach(() => {
    vi.spyOn(router, 'replace').mockImplementation(() => {});

    originalLocation = window.location;
    delete (window as any).location;

    (window.location as any) = {
      ...originalLocation,
      href: 'https://retroachievements.org/game/123',
      pathname: '/game/123',
      search: '',
    } as Location;
  });

  afterEach(() => {
    (window.location as any) = originalLocation;

    vi.restoreAllMocks();
  });

  it('returns the correct function definitions', () => {
    // ACT
    const { result } = renderHook(() => useGameShowTabs(), {
      jotaiAtoms: [
        [currentTabAtom, 'achievements'],
        //
      ],
    });

    // ASSERT
    expect(result.current.currentTab).toEqual('achievements');
    expect(typeof result.current.setCurrentTab).toEqual('function');
  });

  it('given the user sets the tab to info, updates the atom and adds the tab param to the URL', () => {
    // ARRANGE
    const { result } = renderHook(() => useGameShowTabs(), {
      jotaiAtoms: [
        [currentTabAtom, 'achievements'],
        //
      ],
    });

    // ACT
    act(() => {
      result.current.setCurrentTab('info');
    });

    // ASSERT
    expect(result.current.currentTab).toEqual('info');
    expect(router.replace).toHaveBeenCalledWith({
      url: 'https://retroachievements.org/game/123?tab=info', // !! full URL because we use new URL()
      preserveScroll: true,
      preserveState: true,
    });
  });

  it('given the user sets the tab to stats, updates the atom and adds the tab param to the URL', () => {
    // ARRANGE
    const { result } = renderHook(() => useGameShowTabs(), {
      jotaiAtoms: [
        [currentTabAtom, 'achievements'],
        //
      ],
    });

    // ACT
    act(() => {
      result.current.setCurrentTab('stats');
    });

    // ASSERT
    expect(result.current.currentTab).toEqual('stats');
    expect(router.replace).toHaveBeenCalledWith({
      url: 'https://retroachievements.org/game/123?tab=stats', // !! full URL because we use new URL()
      preserveScroll: true,
      preserveState: true,
    });
  });

  it('given the user sets the tab to community, updates the atom and adds the tab param to the URL', () => {
    // ARRANGE
    const { result } = renderHook(() => useGameShowTabs(), {
      jotaiAtoms: [
        [currentTabAtom, 'achievements'],
        //
      ],
    });

    // ACT
    act(() => {
      result.current.setCurrentTab('community');
    });

    // ASSERT
    expect(result.current.currentTab).toEqual('community');
    expect(router.replace).toHaveBeenCalledWith({
      url: 'https://retroachievements.org/game/123?tab=community', // !! full URL because we use new URL()
      preserveScroll: true,
      preserveState: true,
    });
  });

  it('given the user sets the tab to achievements, updates the atom and removes the tab param from the URL', () => {
    // ARRANGE
    (window.location as any).href = 'https://retroachievements.org/game/123?tab=info'; // !! include query param in href
    window.location.search = '?tab=info';
    const { result } = renderHook(() => useGameShowTabs(), {
      jotaiAtoms: [
        [currentTabAtom, 'info'],
        //
      ],
    });

    // ACT
    act(() => {
      result.current.setCurrentTab('achievements');
    });

    // ASSERT
    expect(result.current.currentTab).toEqual('achievements');
    expect(router.replace).toHaveBeenCalledWith({
      url: 'https://retroachievements.org/game/123', // !! tab param removed
      preserveScroll: true,
      preserveState: true,
    });
  });

  it('given there are existing query params and the user sets a non-achievements tab, preserves those existing params', () => {
    // ARRANGE
    (window.location as any).href = 'https://retroachievements.org/game/123?foo=bar&baz=qux'; // !! include query params in href
    window.location.search = '?foo=bar&baz=qux';
    const { result } = renderHook(() => useGameShowTabs(), {
      jotaiAtoms: [
        [currentTabAtom, 'achievements'],
        //
      ],
    });

    // ACT
    act(() => {
      result.current.setCurrentTab('stats');
    });

    // ASSERT
    expect(router.replace).toHaveBeenCalledWith({
      url: 'https://retroachievements.org/game/123?foo=bar&baz=qux&tab=stats', // !! preserves existing params
      preserveScroll: true,
      preserveState: true,
    });
  });

  it('given there are existing query params including tab and the user sets to achievements, removes only the tab param', () => {
    // ARRANGE
    (window.location as any).href =
      'https://retroachievements.org/game/123?foo=bar&tab=stats&baz=qux'; // !! include query params in href
    window.location.search = '?foo=bar&tab=stats&baz=qux';
    const { result } = renderHook(() => useGameShowTabs(), {
      jotaiAtoms: [
        [currentTabAtom, 'stats'],
        //
      ],
    });

    // ACT
    act(() => {
      result.current.setCurrentTab('achievements');
    });

    // ASSERT
    expect(router.replace).toHaveBeenCalledWith({
      url: 'https://retroachievements.org/game/123?foo=bar&baz=qux', // !! only tab param removed
      preserveScroll: true,
      preserveState: true,
    });
  });

  it('given shouldPushHistory is true, uses router.visit instead of router.replace', () => {
    // ARRANGE
    vi.spyOn(router, 'visit').mockImplementation(() => {});

    const { result } = renderHook(() => useGameShowTabs(), {
      jotaiAtoms: [
        [currentTabAtom, 'achievements'],
        //
      ],
    });

    // ACT
    act(() => {
      result.current.setCurrentTab('community', { shouldPushHistory: true });
    });

    // ASSERT
    expect(router.visit).toHaveBeenCalledWith(
      'https://retroachievements.org/game/123?tab=community',
      {
        preserveScroll: true,
        preserveState: true,
      },
    );
    expect(router.replace).not.toHaveBeenCalled();
  });

  it('given the URL has a tab param on mount, syncs the atom to match the URL', () => {
    // ARRANGE
    (window.location as any).href = 'https://retroachievements.org/game/123?tab=community';
    window.location.search = '?tab=community';

    // ACT
    const { result } = renderHook(() => useGameShowTabs(), {
      jotaiAtoms: [
        [currentTabAtom, 'achievements'], // !! starts as achievements
        //
      ],
    });

    // ASSERT
    expect(result.current.currentTab).toEqual('community'); // synced to URL
  });

  it('given a popstate event fires, syncs the atom to match the new URL', () => {
    // ARRANGE
    (window.location as any).href = 'https://retroachievements.org/game/123?tab=community';
    window.location.search = '?tab=community';

    const { result } = renderHook(() => useGameShowTabs(), {
      jotaiAtoms: [
        [currentTabAtom, 'achievements'],
        //
      ],
    });

    // ... sanity check - should have synced on mount ...
    expect(result.current.currentTab).toEqual('community');

    // ACT
    // ... simulate a browser back navigation by changing the URL and firing popstate ...
    (window.location as any).href = 'https://retroachievements.org/game/123';
    window.location.search = '';

    act(() => {
      window.dispatchEvent(new PopStateEvent('popstate'));
    });

    // ASSERT
    expect(result.current.currentTab).toEqual('achievements');
  });
});
