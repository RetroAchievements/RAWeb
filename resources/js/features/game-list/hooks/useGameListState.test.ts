import { renderHook } from '@/test';
import { createPaginatedData, createZiggyProps } from '@/test/factories';

import { useGameListState } from './useGameListState';

describe('Hook: useGameListState', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { result } = renderHook(
      () => useGameListState(createPaginatedData([]), { canShowProgressColumn: true }),
      {
        pageProps: {
          ziggy: createZiggyProps(),
        },
      },
    );

    // ASSERT
    expect(result).toBeDefined();
  });

  it('given paginatedGames, correctly sets the initial pagination state', () => {
    // ARRANGE
    const paginatedGames = createPaginatedData([], { currentPage: 1, perPage: 25 });

    const { result } = renderHook(
      () => useGameListState(createPaginatedData([]), { canShowProgressColumn: true }),
      {
        initialProps: paginatedGames,
        pageProps: {
          ziggy: createZiggyProps(),
        },
      },
    );

    // ASSERT
    expect(result.current.pagination).toEqual({ pageIndex: 0, pageSize: 25 });
  });

  it('given no sort param, correctly sets the initial sorting state', () => {
    // ARRANGE
    const paginatedGames = createPaginatedData([], { currentPage: 1, perPage: 25 });

    const { result } = renderHook(
      () => useGameListState(createPaginatedData([]), { canShowProgressColumn: true }),
      {
        initialProps: paginatedGames,
        pageProps: {
          ziggy: createZiggyProps(),
        },
      },
    );

    // ASSERT
    expect(result.current.sorting).toEqual([{ id: 'title', desc: false }]);
  });

  it('given a sort param, correctly sets the initial sorting state', () => {
    // ARRANGE
    const paginatedGames = createPaginatedData([], { currentPage: 1, perPage: 25 });

    const { result } = renderHook(
      () => useGameListState(createPaginatedData([]), { canShowProgressColumn: true }),
      {
        initialProps: paginatedGames,
        pageProps: {
          ziggy: createZiggyProps({ query: { sort: 'system' } }),
        },
      },
    );

    // ASSERT
    expect(result.current.sorting).toEqual([{ id: 'system', desc: false }]);
  });

  it('given an array sort param from the browser, bails to a sane default sort value', () => {
    // ARRANGE
    const paginatedGames = createPaginatedData([], { currentPage: 1, perPage: 25 });

    const { result } = renderHook(
      () => useGameListState(createPaginatedData([]), { canShowProgressColumn: true }),
      {
        initialProps: paginatedGames,
        pageProps: {
          ziggy: createZiggyProps({ query: [] as any }),
        },
      },
    );

    // ASSERT
    expect(result.current.sorting).toEqual([{ id: 'title', desc: false }]);
  });

  it('given a negative sort param, correctly sets the initial sorting state', () => {
    // ARRANGE
    const paginatedGames = createPaginatedData([], { currentPage: 1, perPage: 25 });

    const { result } = renderHook(
      () => useGameListState(createPaginatedData([]), { canShowProgressColumn: true }),
      {
        initialProps: paginatedGames,
        pageProps: {
          ziggy: createZiggyProps({ query: { sort: '-title' } }),
        },
      },
    );

    // ASSERT
    expect(result.current.sorting).toEqual([{ id: 'title', desc: true }]);
  });

  it('given no filter param, correctly sets the initial columnFilters state', () => {
    // ARRANGE
    const paginatedGames = createPaginatedData([], { currentPage: 1, perPage: 25 });

    const { result } = renderHook(
      () => useGameListState(createPaginatedData([]), { canShowProgressColumn: true }),
      {
        initialProps: paginatedGames,
        pageProps: {
          ziggy: createZiggyProps(),
        },
      },
    );

    // ASSERT
    expect(result.current.columnFilters).toEqual([]);
  });

  it('given a single filter param, correctly sets the initial columnFilters state', () => {
    // ARRANGE
    const paginatedGames = createPaginatedData([], { currentPage: 1, perPage: 25 });

    const { result } = renderHook(
      () => useGameListState(createPaginatedData([]), { canShowProgressColumn: true }),
      {
        initialProps: paginatedGames,
        pageProps: {
          ziggy: createZiggyProps({
            query: {
              filter: { system: '1,5' },
            },
          }),
        },
      },
    );

    // ASSERT
    expect(result.current.columnFilters).toEqual([{ id: 'system', value: ['1', '5'] }]);
  });

  it('given multiple filter params, correctly sets the initial columnFilters state', () => {
    // ARRANGE
    const paginatedGames = createPaginatedData([], { currentPage: 1, perPage: 25 });

    const { result } = renderHook(
      () => useGameListState(createPaginatedData([]), { canShowProgressColumn: true }),
      {
        initialProps: paginatedGames,
        pageProps: {
          ziggy: createZiggyProps({
            query: {
              filter: { system: '1', achievementsPublished: 'has' },
            },
          }),
        },
      },
    );

    // ASSERT
    expect(result.current.columnFilters).toEqual([
      { id: 'system', value: ['1'] },
      { id: 'achievementsPublished', value: ['has'] },
    ]);
  });

  it('given default column filters, correctly sets those implicit filters', () => {
    // ARRANGE
    const paginatedGames = createPaginatedData([], { currentPage: 1, perPage: 25 });

    const { result } = renderHook(
      () =>
        useGameListState(createPaginatedData([]), {
          canShowProgressColumn: true,
          defaultColumnFilters: [{ id: 'system', value: ['10'] }],
        }),
      {
        initialProps: paginatedGames,
        pageProps: {
          ziggy: createZiggyProps(),
        },
      },
    );

    // ASSERT
    expect(result.current.columnFilters).toEqual([{ id: 'system', value: ['10'] }]);
  });

  it('given default column filters, does not override existing set filter id values', () => {
    // ARRANGE
    const paginatedGames = createPaginatedData([], { currentPage: 1, perPage: 25 });

    const { result } = renderHook(
      () =>
        useGameListState(createPaginatedData([]), {
          canShowProgressColumn: true,
          defaultColumnFilters: [{ id: 'system', value: ['10'] }],
        }),
      {
        initialProps: paginatedGames,
        pageProps: {
          ziggy: createZiggyProps({
            query: {
              filter: { system: '1' },
            },
          }),
        },
      },
    );

    // ASSERT
    expect(result.current.columnFilters).toEqual([{ id: 'system', value: ['1'] }]);
  });

  it('given the canShowProgressColumn option is truthy, enables progress column visibility by default', () => {
    // ARRANGE
    const { result } = renderHook(
      () => useGameListState(createPaginatedData([]), { canShowProgressColumn: true }),
      {
        pageProps: {
          ziggy: createZiggyProps(),
        },
      },
    );

    // ASSERT
    expect(result.current.columnVisibility.progress).toBeTruthy();
    expect(result.current.columnVisibility.playersTotal).toBeFalsy();
  });

  it('given the canShowProgressColumn option is not truthy, enables players total column visibility by default', () => {
    // ARRANGE
    const { result } = renderHook(
      () => useGameListState(createPaginatedData([]), { canShowProgressColumn: false }),
      {
        pageProps: {
          ziggy: createZiggyProps(),
        },
      },
    );

    // ASSERT
    expect(result.current.columnVisibility.progress).toBeFalsy();
    expect(result.current.columnVisibility.playersTotal).toBeTruthy();
  });
});
