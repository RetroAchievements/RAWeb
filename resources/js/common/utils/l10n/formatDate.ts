import type { Dayjs } from 'dayjs';
import dayjs from 'dayjs';
import localizedFormat from 'dayjs/plugin/localizedFormat';

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
type ValidLocalizedFormat =
  | 'LT' // "8:02 PM"
  | 'LTS' // "8:02:18 PM"
  | 'L' // "08/16/2018"
  | 'LL' // "August 16, 2018"
  | 'LLL' // "August 16, 2018 8:02 PM"
  | 'LLLL' // "Thursday, August 16, 2018 8:02 PM"
  | 'l' // "8/16/2018"
  | 'll' // "Aug 16, 2018"
  | 'lll' // "Aug 16, 2018 8:02 PM"
  | 'llll' // "Thu, Aug 16, 2018 8:02 PM"
  | 'MMM DD, YYYY, HH:mm'; // "Aug 16, 2018, 08:02"

export function formatDate(
  date: Dayjs,
  format: ValidLocalizedFormat | `${ValidLocalizedFormat} ${ValidLocalizedFormat}`,
): string {
  const currentLocale = dayjs.locale();

  if (format === 'MMM DD, YYYY, HH:mm') {
    if (currentLocale === 'en' || currentLocale === 'en-us') {
      return date.format(format);
    } else {
      return date.format('DD MMM YYYY, HH:mm');
    }
  }

  return date.format(format);
}
