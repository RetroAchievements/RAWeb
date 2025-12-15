// eslint-disable-next-line no-restricted-imports -- fine in a test
import * as InertiajsReact from '@inertiajs/react';

import { renderHook } from '@/test';
import { createLeaderboard } from '@/test/factories';

import { usePreloadDeferredLeaderboards } from './usePreloadDeferredLeaderboards';

describe('Hook: usePreloadDeferredLeaderboards', () => {
  let mockGetEntriesByType: ReturnType<typeof vi.fn<(type: string) => PerformanceEntryList>>;
  let mockRouterReload: ReturnType<typeof vi.fn<() => void>>;

  beforeEach(() => {
    vi.clearAllMocks();

    mockGetEntriesByType = vi.fn<(type: string) => PerformanceEntryList>();
    global.performance.getEntriesByType = mockGetEntriesByType;

    mockRouterReload = vi.fn<() => void>();
    vi.spyOn(InertiajsReact.router, 'reload').mockImplementation(mockRouterReload);
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  it('is defined', () => {
    // ASSERT
    expect(usePreloadDeferredLeaderboards).toBeDefined();
  });

  it('given navigation type is back_forward and numLeaderboards > 5 and data is undefined, fetches the leaderboards', () => {
    // ARRANGE
    mockGetEntriesByType.mockReturnValue([{ type: 'back_forward' } as PerformanceNavigationTiming]);

    // ACT
    renderHook(() => usePreloadDeferredLeaderboards(10, undefined)); // !! 10 leaderboards, no data loaded

    // ASSERT
    expect(mockRouterReload).toHaveBeenCalledWith();
  });

  it('given navigation type is navigate (a normal page load), does not fetch the leaderboards', () => {
    // ARRANGE
    mockGetEntriesByType.mockReturnValue([{ type: 'navigate' } as PerformanceNavigationTiming]);

    // ACT
    renderHook(() => usePreloadDeferredLeaderboards(10, undefined));

    // ASSERT
    expect(mockRouterReload).not.toHaveBeenCalled();
  });

  it('given navigation type is back_forward but numLeaderboards <= 5, does not fetch the leaderboards', () => {
    // ARRANGE
    mockGetEntriesByType.mockReturnValue([{ type: 'back_forward' } as PerformanceNavigationTiming]);

    // ACT
    renderHook(() => usePreloadDeferredLeaderboards(3, undefined)); // !! only 3 leaderboards

    // ASSERT
    expect(mockRouterReload).not.toHaveBeenCalled();
  });

  it('given navigation type is back_forward but numLeaderboards equals 5, does not fetch the leaderboards', () => {
    // ARRANGE
    mockGetEntriesByType.mockReturnValue([{ type: 'back_forward' } as PerformanceNavigationTiming]);

    // ACT
    renderHook(() => usePreloadDeferredLeaderboards(5, undefined)); // !! exactly 5 leaderboards

    // ASSERT
    expect(mockRouterReload).not.toHaveBeenCalled();
  });

  it('given navigation type is back_forward but allLeaderboards is already loaded, does not fetch the leaderboards', () => {
    // ARRANGE
    mockGetEntriesByType.mockReturnValue([{ type: 'back_forward' } as PerformanceNavigationTiming]);
    const mockLeaderboardsData = [createLeaderboard(), createLeaderboard()];

    // ACT
    renderHook(() => usePreloadDeferredLeaderboards(10, mockLeaderboardsData)); // !! data is already present

    // ASSERT
    expect(mockRouterReload).not.toHaveBeenCalled();
  });

  it('given the performance API is not available, does not crash', () => {
    // ARRANGE
    // @ts-expect-error -- testing browser compatibility
    global.performance.getEntriesByType = undefined; // !! browser doesn't support the performance API

    // ASSERT
    expect(() => {
      renderHook(() => usePreloadDeferredLeaderboards(10, undefined));
    }).not.toThrow();

    expect(mockRouterReload).not.toHaveBeenCalled();
  });

  it('given performance.getEntriesByType returns an empty array, does not fetch the leaderboards', () => {
    // ARRANGE
    mockGetEntriesByType.mockReturnValue([]); // !! no navigation entries

    // ACT
    renderHook(() => usePreloadDeferredLeaderboards(10, undefined));

    // ASSERT
    expect(mockRouterReload).not.toHaveBeenCalled();
  });

  it('given performance.getEntriesByType returns undefined, does not fetch the leaderboards', () => {
    // ARRANGE
    mockGetEntriesByType.mockReturnValue(undefined as unknown as PerformanceEntryList);

    // ACT
    renderHook(() => usePreloadDeferredLeaderboards(10, undefined));

    // ASSERT
    expect(mockRouterReload).not.toHaveBeenCalled();
  });

  it('given allLeaderboards changes from undefined to loaded, does not trigger a duplicate fetch', () => {
    // ARRANGE
    mockGetEntriesByType.mockReturnValue([{ type: 'back_forward' } as PerformanceNavigationTiming]);

    const { rerender } = renderHook(
      ({ data }: { data: unknown }) => usePreloadDeferredLeaderboards(10, data),
      { initialProps: { data: undefined as unknown } },
    );

    expect(mockRouterReload).toHaveBeenCalledTimes(1);

    // ACT
    // ... data loads and component re-renders ...
    rerender({ data: [createLeaderboard()] });

    // ASSERT
    // ... should not trigger another reload ...
    expect(mockRouterReload).toHaveBeenCalledTimes(1);
  });
});
