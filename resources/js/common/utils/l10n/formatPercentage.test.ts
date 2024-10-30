import { formatPercentage } from './formatPercentage';

describe('Util: formatPercentage', () => {
  it('is defined', () => {
    // ASSERT
    expect(formatPercentage).toBeDefined();
  });

  it('by default formats percentages targeting the en-US locale', () => {
    // ACT
    const formatted = formatPercentage(0.1234);

    // ASSERT
    expect(formatted).toEqual('12.34%');
  });

  it('accepts a custom locale code', () => {
    // ACT
    const formatted = formatPercentage(0.1234, { locale: 'pt-BR' });

    // ASSERT
    expect(formatted).toEqual('12,34%');
  });

  it('can clamp fraction digits', () => {
    // ACT
    const formatted = formatPercentage(0.1234, {
      maximumFractionDigits: 0,
      minimumFractionDigits: 0,
    });

    // ASSERT
    expect(formatted).toEqual('12%');
  });

  it('can be configured to omit the percentage sign', () => {
    // ACT
    const formatted = formatPercentage(0.1234, { omitSign: true });

    // ASSERT
    expect(formatted).toEqual('12.34');
  });
});
