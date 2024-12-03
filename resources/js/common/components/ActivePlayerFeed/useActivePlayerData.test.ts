import type { Mock } from 'vitest';

import { renderHook } from '@/test';
import { createActivePlayer, createPaginatedData } from '@/test/factories';

import { useActivePlayerData } from './useActivePlayerData';
import { useActivePlayersInfiniteQuery } from './useActivePlayersInfiniteQuery';

vi.mock('./useActivePlayersInfiniteQuery', () => ({
  useActivePlayersInfiniteQuery: vi.fn(),
}));

describe('Hook: useActivePlayerData', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('renders without crashing', () => {
    // ARRANGE
    (useActivePlayersInfiniteQuery as Mock).mockReturnValue({
      data: undefined,
      isLoading: false,
      fetchNextPage: vi.fn(),
    });

    const { result } = renderHook(() =>
      useActivePlayerData({
        initialActivePlayers: createPaginatedData([createActivePlayer(), createActivePlayer()]),
        isInfiniteQueryEnabled: false,
        searchValue: '',
      }),
    );

    // ASSERT
    expect(result.current).toBeDefined();
    expect(result.current.players).toBeDefined();
    expect(result.current.loadMore).toBeDefined();
  });

  it('given the infinite query is disabled, returns the initial active player list', () => {
    // ARRANGE
    (useActivePlayersInfiniteQuery as Mock).mockReturnValue({
      data: undefined,
      isLoading: false,
      fetchNextPage: vi.fn(),
    });

    const mockPlayers = [createActivePlayer(), createActivePlayer()];

    const { result } = renderHook(() =>
      useActivePlayerData({
        initialActivePlayers: createPaginatedData(mockPlayers),
        isInfiniteQueryEnabled: false,
        searchValue: '',
      }),
    );

    // ASSERT
    expect(result.current.players).toEqual(mockPlayers);
  });

  it('given the infinite query is enabled, configures it with the correct parameters', () => {
    // ARRANGE
    (useActivePlayersInfiniteQuery as Mock).mockReturnValue({
      data: undefined,
      isLoading: false,
      fetchNextPage: vi.fn(),
    });

    const mockPlayers = [createActivePlayer(), createActivePlayer()];

    renderHook(() =>
      useActivePlayerData({
        initialActivePlayers: createPaginatedData(mockPlayers),
        isInfiniteQueryEnabled: true,
        searchValue: '',
      }),
    );

    // ASSERT
    expect(useActivePlayersInfiniteQuery).toHaveBeenCalledWith({
      initialData: undefined,
      isEnabled: true,
      perPage: 100,
      search: '',
    });
  });

  it('given the infinite query is loading, returns the initial list of players', () => {
    // ARRANGE
    (useActivePlayersInfiniteQuery as Mock).mockReturnValue({
      data: undefined,
      isLoading: true,
      fetchNextPage: vi.fn(),
    });

    const mockPlayers = [createActivePlayer(), createActivePlayer()];

    const { result } = renderHook(() =>
      useActivePlayerData({
        initialActivePlayers: createPaginatedData(mockPlayers),
        isInfiniteQueryEnabled: true,
        searchValue: '',
      }),
    );

    // ASSERT
    expect(result.current.players).toEqual(mockPlayers);
  });

  it('given infinite query data is available, returns combined players from all pages', () => {
    // ARRANGE
    const mockPlayers = [createActivePlayer(), createActivePlayer()];
    const mockAdditionalPlayers = [createActivePlayer(), createActivePlayer()];

    const mockInfiniteData = {
      pages: [createPaginatedData(mockPlayers), createPaginatedData(mockAdditionalPlayers)],
      pageParams: [1, 2],
    };

    (useActivePlayersInfiniteQuery as Mock).mockReturnValue({
      data: mockInfiniteData, // !!
      isLoading: false, // !!
      fetchNextPage: vi.fn(),
    });

    const { result } = renderHook(() =>
      useActivePlayerData({
        initialActivePlayers: createPaginatedData(mockPlayers),
        isInfiniteQueryEnabled: true,
        searchValue: '',
      }),
    );

    // ASSERT
    expect(result.current.players).toEqual([...mockPlayers, ...mockAdditionalPlayers]);
  });

  it('provides a loadMore function that calls fetchNextPage', () => {
    // ARRANGE
    const mockPlayers = [createActivePlayer(), createActivePlayer()];
    const mockInfiniteData = {
      pages: [createPaginatedData(mockPlayers)],
      pageParams: [1],
    };

    const mockFetchNextPage = vi.fn();

    (useActivePlayersInfiniteQuery as Mock).mockReturnValue({
      data: mockInfiniteData,
      isLoading: false,
      fetchNextPage: mockFetchNextPage,
    });

    const { result } = renderHook(() =>
      useActivePlayerData({
        initialActivePlayers: createPaginatedData(mockPlayers),
        isInfiniteQueryEnabled: true,
        searchValue: '',
      }),
    );

    // ACT
    result.current.loadMore();

    // ASSERT
    expect(mockFetchNextPage).toHaveBeenCalledOnce();
  });
});
