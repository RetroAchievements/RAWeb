import type { ColumnFiltersState } from '@tanstack/react-table';

import { getAreTicketListFiltersNonDefault } from './getAreTicketListFiltersNonDefault';

const serverDefaultColumnFilters: ColumnFiltersState = [
  { id: 'status', value: ['unresolved'] },
  { id: 'type', value: ['0'] },
];

describe('Util: getAreTicketListFiltersNonDefault', () => {
  it('is defined', () => {
    // ASSERT
    expect(getAreTicketListFiltersNonDefault).toBeDefined();
  });

  it('given every filter has its current default value, returns false', () => {
    // ACT
    const result = getAreTicketListFiltersNonDefault(
      [
        { id: 'status', value: ['unresolved'] },
        { id: 'type', value: ['0'] },
      ],
      serverDefaultColumnFilters,
    );

    // ASSERT
    expect(result).toEqual(false);
  });

  it('given any filter has a non-default value, returns true', () => {
    // ACT
    const movedStatus = getAreTicketListFiltersNonDefault(
      [{ id: 'status', value: ['resolved'] }],
      serverDefaultColumnFilters,
    );
    const movedFacet = getAreTicketListFiltersNonDefault(
      [{ id: 'type', value: ['2'] }],
      serverDefaultColumnFilters,
    );

    // ASSERT
    expect(movedStatus).toEqual(true);
    expect(movedFacet).toEqual(true);
  });

  it('given a non-scoped filter, returns true', () => {
    // ACT
    const result = getAreTicketListFiltersNonDefault(
      [{ id: 'emulator', value: ['RetroArch'] }],
      serverDefaultColumnFilters,
    );

    // ASSERT
    expect(result).toEqual(true);
  });
});
