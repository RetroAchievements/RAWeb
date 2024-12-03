import axios from 'axios';

import { renderHook, waitFor } from '@/test';
import { createActivePlayer, createPaginatedData } from '@/test/factories';

import { useActivePlayersInfiniteQuery } from './useActivePlayersInfiniteQuery';

describe('Hook: useActivePlayersInfiniteQuery', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  afterEach(() => {
    vi.useRealTimers();
  });

  it('renders without crashing', () => {
    // ARRANGE
    const mockInitialData = createPaginatedData([createActivePlayer(), createActivePlayer()], {
      currentPage: 1,
      lastPage: 3,
      total: 6,
    });

    vi.spyOn(axios, 'get').mockResolvedValueOnce({ data: mockInitialData });

    const { result } = renderHook(() => useActivePlayersInfiniteQuery({ isEnabled: true }));

    // ASSERT
    expect(result.current).toBeDefined();
  });

  it('makes the initial API call with correct parameters', async () => {
    // ARRANGE
    const mockInitialData = createPaginatedData([createActivePlayer(), createActivePlayer()], {
      currentPage: 1,
      lastPage: 3,
      total: 6,
    });

    const getSpy = vi.spyOn(axios, 'get').mockResolvedValueOnce({ data: mockInitialData });

    renderHook(() =>
      useActivePlayersInfiniteQuery({ isEnabled: true, search: 'test', perPage: 100 }),
    );

    // ASSERT
    expect(getSpy).toHaveBeenCalledWith([
      'api.active-player.index',
      { page: 1, perPage: 100, search: 'test' },
    ]);
  });

  it('uses provided initial data correctly', () => {
    // ARRANGE
    const mockInitialData = createPaginatedData([createActivePlayer(), createActivePlayer()], {
      currentPage: 1,
      lastPage: 3,
      total: 6,
    });

    const mockSecondPageData = createPaginatedData([createActivePlayer(), createActivePlayer()], {
      currentPage: 2,
      lastPage: 3,
      total: 6,
    });

    vi.spyOn(axios, 'get').mockResolvedValueOnce({ data: mockSecondPageData });

    const { result } = renderHook(() =>
      useActivePlayersInfiniteQuery({
        isEnabled: true,
        initialData: mockInitialData,
      }),
    );

    // ASSERT
    expect(result.current.data?.pages[0]).toEqual(mockInitialData);
    expect(result.current.data?.pageParams).toEqual([1]);
  });

  it('can correctly fetch the next page', async () => {
    // ARRANGE
    const mockInitialData = createPaginatedData([createActivePlayer(), createActivePlayer()], {
      currentPage: 1,
      lastPage: 3,
      total: 6,
    });

    const mockSecondPageData = createPaginatedData([createActivePlayer(), createActivePlayer()], {
      currentPage: 2,
      lastPage: 3,
      total: 6,
    });

    const getSpy = vi
      .spyOn(axios, 'get')
      .mockResolvedValueOnce({ data: mockInitialData })
      .mockResolvedValueOnce({ data: mockSecondPageData });

    const { result } = renderHook(() => useActivePlayersInfiniteQuery({ isEnabled: true }));

    // ACT
    await waitFor(() => {
      // avoid a race condition
      expect(result.current.data?.pages[0]).toBeDefined();
    });

    await result.current.fetchNextPage();

    // ASSERT
    expect(getSpy).toHaveBeenCalledTimes(2);
    expect(getSpy).toHaveBeenLastCalledWith(['api.active-player.index', { page: 2, perPage: 100 }]);
  });

  it('respects the isEnabled flag', () => {
    // ARRANGE
    const getSpy = vi.spyOn(axios, 'get');

    renderHook(() => useActivePlayersInfiniteQuery({ isEnabled: false }));

    // ASSERT
    expect(getSpy).not.toHaveBeenCalled();
  });

  it('does not overfetch when reaching the last page', async () => {
    // ARRANGE
    const mockLastPageData = createPaginatedData([createActivePlayer(), createActivePlayer()], {
      currentPage: 2, // !!
      lastPage: 2, // !!
      total: 4,
    });

    vi.spyOn(axios, 'get').mockResolvedValueOnce({ data: mockLastPageData });

    const { result } = renderHook(() => useActivePlayersInfiniteQuery({ isEnabled: true }));

    // ASSERT
    await waitFor(() => {
      expect(result.current.hasNextPage).toEqual(false);
    });
  });

  it('given at least five pages are loaded, applies the correct stale time (infinity)', async () => {
    // ARRANGE
    vi.useFakeTimers();

    const mockInitialData = createPaginatedData([createActivePlayer(), createActivePlayer()], {
      currentPage: 1,
      lastPage: 8,
      total: 6,
    });
    const mockSecondPageData = createPaginatedData([createActivePlayer(), createActivePlayer()], {
      currentPage: 2,
      lastPage: 8,
      total: 6,
    });
    const mockThirdPageData = createPaginatedData([createActivePlayer(), createActivePlayer()], {
      currentPage: 2,
      lastPage: 8,
      total: 6,
    });
    const mockFourthPageData = createPaginatedData([createActivePlayer(), createActivePlayer()], {
      currentPage: 2,
      lastPage: 8,
      total: 6,
    });
    const mockFifthPageData = createPaginatedData([createActivePlayer(), createActivePlayer()], {
      currentPage: 2,
      lastPage: 8,
      total: 6,
    });

    const getSpy = vi
      .spyOn(axios, 'get')
      .mockResolvedValueOnce({ data: mockInitialData })
      .mockResolvedValueOnce({ data: mockSecondPageData })
      .mockResolvedValueOnce({ data: mockThirdPageData })
      .mockResolvedValueOnce({ data: mockFourthPageData })
      .mockResolvedValueOnce({ data: mockFifthPageData });

    const { result } = renderHook(() => useActivePlayersInfiniteQuery({ isEnabled: true }));

    // ACT
    // ... load more pages ...
    await result.current.fetchNextPage();
    await result.current.fetchNextPage();
    await result.current.fetchNextPage();
    await result.current.fetchNextPage();
    await result.current.fetchNextPage();

    vi.advanceTimersByTime(10 * 60 * 1000);

    // ASSERT
    expect(getSpy).toHaveBeenCalledTimes(5);
  });
});
