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

  it('given persisted view preferences exist, uses those for initial pagination state', () => {
    // ARRANGE
    const paginatedGames = createPaginatedData([], { currentPage: 1, perPage: 25 });
    const persistedViewPreferences = {
      pagination: { pageIndex: 2, pageSize: 50 },
    };

    const { result } = renderHook(
      () => useGameListState(createPaginatedData([]), { canShowProgressColumn: true }),
      {
        initialProps: paginatedGames,
        pageProps: {
          persistedViewPreferences,
          ziggy: createZiggyProps(),
        },
      },
    );

    // ASSERT
    expect(result.current.pagination).toEqual({ pageIndex: 2, pageSize: 50 });
  });

  it('given persisted view preferences exist and there is no sort query param, uses those for initial sorting state', () => {
    // ARRANGE
    const persistedViewPreferences = {
      sorting: [{ id: 'lastUpdated', desc: true }],
    };

    const { result } = renderHook(
      () => useGameListState(createPaginatedData([]), { canShowProgressColumn: true }),
      {
        pageProps: {
          persistedViewPreferences,
          ziggy: createZiggyProps({
            query: {}, // !!
          }),
        },
      },
    );

    // ASSERT
    expect(result.current.sorting).toEqual([{ id: 'lastUpdated', desc: true }]);
  });

  it('given both query params and persisted preferences exist, query params take priority for sorting', () => {
    // ARRANGE
    const persistedViewPreferences = {
      sorting: [{ id: 'lastUpdated', desc: true }],
    };

    const { result } = renderHook(
      () => useGameListState(createPaginatedData([]), { canShowProgressColumn: true }),
      {
        pageProps: {
          persistedViewPreferences,
          ziggy: createZiggyProps({
            query: {
              sort: '-system', // !!
            },
          }),
        },
      },
    );

    // ASSERT
    expect(result.current.sorting).toEqual([{ id: 'system', desc: true }]);
  });

  it('given persisted view preferences exist, uses those for initial column visibility state', () => {
    // ARRANGE
    const persistedViewPreferences = {
      columnVisibility: {
        hasActiveOrInReviewClaims: true,
        lastUpdated: true,
        numUnresolvedTickets: true,
        numVisibleLeaderboards: true,
        playersTotal: true,
        progress: false,
      },
    };

    const { result } = renderHook(
      () => useGameListState(createPaginatedData([]), { canShowProgressColumn: true }),
      {
        pageProps: {
          persistedViewPreferences,
          ziggy: createZiggyProps(),
        },
      },
    );

    // ASSERT
    expect(result.current.columnVisibility).toEqual(persistedViewPreferences.columnVisibility);
  });

  it('given persisted view preferences exist, uses those for initial column filters state', () => {
    // ARRANGE
    const persistedViewPreferences = {
      columnFilters: [
        { id: 'system', value: ['1', '2'] },
        { id: 'status', value: ['active'] },
      ],
    };

    const { result } = renderHook(
      () => useGameListState(createPaginatedData([]), { canShowProgressColumn: true }),
      {
        pageProps: {
          persistedViewPreferences,
          ziggy: createZiggyProps({
            query: {},
          }),
        },
      },
    );

    // ASSERT
    expect(result.current.columnFilters).toEqual([
      { id: 'system', value: ['1', '2'] },
      { id: 'status', value: ['active'] },
    ]);
  });

  it('given persisted view preferences exist but are null, falls back to defaults', () => {
    // ARRANGE
    const { result } = renderHook(
      () => useGameListState(createPaginatedData([]), { canShowProgressColumn: true }),
      {
        pageProps: {
          persistedViewPreferences: null,
          ziggy: createZiggyProps(),
        },
      },
    );

    // ASSERT
    expect(result.current.sorting).toEqual([{ id: 'title', desc: false }]);
    expect(result.current.columnFilters).toEqual([]);
    expect(result.current.columnVisibility).toEqual({
      hasActiveOrInReviewClaims: false,
      lastUpdated: false,
      numUnresolvedTickets: false,
      numVisibleLeaderboards: false,
      playersTotal: false,
      progress: true,
    });
  });

  it('given both query params and persisted preferences exist, query params should take precedence for all state', () => {
    // ARRANGE
    const persistedViewPreferences = {
      pagination: { pageIndex: 2, pageSize: 50 },
      sorting: [{ id: 'lastUpdated', desc: true }],
      columnFilters: [{ id: 'system', value: ['3', '4'] }],
    };

    const { result } = renderHook(
      () => useGameListState(createPaginatedData([]), { canShowProgressColumn: true }),
      {
        pageProps: {
          persistedViewPreferences,
          ziggy: createZiggyProps({
            query: {
              page: '1', // !!
              sort: '-title', // !!
              filter: { system: '1,2' }, // !!
            },
          }),
        },
      },
    );

    // ASSERT - these values come from query params!!
    expect(result.current.sorting).toEqual([{ id: 'title', desc: true }]);
    expect(result.current.columnFilters).toEqual([{ id: 'system', value: ['1', '2'] }]);
  });

  it('given filter params and non-overlapping default column filters, includes both in the initial state', () => {
    // ARRANGE
    const paginatedGames = createPaginatedData([], { currentPage: 1, perPage: 25 });

    const { result } = renderHook(
      () =>
        useGameListState(createPaginatedData([]), {
          canShowProgressColumn: true,
          defaultColumnFilters: [
            { id: 'status', value: ['active'] }, // !! different id than URL param
          ],
        }),
      {
        initialProps: paginatedGames,
        pageProps: {
          ziggy: createZiggyProps({
            query: {
              filter: { system: '1' }, // !! different id than default filter
            },
          }),
        },
      },
    );

    // ASSERT
    expect(result.current.columnFilters).toEqual([
      { id: 'system', value: ['1'] },
      { id: 'status', value: ['active'] },
    ]);
  });
});
