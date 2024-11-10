import 'dayjs/locale/pt-br';
import 'dayjs/locale/pl';

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

  describe('MMM YYYY', () => {
    it('formats for en-us correctly', () => {
      // ARRANGE
      dayjs.locale('en-us');

      const date = dayjs.utc('2023-06-06');

      // ACT
      const formatted = formatDate(date, 'MMM YYYY');

      // ASSERT
      expect(formatted).toEqual('June 2023');
    });

    it('formats for pt-br correctly', () => {
      // ARRANGE
      dayjs.locale('pt-br');

      const date = dayjs.utc('2023-06-06');

      // ACT
      const formatted = formatDate(date, 'MMM YYYY');

      // ASSERT
      expect(formatted).toEqual('junho de 2023');
    });

    it('formats for pl correctly', () => {
      // ARRANGE
      dayjs.locale('pl');

      const date = dayjs.utc('2023-06-06');

      // ACT
      const formatted = formatDate(date, 'MMM YYYY');

      // ASSERT
      expect(formatted).toEqual('czerwiec 2023');
    });
  });

  describe('MMM DD, YYYY, HH:mm', () => {
    it('formats midnight correctly in en-us', () => {
      // ARRANGE
      dayjs.locale('en-us');
      const date = dayjs.utc('2023-12-25');

      // ACT
      const formatted = formatDate(date, 'MMM DD, YYYY, HH:mm');

      // ASSERT
      expect(formatted).toEqual('Dec 25, 2023, 00:00');
    });

    it('formats regular time correctly in en-us', () => {
      // ARRANGE
      dayjs.locale('en-us');
      const date = dayjs.utc('2023-12-25T14:30:00Z');

      // ACT
      const formatted = formatDate(date, 'MMM DD, YYYY, HH:mm');

      // ASSERT
      expect(formatted).toEqual('Dec 25, 2023, 14:30');
    });

    it('formats midnight correctly in pt-br', () => {
      // ARRANGE
      dayjs.locale('pt-br');
      const date = dayjs.utc('2023-12-25');

      // ACT
      const formatted = formatDate(date, 'MMM DD, YYYY, HH:mm');

      // ASSERT
      expect(formatted).toEqual('25 de dez. de 2023, 00:00');
    });
  });
});
