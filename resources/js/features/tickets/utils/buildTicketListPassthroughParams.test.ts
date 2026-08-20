import { buildTicketListPassthroughParams } from './buildTicketListPassthroughParams';

describe('Util: buildTicketListPassthroughParams', () => {
  it('is defined', () => {
    // ASSERT
    expect(buildTicketListPassthroughParams).toBeDefined();
  });

  it('given the URL carries a sort and filters, returns them as flat params', () => {
    // ACT
    const result = buildTicketListPassthroughParams({
      sort: 'state',
      filter: { status: 'resolved', emulator: 'RetroArch' },
    });

    // ASSERT
    expect(result).toEqual({
      sort: 'state',
      'filter[status]': 'resolved',
      'filter[emulator]': 'RetroArch',
    });
  });

  it('given the URL is bare, returns no params', () => {
    // ACT
    const result = buildTicketListPassthroughParams({});

    // ASSERT
    expect(result).toEqual({});
  });

  it('given empty or non-string values, drops them', () => {
    // ACT
    const result = buildTicketListPassthroughParams({
      sort: '',
      filter: { status: '', mode: ['softcore'] as unknown as string },
    });

    // ASSERT
    expect(result).toEqual({});
  });

  it('given the filter param is not an object, ignores it', () => {
    // ACT
    const result = buildTicketListPassthroughParams({ filter: 'resolved' });

    // ASSERT
    expect(result).toEqual({});
  });
});
