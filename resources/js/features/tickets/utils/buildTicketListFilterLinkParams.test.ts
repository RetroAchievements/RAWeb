import { buildTicketListFilterLinkParams } from './buildTicketListFilterLinkParams';

describe('Util: buildTicketListFilterLinkParams', () => {
  it('given every filter is its default value, keeps only the status', () => {
    // ARRANGE
    const serverDefaultColumnFilters = [
      { id: 'status', value: ['unresolved'] },
      { id: 'type', value: ['0'] },
    ];

    // ACT
    const result = buildTicketListFilterLinkParams(
      serverDefaultColumnFilters,
      serverDefaultColumnFilters,
    );

    // ASSERT
    expect(result).toEqual({ 'filter[status]': 'unresolved' });
  });

  it('given filters that narrow the list, keeps them along with the status', () => {
    // ACT
    const result = buildTicketListFilterLinkParams(
      [
        { id: 'status', value: ['all'] },
        { id: 'type', value: ['0'] },
        { id: 'signal', value: ['probably-defect'] },
      ],
      [
        { id: 'status', value: ['unresolved'] },
        { id: 'type', value: ['0'] },
        { id: 'signal', value: ['all'] },
      ],
    );

    // ASSERT
    expect(result).toEqual({
      'filter[status]': 'all',
      'filter[signal]': 'probably-defect',
    });
  });

  it('given an empty free text filter, leaves it out', () => {
    // ACT
    const result = buildTicketListFilterLinkParams(
      [
        { id: 'status', value: ['open'] },
        { id: 'core', value: [''] },
      ],
      [{ id: 'status', value: ['unresolved'] }],
    );

    // ASSERT
    expect(result).toEqual({ 'filter[status]': 'open' });
  });
});
