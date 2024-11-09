import type { Dayjs } from 'dayjs';
import dayjs from 'dayjs';
import localizedFormat from 'dayjs/plugin/localizedFormat';
import utc from 'dayjs/plugin/utc';

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

const formatOverrides: Partial<
  Record<
    ValidLocalizedFormat | `${ValidLocalizedFormat} ${ValidLocalizedFormat}`,
    (locale: string) => string
  >
> = {
  'MMM DD, YYYY, HH:mm': (locale) =>
    ['en', 'en-us'].includes(locale) ? 'MMM DD, YYYY, HH:mm' : 'DD MMM YYYY, HH:mm',
  'MMM YYYY': (locale) => {
    switch (locale) {
      case 'en':
      case 'en-us':
        return 'MMM YYYY';
      case 'pt-br':
        return 'MMM [de] YYYY';
      default:
        return 'MMM YYYY';
    }
  },
};

export function formatDate(
  date: Dayjs | string,
  format: ValidLocalizedFormat | `${ValidLocalizedFormat} ${ValidLocalizedFormat}`,
): string {
  const dayjsDate = typeof date === 'string' ? dayjs.utc(date) : date;
  const locale = dayjs.locale();

  // Determine if there's a format override for the current format and locale
  const overriddenFormat = formatOverrides[format]?.(locale) || format;

  return dayjsDate.format(overriddenFormat);
}
