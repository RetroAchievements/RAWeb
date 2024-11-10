import type { Dayjs } from 'dayjs';
import dayjs from 'dayjs';
import localizedFormat from 'dayjs/plugin/localizedFormat';
import utc from 'dayjs/plugin/utc';

import { mapDayjsLocaleToIntlLocale } from './mapDayjsLocaleToIntlLocale';

// TODO switch from dayjs to date-fns for better perf and localization support

dayjs.extend(utc);
dayjs.extend(localizedFormat);

/**
 * dayjs's locale is set globally. It defaults to "en".
 * @see https://day.js.org/docs/en/i18n/changing-locale
 *
 * It's still possible to use dayjs's .format() to output
 * localized dates, however it's less safe to do so.
 * When in doubt, use this util.
 */

/**
 * @see https://day.js.org/docs/en/display/format#list-of-localized-formats
 */
type StandardLocalizedFormat =
  | 'LT' // "8:02 PM"
  | 'LTS' // "8:02:18 PM"
  | 'L' // "08/16/2018"
  | 'LL' // "August 16, 2018"
  | 'LLL' // "August 16, 2018 8:02 PM"
  | 'LLLL' // "Thursday, August 16, 2018 8:02 PM"
  | 'l' // "8/16/2018"
  | 'll' // "Aug 16, 2018"
  | 'lll' // "Aug 16, 2018 8:02 PM"
  | 'llll'; // "Thu, Aug 16, 2018 8:02 PM"

type CustomLocalizedFormat = 'MMM DD, YYYY, HH:mm' | 'MMM YYYY';

type ValidLocalizedFormat = StandardLocalizedFormat | CustomLocalizedFormat;

export function formatDate(
  date: Dayjs | string,
  format: ValidLocalizedFormat | `${ValidLocalizedFormat} ${ValidLocalizedFormat}`,
): string {
  const dayjsDate = typeof date === 'string' ? dayjs.utc(date) : date;
  const locale = dayjs.locale();
  const nativeLocale = mapDayjsLocaleToIntlLocale(locale);

  if (format === 'MMM YYYY') {
    const formatter = new Intl.DateTimeFormat(nativeLocale, {
      month: 'long',
      year: 'numeric',
    });

    return formatter.format(dayjsDate.toDate());
  } else if (format === 'MMM DD, YYYY, HH:mm') {
    const formatter = new Intl.DateTimeFormat(nativeLocale, {
      month: 'short',
      day: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
      hour12: false,
    });

    return formatter.format(dayjsDate.toDate());
  }

  return dayjsDate.format(format);
}
