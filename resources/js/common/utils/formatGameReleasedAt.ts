import dayjs from 'dayjs';
import localizedFormat from 'dayjs/plugin/localizedFormat';
import utc from 'dayjs/plugin/utc';

import { formatDate } from './l10n/formatDate';

dayjs.extend(utc);
dayjs.extend(localizedFormat);

/**
 * Formats the game's release date based on the provided releasedAt and
 * releasedAtGranularity values. The formatted date is automatically adjusted
 * for the user's current locale and can be returned in varying levels of
 * granularity.
 *
 * If no releasedAt value is provided, the function returns null.
 */
export function formatGameReleasedAt(
  releasedAt: App.Platform.Data.Game['releasedAt'],
  releasedAtGranularity: App.Platform.Data.Game['releasedAtGranularity'],
): string | null {
  if (!releasedAt) {
    return null;
  }

  const dayjsDate = dayjs.utc(releasedAt);
  let formattedDate;
  if (releasedAtGranularity === 'day') {
    formattedDate = formatDate(dayjsDate, 'll');
  } else if (releasedAtGranularity === 'month') {
    formattedDate = dayjsDate.format('MMM YYYY');
  } else {
    formattedDate = dayjsDate.format('YYYY');
  }

  return formattedDate;
}
