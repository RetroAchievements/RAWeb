import type { Dayjs } from 'dayjs';
import { useTranslation } from 'react-i18next';

import { formatDate as originalFormatDate } from '../utils/l10n/formatDate';

/**
 * A convenience wrapper around the `formatDate()` util. This wrapper
 * automatically leverages the user's locale from i18n context, preventing
 * SSR hydration mismatches from locale leaking between concurrent requests.
 */
export function useFormatDate() {
  const { i18n } = useTranslation();

  const formatDate = (
    date: Dayjs | string,
    format: Parameters<typeof originalFormatDate>[1],
  ): string => {
    return originalFormatDate(date, format, i18n.language);
  };

  return { formatDate };
}
