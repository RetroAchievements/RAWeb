import 'dayjs/locale/pt-br';

import { formatGameReleasedAt } from './formatGameReleasedAt';

describe('Util: formatGameReleasedAt', () => {
  it('is defined', () => {
    // ASSERT
    expect(formatGameReleasedAt).toBeDefined();
  });

  it('given there is no release date, falls back to null', () => {
    // ACT
    const result = formatGameReleasedAt(null, 'day', 'en_US');

    // ASSERT
    expect(result).toBeNull();
  });

  it('formats to a human-readable date, respecting the granularity value', () => {
    // ARRANGE
    const mockDate = new Date('1987-05-05').toISOString();

    // ACT
    const resultOne = formatGameReleasedAt(mockDate, 'year', 'en_US');
    const resultTwo = formatGameReleasedAt(mockDate, 'month', 'en_US');
    const resultThree = formatGameReleasedAt(mockDate, 'day', 'en_US');
    const resultFour = formatGameReleasedAt(mockDate, null, 'en_US');

    // ASSERT
    expect(resultOne).toEqual('1987');
    expect(resultTwo).toEqual('May 1987');
    expect(resultThree).toEqual('May 5, 1987');
    expect(resultFour).toEqual('1987');
  });

  it("formats to the user's current locale, respecting the granularity value", () => {
    // ARRANGE
    const mockDate = new Date('1987-05-05').toISOString();

    // ACT
    const resultOne = formatGameReleasedAt(mockDate, 'year', 'pt_BR');
    const resultTwo = formatGameReleasedAt(mockDate, 'month', 'pt_BR');
    const resultThree = formatGameReleasedAt(mockDate, 'day', 'pt_BR');
    const resultFour = formatGameReleasedAt(mockDate, null, 'pt_BR');

    // ASSERT
    expect(resultOne).toEqual('1987');
    expect(resultTwo).toEqual('maio de 1987');
    expect(resultThree).toEqual('5 de mai de 1987');
    expect(resultFour).toEqual('1987');
  });
});
