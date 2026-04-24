import type { ColumnFiltersState, SortingState } from '@tanstack/react-table';

import { serializeGameListViewState } from './serializeGameListViewState';

describe('Util: serializeGameListViewState', () => {
  it('given page, filter, and sort state, serializes them while omitting defaults', () => {
    // ARRANGE
    const columnFilters: ColumnFiltersState = [
      { id: 'achievementsPublished', value: ['has'] },
      { id: 'system', value: ['1', '5'] },
    ];
    const sorting: SortingState = [{ id: 'system', desc: true }];

    // ACT
    const result = serializeGameListViewState({
      currentSearch: '',
      columnFilters,
      pagination: { pageIndex: 1, pageSize: 50 },
      sorting,
      defaultColumnFilters: [{ id: 'achievementsPublished', value: ['has'] }],
      defaultColumnSort: { id: 'title', desc: false },
      defaultPageSize: 25,
    });

    // ASSERT
    expect(result.toString()).toBe(
      'page%5Bnumber%5D=2&page%5Bsize%5D=50&filter%5Bsystem%5D=1%2C5&sort=-system',
    );
  });

  it('given inactive filters exist in the current search params, removes them', () => {
    // ARRANGE
    const columnFilters: ColumnFiltersState = [{ id: 'system', value: '1' }];
    const sorting: SortingState = [{ id: 'title', desc: false }];

    // ACT
    const result = serializeGameListViewState({
      currentSearch: '?filter[oldParam]=value',
      columnFilters,
      pagination: { pageIndex: 0, pageSize: 25 },
      sorting,
      defaultColumnSort: { id: 'title', desc: false },
      defaultPageSize: 25,
    });

    // ASSERT
    expect(result.toString()).toBe('filter%5Bsystem%5D=1');
  });

  it('given no sorting is active, removes the sort param', () => {
    // ARRANGE
    const columnFilters: ColumnFiltersState = [{ id: 'achievementsPublished', value: ['has'] }];

    // ACT
    const result = serializeGameListViewState({
      currentSearch: '?sort=-system',
      columnFilters,
      pagination: { pageIndex: 0, pageSize: 25 },
      sorting: [],
      defaultColumnFilters: [{ id: 'achievementsPublished', value: ['has'] }],
      defaultColumnSort: { id: 'title', desc: false },
      defaultPageSize: 25,
    });

    // ASSERT
    expect(result.toString()).toBe('');
  });
});
