// eslint-disable-next-line no-restricted-imports -- fine in a test
import * as InertiajsReact from '@inertiajs/react';

import { act, renderHook } from '@/test';
import { createLeaderboard } from '@/test/factories';

import { currentListViewAtom, currentPlayableListSortAtom } from '../state/games.atoms';
import { useCurrentListView } from './useCurrentListView';

describe('Hook: useCurrentListView', () => {
  let mockRouterReload: ReturnType<typeof vi.fn>;

  beforeEach(() => {
    vi.clearAllMocks();

    mockRouterReload = vi.fn();
    vi.spyOn(InertiajsReact.router, 'reload').mockImplementation(mockRouterReload);
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  it('is defined', () => {
    // ASSERT
    expect(useCurrentListView).toBeDefined();
  });

  it('given the user is switching to leaderboards and allLeaderboards is undefined, fetches the leaderboards', () => {
    // ARRANGE
    const { result } = renderHook(() => useCurrentListView(), {
      pageProps: {
        allLeaderboards: undefined, // !! deferred data not loaded yet
      },
      jotaiAtoms: [
        [currentListViewAtom, 'achievements'],
        [currentPlayableListSortAtom, 'normal'],
        //
      ],
    });

    // ACT
    act(() => {
      result.current.setCurrentListView('leaderboards');
    });

    // ASSERT
    expect(mockRouterReload).toHaveBeenCalledWith({ only: ['allLeaderboards'] });
  });

  it('given the user is switching to leaderboards and allLeaderboards is loaded, does not fetch leaderboards', () => {
    // ARRANGE
    const mockLeaderboards = [createLeaderboard(), createLeaderboard()];

    const { result } = renderHook(() => useCurrentListView(), {
      pageProps: {
        allLeaderboards: mockLeaderboards, // !! data already loaded
      },
      jotaiAtoms: [
        [currentListViewAtom, 'achievements'],
        [currentPlayableListSortAtom, 'normal'],
        //
      ],
    });

    // ACT
    act(() => {
      result.current.setCurrentListView('leaderboards');
    });

    // ASSERT
    expect(mockRouterReload).not.toHaveBeenCalled();
  });

  it('given the user is switching to achievements, does not refetch leaderboards', () => {
    // ARRANGE
    const { result } = renderHook(() => useCurrentListView(), {
      pageProps: {
        allLeaderboards: undefined,
      },
      jotaiAtoms: [
        [currentListViewAtom, 'leaderboards'],
        [currentPlayableListSortAtom, 'displayOrder'],
        //
      ],
    });

    // ACT
    act(() => {
      result.current.setCurrentListView('achievements');
    });

    // ASSERT
    expect(mockRouterReload).not.toHaveBeenCalled();
  });
});
