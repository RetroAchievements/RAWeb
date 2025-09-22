import { act, renderHook } from '@/test';

import { currentTabAtom } from '../state/games.atoms';
import { useGameShowTabs } from './useGameShowTabs';

describe('Hook: useGameShowTabs', () => {
  let originalLocation: Location;

  beforeEach(() => {
    vi.spyOn(window.history, 'replaceState').mockImplementation(() => {});

    originalLocation = window.location;
    delete (window as any).location;

    (window.location as any) = {
      ...originalLocation,
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
      jotaiAtoms: [[currentTabAtom, 'achievements']],
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
    expect(window.history.replaceState).toHaveBeenCalledWith(null, '', '/game/123?tab=info');
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
    expect(window.history.replaceState).toHaveBeenCalledWith(null, '', '/game/123?tab=stats');
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
    expect(window.history.replaceState).toHaveBeenCalledWith(null, '', '/game/123?tab=community');
  });

  it('given the user sets the tab to achievements, updates the atom and removes the tab param from the URL', () => {
    // ARRANGE
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
    expect(window.history.replaceState).toHaveBeenCalledWith(null, '', '/game/123');
  });

  it('given there are existing query params and the user sets a non-achievements tab, preserves those existing params', () => {
    // ARRANGE
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
    expect(window.history.replaceState).toHaveBeenCalledWith(
      null,
      '',
      '/game/123?foo=bar&baz=qux&tab=stats',
    );
  });

  it('given there are existing query params including tab and the user sets to achievements, removes only the tab param', () => {
    // ARRANGE
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
    expect(window.history.replaceState).toHaveBeenCalledWith(null, '', '/game/123?foo=bar&baz=qux');
  });
});
