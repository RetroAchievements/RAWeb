import type { ColumnFiltersState } from '@tanstack/react-table';

import { resolveInitialTicketListColumnFilters } from './resolveInitialTicketListColumnFilters';

const serverDefaultColumnFilters: ColumnFiltersState = [{ id: 'status', value: ['unresolved'] }];

describe('Util: resolveInitialTicketListColumnFilters', () => {
  it('is defined', () => {
    // ASSERT
    expect(resolveInitialTicketListColumnFilters).toBeDefined();
  });

  it('given the URL is silent, falls back to the scope defaults', () => {
    // ACT
    const result = resolveInitialTicketListColumnFilters({}, serverDefaultColumnFilters);

    // ASSERT
    expect(result).toEqual(serverDefaultColumnFilters);
  });

  it('given the URL carries a filter, uses it and still applies defaults the URL is silent about', () => {
    // ACT
    const result = resolveInitialTicketListColumnFilters(
      { filter: { emulator: 'RetroArch' } },
      serverDefaultColumnFilters,
    );

    // ASSERT
    expect(result).toEqual([
      { id: 'emulator', value: ['RetroArch'] },
      { id: 'status', value: ['unresolved'] },
    ]);
  });

  it('given the URL overrides a default filter kind, the URL wins', () => {
    // ACT
    const result = resolveInitialTicketListColumnFilters(
      { filter: { status: 'resolved' } },
      serverDefaultColumnFilters,
    );

    // ASSERT
    expect(result).toEqual([{ id: 'status', value: ['resolved'] }]);
  });

  it('given the URL filter values are empty strings, ignores them', () => {
    // ACT
    const result = resolveInitialTicketListColumnFilters(
      { filter: { status: '' } },
      serverDefaultColumnFilters,
    );

    // ASSERT
    expect(result).toEqual(serverDefaultColumnFilters);
  });

  it('given the URL filter param is not an object, ignores it', () => {
    // ACT
    const result = resolveInitialTicketListColumnFilters(
      { filter: 'resolved' },
      serverDefaultColumnFilters,
    );

    // ASSERT
    expect(result).toEqual(serverDefaultColumnFilters);
  });
});
