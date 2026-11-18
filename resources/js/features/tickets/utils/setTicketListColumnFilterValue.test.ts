import { setTicketListColumnFilterValue } from './setTicketListColumnFilterValue';

describe('Util: setTicketListColumnFilterValue', () => {
  it('is defined', () => {
    // ASSERT
    expect(setTicketListColumnFilterValue).toBeDefined();
  });

  it('given the filter is not yet set, appends it', () => {
    // ACT
    const result = setTicketListColumnFilterValue(
      [{ id: 'status', value: ['open'] }],
      'core',
      'nestopia',
    );

    // ASSERT
    expect(result).toEqual([
      { id: 'status', value: ['open'] },
      { id: 'core', value: ['nestopia'] },
    ]);
  });

  it('given the filter is already set, replaces its value in place', () => {
    // ACT
    const result = setTicketListColumnFilterValue(
      [
        { id: 'core', value: ['nestopia'] },
        { id: 'status', value: ['open'] },
      ],
      'core',
      'mesen',
    );

    // ASSERT
    expect(result).toEqual([
      { id: 'core', value: ['mesen'] },
      { id: 'status', value: ['open'] },
    ]);
  });

  it('given an empty value, removes the filter', () => {
    // ACT
    const result = setTicketListColumnFilterValue(
      [
        { id: 'core', value: ['nestopia'] },
        { id: 'status', value: ['open'] },
      ],
      'core',
      '',
    );

    // ASSERT
    expect(result).toEqual([{ id: 'status', value: ['open'] }]);
  });
});
