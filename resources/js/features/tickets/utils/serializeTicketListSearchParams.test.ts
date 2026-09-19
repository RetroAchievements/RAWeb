import type { ColumnFiltersState } from '@tanstack/react-table';

import { serializeTicketListSearchParams } from './serializeTicketListSearchParams';

const serverDefaultColumnFilters: ColumnFiltersState = [{ id: 'status', value: ['unresolved'] }];

describe('Util: serializeTicketListSearchParams', () => {
  it('is defined', () => {
    // ASSERT
    expect(serializeTicketListSearchParams).toBeDefined();
  });

  it('given page and filter state, serializes them while omitting defaults', () => {
    // ACT
    const result = serializeTicketListSearchParams({
      serverDefaultSortParam: '-createdAt',
      currentSearch: '',
      columnFilters: [
        { id: 'status', value: ['resolved'] },
        { id: 'emulator', value: ['RetroArch'] },
      ],
      sortParam: '-createdAt',
      pageNumber: 2,
      serverDefaultColumnFilters,
    });

    // ASSERT
    expect(result.toString()).toEqual(
      'page%5Bnumber%5D=2&filter%5Bstatus%5D=resolved&filter%5Bemulator%5D=RetroArch',
    );
  });

  it('given every value equals its default, serializes to an empty string', () => {
    // ACT
    const result = serializeTicketListSearchParams({
      serverDefaultSortParam: '-createdAt',
      currentSearch: '?filter[status]=resolved&page[number]=3',
      columnFilters: serverDefaultColumnFilters,
      sortParam: '-createdAt',
      pageNumber: 1,
      serverDefaultColumnFilters,
    });

    // ASSERT
    expect(result.toString()).toEqual('');
  });

  it('given a filter kind is no longer active, removes it from the current search', () => {
    // ACT
    const result = serializeTicketListSearchParams({
      serverDefaultSortParam: '-createdAt',
      currentSearch: '?filter[emulator]=RetroArch',
      columnFilters: [{ id: 'status', value: ['resolved'] }],
      sortParam: '-createdAt',
      pageNumber: 1,
      serverDefaultColumnFilters,
    });

    // ASSERT
    expect(result.toString()).toEqual('filter%5Bstatus%5D=resolved');
  });

  it('given a filter with an empty value, removes it from the current search', () => {
    // ACT
    const result = serializeTicketListSearchParams({
      serverDefaultSortParam: '-createdAt',
      currentSearch: '?filter[emulator]=RetroArch',
      columnFilters: [{ id: 'emulator', value: [] }],
      sortParam: '-createdAt',
      pageNumber: 1,
      serverDefaultColumnFilters,
    });

    // ASSERT
    expect(result.toString()).toEqual('');
  });

  it('given a filter value stored as a plain string, serializes it as-is', () => {
    // ACT
    const result = serializeTicketListSearchParams({
      serverDefaultSortParam: '-createdAt',
      currentSearch: '',
      columnFilters: [{ id: 'status', value: 'all' }],
      sortParam: '-createdAt',
      pageNumber: 1,
      serverDefaultColumnFilters,
    });

    // ASSERT
    expect(result.toString()).toEqual('filter%5Bstatus%5D=all');
  });

  it('given no defaults are provided, treats every active filter as non-default', () => {
    // ACT
    const result = serializeTicketListSearchParams({
      serverDefaultSortParam: '-createdAt',
      currentSearch: '',
      columnFilters: serverDefaultColumnFilters,
      sortParam: '-createdAt',
      pageNumber: 1,
    });

    // ASSERT
    expect(result.toString()).toEqual('filter%5Bstatus%5D=unresolved');
  });

  it('given the URL has a param the list does not even own, leaves it alone', () => {
    // ACT
    const result = serializeTicketListSearchParams({
      serverDefaultSortParam: '-createdAt',
      currentSearch: '?highlight=17',
      columnFilters: serverDefaultColumnFilters,
      sortParam: '-createdAt',
      pageNumber: 2,
      serverDefaultColumnFilters,
    });

    // ASSERT
    expect(result.toString()).toEqual('highlight=17&page%5Bnumber%5D=2');
  });

  it('serializes a non-default sort', () => {
    // ACT
    const result = serializeTicketListSearchParams({
      serverDefaultSortParam: '-createdAt',
      currentSearch: '',
      columnFilters: serverDefaultColumnFilters,
      sortParam: 'state',
      pageNumber: 1,
      serverDefaultColumnFilters,
    });

    // ASSERT
    expect(result.toString()).toEqual('sort=state');
  });

  it('always omits the default sort', () => {
    // ACT
    const result = serializeTicketListSearchParams({
      serverDefaultSortParam: '-createdAt',
      currentSearch: '?sort=state',
      columnFilters: serverDefaultColumnFilters,
      sortParam: '-createdAt',
      pageNumber: 1,
      serverDefaultColumnFilters,
    });

    // ASSERT
    expect(result.toString()).toEqual('');
  });

  it('given the server default sort is resolution date, omits it and keeps a different sort in the URL', () => {
    // ACT
    const defaultSortResult = serializeTicketListSearchParams({
      serverDefaultSortParam: '-resolvedAt',
      columnFilters: [],
      sortParam: '-resolvedAt',
      pageNumber: 1,
    });
    const changedSortResult = serializeTicketListSearchParams({
      serverDefaultSortParam: '-resolvedAt',
      columnFilters: [],
      sortParam: '-createdAt',
      pageNumber: 1,
    });

    // ASSERT
    expect(defaultSortResult.has('sort')).toEqual(false);
    expect(changedSortResult.get('sort')).toEqual('-createdAt');
  });
});
