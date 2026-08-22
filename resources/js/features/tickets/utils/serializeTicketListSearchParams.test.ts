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
      currentSearch: '',
      columnFilters: [
        { id: 'status', value: ['resolved'] },
        { id: 'emulator', value: ['RetroArch'] },
      ],
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
      currentSearch: '?filter[status]=resolved&page[number]=3',
      columnFilters: serverDefaultColumnFilters,
      pageNumber: 1,
      serverDefaultColumnFilters,
    });

    // ASSERT
    expect(result.toString()).toEqual('');
  });

  it('given a filter kind is no longer active, removes it from the current search', () => {
    // ACT
    const result = serializeTicketListSearchParams({
      currentSearch: '?filter[emulator]=RetroArch',
      columnFilters: [{ id: 'status', value: ['resolved'] }],
      pageNumber: 1,
      serverDefaultColumnFilters,
    });

    // ASSERT
    expect(result.toString()).toEqual('filter%5Bstatus%5D=resolved');
  });

  it('given a filter with an empty value, removes it from the current search', () => {
    // ACT
    const result = serializeTicketListSearchParams({
      currentSearch: '?filter[emulator]=RetroArch',
      columnFilters: [{ id: 'emulator', value: [] }],
      pageNumber: 1,
      serverDefaultColumnFilters,
    });

    // ASSERT
    expect(result.toString()).toEqual('');
  });

  it('given a filter value stored as a plain string, serializes it as-is', () => {
    // ACT
    const result = serializeTicketListSearchParams({
      currentSearch: '',
      columnFilters: [{ id: 'status', value: 'all' }],
      pageNumber: 1,
      serverDefaultColumnFilters,
    });

    // ASSERT
    expect(result.toString()).toEqual('filter%5Bstatus%5D=all');
  });

  it('given no defaults are provided, treats every active filter as non-default', () => {
    // ACT
    const result = serializeTicketListSearchParams({
      currentSearch: '',
      columnFilters: serverDefaultColumnFilters,
      pageNumber: 1,
    });

    // ASSERT
    expect(result.toString()).toEqual('filter%5Bstatus%5D=unresolved');
  });

  it('given the URL has a param the list does not even own, leaves it alone', () => {
    // ACT
    const result = serializeTicketListSearchParams({
      currentSearch: '?sort=state',
      columnFilters: serverDefaultColumnFilters,
      pageNumber: 2,
      serverDefaultColumnFilters,
    });

    // ASSERT
    expect(result.toString()).toEqual('sort=state&page%5Bnumber%5D=2');
  });
});
