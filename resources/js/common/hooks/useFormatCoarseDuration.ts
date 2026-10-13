import dayjs from 'dayjs';
import { useTranslation } from 'react-i18next';

import { formatCoarseDuration as formatCoarseDurationUtil } from '../utils/l10n/formatCoarseDuration';

/**
 * A convenience wrapper around the `formatCoarseDuration()` util.
 * Measures the gap between two timestamps and formats it in the user's locale.
 */
export function useFormatCoarseDuration() {
  const { i18n } = useTranslation();

  // Callers word "before" or "after" themselves.
  // This measures the gap in either direction.
  const formatCoarseDuration = (start: string, end: string): string => {
    return formatCoarseDurationUtil(
      Math.abs(dayjs(end).diff(dayjs(start), 'second')),
      i18n.language,
    );
  };

  return { formatCoarseDuration };
}
