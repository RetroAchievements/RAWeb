import { getTicketListColumnFiltersForSort } from './getTicketListColumnFiltersForSort';

describe('Util: getTicketListColumnFiltersForSort', () => {
  it('is defined', () => {
    // ASSERT
    expect(getTicketListColumnFiltersForSort).toBeDefined();
  });

  it('given a resolved sort and a status with no resolved dates, switches the status to resolved', () => {
    // ACT
    const result = getTicketListColumnFiltersForSort(
      [
        { id: 'status', value: ['open'] },
        { id: 'type', value: ['0'] },
      ],
      '-resolvedAt',
    );

    // ASSERT
    expect(result).toEqual([
      { id: 'status', value: ['resolved'] },
      { id: 'type', value: ['0'] },
    ]);
  });

  it('given a resolved sort and a status that has resolved dates, keeps the status', () => {
    // ACT
    const result = getTicketListColumnFiltersForSort(
      [{ id: 'status', value: ['all'] }],
      'resolvedAt',
    );

    // ASSERT
    expect(result).toEqual([{ id: 'status', value: ['all'] }]);
  });

  it('given a sort other than resolved, keeps the status', () => {
    // ACT
    const result = getTicketListColumnFiltersForSort(
      [{ id: 'status', value: ['unresolved'] }],
      '-state',
    );

    // ASSERT
    expect(result).toEqual([{ id: 'status', value: ['unresolved'] }]);
  });

  it('given no status filter, does not add one', () => {
    // ACT
    const result = getTicketListColumnFiltersForSort([], '-resolvedAt');

    // ASSERT
    expect(result).toEqual([]);
  });
});
