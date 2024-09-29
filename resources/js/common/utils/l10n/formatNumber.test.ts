import { formatNumber } from './formatNumber';

describe('Util: formatNumber', () => {
  it('is defined', () => {
    // ASSERT
    expect(formatNumber).toBeDefined();
  });

  it('by default formats numbers targeting the en-US locale', () => {
    // ACT
    const formatted = formatNumber(12345);

    // ASSERT
    expect(formatted).toEqual('12,345');
  });

  it('accepts a custom locale code', () => {
    // ACT
    const formatted = formatNumber(12345, { locale: 'pt-BR' });

    // ASSERT
    expect(formatted).toEqual('12.345');
  });
});
