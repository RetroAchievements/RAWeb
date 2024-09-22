import { renderHook } from '@/test';
import { createPaginatedData, createZiggyProps } from '@/test/factories';

import { useGameListState } from './useGameListState';

describe('Hook: useGameListState', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { result } = renderHook(() => useGameListState(createPaginatedData([])), {
      pageProps: {
        ziggy: createZiggyProps(),
      },
    });

    // ASSERT
    expect(result).toBeDefined();
  });

  it('given paginatedGames, correctly sets the initial pagination state', () => {
    // ARRANGE
    const paginatedGames = createPaginatedData([], { currentPage: 1, perPage: 25 });

    const { result } = renderHook(() => useGameListState(createPaginatedData([])), {
      initialProps: paginatedGames,
      pageProps: {
        ziggy: createZiggyProps(),
      },
    });

    // ASSERT
    const currentValue = result.current as ReturnType<typeof useGameListState>;

    expect(currentValue.pagination).toEqual({ pageIndex: 0, pageSize: 25 });
  });

  it('given no sort param, correctly sets the initial sorting state', () => {
    // ARRANGE
    const paginatedGames = createPaginatedData([], { currentPage: 1, perPage: 25 });

    const { result } = renderHook(() => useGameListState(createPaginatedData([])), {
      initialProps: paginatedGames,
      pageProps: {
        ziggy: createZiggyProps(),
      },
    });

    // ASSERT
    const currentValue = result.current as ReturnType<typeof useGameListState>;

    expect(currentValue.sorting).toEqual([]);
  });

  it('given a sort param, correctly sets the initial sorting state', () => {
    // ARRANGE
    const paginatedGames = createPaginatedData([], { currentPage: 1, perPage: 25 });

    const { result } = renderHook(() => useGameListState(createPaginatedData([])), {
      initialProps: paginatedGames,
      pageProps: {
        ziggy: createZiggyProps({ query: { sort: 'system' } }),
      },
    });

    // ASSERT
    const currentValue = result.current as ReturnType<typeof useGameListState>;

    expect(currentValue.sorting).toEqual([{ id: 'system', desc: false }]);
  });

  it('given a negative sort param, correctly sets the initial sorting state', () => {
    // ARRANGE
    const paginatedGames = createPaginatedData([], { currentPage: 1, perPage: 25 });

    const { result } = renderHook(() => useGameListState(createPaginatedData([])), {
      initialProps: paginatedGames,
      pageProps: {
        ziggy: createZiggyProps({ query: { sort: '-title' } }),
      },
    });

    // ASSERT
    const currentValue = result.current as ReturnType<typeof useGameListState>;

    expect(currentValue.sorting).toEqual([{ id: 'title', desc: true }]);
  });

  it('given no filter param, correctly sets the initial columnFilters state', () => {
    // ARRANGE
    const paginatedGames = createPaginatedData([], { currentPage: 1, perPage: 25 });

    const { result } = renderHook(() => useGameListState(createPaginatedData([])), {
      initialProps: paginatedGames,
      pageProps: {
        ziggy: createZiggyProps(),
      },
    });

    // ASSERT
    const currentValue = result.current as ReturnType<typeof useGameListState>;

    expect(currentValue.columnFilters).toEqual([]);
  });

  it('given a single filter param, correctly sets the initial columnFilters state', () => {
    // ARRANGE
    const paginatedGames = createPaginatedData([], { currentPage: 1, perPage: 25 });

    const { result } = renderHook(() => useGameListState(createPaginatedData([])), {
      initialProps: paginatedGames,
      pageProps: {
        ziggy: createZiggyProps({
          query: {
            filter: { system: '1,5' },
          },
        }),
      },
    });

    // ASSERT
    const currentValue = result.current as ReturnType<typeof useGameListState>;

    expect(currentValue.columnFilters).toEqual([{ id: 'system', value: ['1', '5'] }]);
  });

  it('given multiple filter params, correctly sets the initial columnFilters state', () => {
    // ARRANGE
    const paginatedGames = createPaginatedData([], { currentPage: 1, perPage: 25 });

    const { result } = renderHook(() => useGameListState(createPaginatedData([])), {
      initialProps: paginatedGames,
      pageProps: {
        ziggy: createZiggyProps({
          query: {
            filter: { system: '1', achievementsPublished: 'has' },
          },
        }),
      },
    });

    // ASSERT
    const currentValue = result.current as ReturnType<typeof useGameListState>;

    expect(currentValue.columnFilters).toEqual([
      { id: 'system', value: ['1'] },
      { id: 'achievementsPublished', value: ['has'] },
    ]);
  });
});
