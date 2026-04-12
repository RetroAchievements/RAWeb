import type { ColumnFiltersState, SortingState, VisibilityState } from '@tanstack/react-table';

import { buildPersistedGameListViewState } from './buildPersistedGameListViewState';

describe('Util: buildPersistedGameListViewState', () => {
  it('given a title filter is present, excludes it and resets the page index', () => {
    // ARRANGE
    const columnFilters: ColumnFiltersState = [
      { id: 'system', value: ['1'] },
      { id: 'title', value: 'mario' }, // !!
    ];
    const columnVisibility: VisibilityState = { system: false };
    const sorting: SortingState = [{ id: 'title', desc: true }];

    // ACT
    const result = buildPersistedGameListViewState({
      columnFilters,
      columnVisibility,
      pagination: { pageIndex: 4, pageSize: 50 },
      sorting,
    });

    // ASSERT
    expect(result).toEqual({
      columnFilters: [{ id: 'system', value: ['1'] }], // !! no title filter
      columnVisibility: { system: false },
      pagination: { pageIndex: 0, pageSize: 50 },
      sorting: [{ id: 'title', desc: true }],
    });
  });
});
