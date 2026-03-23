// eslint-disable-next-line no-restricted-imports -- fine in a test
import * as InertiajsReact from '@inertiajs/react';

import { renderHook } from '@/test';

import { useReloadDeferredOnBackForward } from './useReloadDeferredOnBackForward';

describe('Hook: useReloadDeferredOnBackForward', () => {
  let mockGetEntriesByType: ReturnType<typeof vi.fn<(type: string) => PerformanceEntryList>>;
  let mockRouterReload: ReturnType<typeof vi.fn<() => void>>;

  beforeEach(() => {
    vi.clearAllMocks();

    mockGetEntriesByType = vi.fn();
    global.performance.getEntriesByType = mockGetEntriesByType;

    mockRouterReload = vi.fn();
    vi.spyOn(InertiajsReact.router, 'reload').mockImplementation(mockRouterReload);
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  it('is defined', () => {
    // ASSERT
    expect(useReloadDeferredOnBackForward).toBeDefined();
  });

  it('given navigation type is back_forward and a prop is undefined, reloads with the prop name', () => {
    // ARRANGE
    mockGetEntriesByType.mockReturnValue([{ type: 'back_forward' } as PerformanceNavigationTiming]);

    // ACT
    renderHook(() => useReloadDeferredOnBackForward({ recentUnlocks: undefined }));

    // ASSERT
    expect(mockRouterReload).toHaveBeenCalledWith({ only: ['recentUnlocks'] });
  });

  it('given navigation type is navigate, does not reload', () => {
    // ARRANGE
    mockGetEntriesByType.mockReturnValue([{ type: 'navigate' } as PerformanceNavigationTiming]);

    // ACT
    renderHook(() => useReloadDeferredOnBackForward({ recentUnlocks: undefined }));

    // ASSERT
    expect(mockRouterReload).not.toHaveBeenCalled();
  });

  it('given navigation type is back_forward but all props are defined, does not reload', () => {
    // ARRANGE
    mockGetEntriesByType.mockReturnValue([{ type: 'back_forward' } as PerformanceNavigationTiming]);

    // ACT
    renderHook(() => useReloadDeferredOnBackForward({ recentUnlocks: [{ id: 1 }] }));

    // ASSERT
    expect(mockRouterReload).not.toHaveBeenCalled();
  });

  it('given multiple props with some undefined, reloads only the undefined ones', () => {
    // ARRANGE
    mockGetEntriesByType.mockReturnValue([{ type: 'back_forward' } as PerformanceNavigationTiming]);

    // ACT
    renderHook(() =>
      useReloadDeferredOnBackForward({
        recentUnlocks: undefined,
        allLeaderboards: [{ id: 1 }],
        changelog: undefined,
      }),
    );

    // ASSERT
    expect(mockRouterReload).toHaveBeenCalledWith({ only: ['recentUnlocks', 'changelog'] });
  });

  it('given the performance API is not available, does not crash', () => {
    // ARRANGE
    // @ts-expect-error -- testing browser compatibility
    global.performance.getEntriesByType = undefined;

    // ASSERT
    expect(() => {
      renderHook(() => useReloadDeferredOnBackForward({ recentUnlocks: undefined }));
    }).not.toThrow();

    expect(mockRouterReload).not.toHaveBeenCalled();
  });

  it('given performance.getEntriesByType returns an empty array, does not reload', () => {
    // ARRANGE
    mockGetEntriesByType.mockReturnValue([]);

    // ACT
    renderHook(() => useReloadDeferredOnBackForward({ recentUnlocks: undefined }));

    // ASSERT
    expect(mockRouterReload).not.toHaveBeenCalled();
  });

  it('given a prop changes from undefined to loaded, does not trigger a duplicate reload', () => {
    // ARRANGE
    mockGetEntriesByType.mockReturnValue([{ type: 'back_forward' } as PerformanceNavigationTiming]);

    const { rerender } = renderHook(
      ({ data }: { data: unknown }) => useReloadDeferredOnBackForward({ recentUnlocks: data }),
      { initialProps: { data: undefined as unknown } },
    );

    expect(mockRouterReload).toHaveBeenCalledTimes(1);

    // ACT
    rerender({ data: [{ id: 1 }] });

    // ASSERT
    expect(mockRouterReload).toHaveBeenCalledTimes(1);
  });
});
