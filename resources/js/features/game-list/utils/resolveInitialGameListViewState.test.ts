import type { TableState } from '@tanstack/react-table';

import { createPaginatedData, createZiggyProps } from '@/test/factories';

import { resolveInitialGameListViewState } from './resolveInitialGameListViewState';

describe('Util: resolveInitialGameListViewState', () => {
  it('given query values exist, resolves them ahead of persisted preferences and defaults', () => {
    // ARRANGE
    const persistedViewPreferences: Partial<TableState> = {
      columnFilters: [{ id: 'system', value: ['3'] }],
      columnVisibility: { playersTotal: true },
      pagination: { pageIndex: 4, pageSize: 50 },
      sorting: [{ id: 'lastUpdated', desc: true }],
    };

    // ACT
    const result = resolveInitialGameListViewState({
      paginatedData: createPaginatedData([], { currentPage: 2, perPage: 25 }),
      query: createZiggyProps({
        query: {
          filter: { system: '1,2' },
          sort: '-title',
        },
      }).query,
      persistedViewPreferences,
      defaultColumnFilters: [{ id: 'achievementsPublished', value: ['has'] }],
      defaultColumnSort: { id: 'playersTotal', desc: true },
      defaultColumnVisibility: { progress: false },
    });

    // ASSERT
    expect(result.columnFilters).toEqual([
      { id: 'system', value: ['1', '2'] },
      { id: 'achievementsPublished', value: ['has'] },
    ]);
    expect(result.columnVisibility).toEqual({ progress: false, playersTotal: true });
    expect(result.pagination).toEqual({ pageIndex: 1, pageSize: 50 });
    expect(result.sorting).toEqual([{ id: 'title', desc: true }]);
  });

  it('given query params are absent, falls back to persisted preferences', () => {
    // ARRANGE
    const persistedViewPreferences: Partial<TableState> = {
      columnFilters: [{ id: 'status', value: ['active'] }],
      pagination: { pageIndex: 9, pageSize: 100 },
      sorting: [{ id: 'lastUpdated', desc: true }],
    };

    // ACT
    const result = resolveInitialGameListViewState({
      paginatedData: createPaginatedData([], { currentPage: 3, perPage: 25 }),
      query: createZiggyProps().query,
      persistedViewPreferences,
      defaultColumnFilters: [{ id: 'achievementsPublished', value: ['has'] }],
      defaultColumnSort: { id: 'title', desc: false },
      defaultColumnVisibility: { progress: false },
    });

    // ASSERT
    expect(result.columnFilters).toEqual([{ id: 'status', value: ['active'] }]);
    expect(result.pagination).toEqual({ pageIndex: 2, pageSize: 100 });
    expect(result.sorting).toEqual([{ id: 'lastUpdated', desc: true }]);
  });

  it('given query params and persisted preferences are absent, falls back to defaults', () => {
    // ARRANGE
    const paginatedData = createPaginatedData([], { currentPage: 4, perPage: 200 });

    // ACT
    const result = resolveInitialGameListViewState({
      paginatedData,
      query: createZiggyProps().query,
      persistedViewPreferences: null,
      defaultColumnFilters: [{ id: 'achievementsPublished', value: ['has'] }],
      defaultColumnSort: { id: 'playersTotal', desc: true },
      defaultColumnVisibility: { progress: false },
    });

    // ASSERT
    expect(result.columnFilters).toEqual([{ id: 'achievementsPublished', value: ['has'] }]);
    expect(result.columnVisibility).toEqual({ progress: false });
    expect(result.pagination).toEqual({ pageIndex: 3, pageSize: 200 });
    expect(result.sorting).toEqual([{ id: 'playersTotal', desc: true }]);
  });

  it('given persisted preferences exist without pagination, falls back to server pagination', () => {
    // ARRANGE
    const paginatedData = createPaginatedData([], { currentPage: 5, perPage: 100 });

    // ACT
    const result = resolveInitialGameListViewState({
      paginatedData,
      query: createZiggyProps().query,
      persistedViewPreferences: {
        columnFilters: [{ id: 'status', value: ['active'] }],
      },
      defaultColumnFilters: [{ id: 'achievementsPublished', value: ['has'] }],
      defaultColumnSort: { id: 'playersTotal', desc: true },
    });

    // ASSERT
    expect(result.pagination).toEqual({ pageIndex: 4, pageSize: 100 });
  });

  it('given a non-string sort query param, returns an empty sorting state', () => {
    // ARRANGE
    const paginatedData = createPaginatedData([]);

    // ACT
    const result = resolveInitialGameListViewState({
      paginatedData,
      query: createZiggyProps({ query: { sort: { nested: true } as any } }).query,
      persistedViewPreferences: null,
    });

    // ASSERT
    expect(result.sorting).toEqual([]);
  });

  it('given an ascending sort query param, resolves ascending sorting', () => {
    // ARRANGE
    const paginatedData = createPaginatedData([]);

    // ACT
    const result = resolveInitialGameListViewState({
      paginatedData,
      query: createZiggyProps({ query: { sort: 'system' } }).query,
      persistedViewPreferences: null,
    });

    // ASSERT
    expect(result.sorting).toEqual([{ id: 'system', desc: false }]);
  });

  it('given the filter query param is not a string record, falls back safely', () => {
    // ARRANGE
    const paginatedData = createPaginatedData([]);

    // ACT
    const result = resolveInitialGameListViewState({
      paginatedData,
      query: createZiggyProps({ query: { filter: [] as any } }).query,
      persistedViewPreferences: null,
      defaultColumnFilters: [{ id: 'achievementsPublished', value: ['has'] }],
    });

    // ASSERT
    expect(result.columnFilters).toEqual([{ id: 'achievementsPublished', value: ['has'] }]);
  });

  it('given query-derived filters exist, merges non-overlapping default filters', () => {
    // ARRANGE
    const paginatedData = createPaginatedData([]);

    // ACT
    const result = resolveInitialGameListViewState({
      paginatedData,
      query: createZiggyProps({
        query: {
          filter: { system: '1' },
        },
      }).query,
      persistedViewPreferences: null,
      defaultColumnFilters: [{ id: 'status', value: ['active'] }],
    });

    // ASSERT
    expect(result.columnFilters).toEqual([
      { id: 'system', value: ['1'] },
      { id: 'status', value: ['active'] },
    ]);
  });

  it('given the query already contains a default filter, does not duplicate it', () => {
    // ARRANGE
    const paginatedData = createPaginatedData([]);

    // ACT
    const result = resolveInitialGameListViewState({
      paginatedData,
      query: createZiggyProps({
        query: {
          filter: { system: '1' },
        },
      }).query,
      persistedViewPreferences: null,
      defaultColumnFilters: [{ id: 'system', value: ['10'] }],
    });

    // ASSERT
    expect(result.columnFilters).toEqual([{ id: 'system', value: ['1'] }]);
  });
});
