import 'dayjs/locale/pt-br';

import dayjs from 'dayjs';
import utc from 'dayjs/plugin/utc';

import { formatDate } from './formatDate';

dayjs.extend(utc);

describe('Util: formatDate', () => {
  beforeEach(() => {
    dayjs.locale('en');
  });

  it('is defined', () => {
    // ASSERT
    expect(formatDate).toBeDefined();
  });

  it('returns a localized date format, defaulting to the en locale', () => {
    // ARRANGE
    const date = dayjs.utc('2023-05-06');

    // ACT
    const formatted = formatDate(date, 'L');

    // ASSERT
    expect(formatted).toEqual('05/06/2023');
  });

  it('given the user is using a non-default locale, returns a localized date format', () => {
    // ARRANGE
    dayjs.locale('pt-br');

    const date = dayjs.utc('2023-05-06');

    // ACT
    const formatted = formatDate(date, 'L');

    // ASSERT
    expect(formatted).toEqual('06/05/2023');
  });
});
