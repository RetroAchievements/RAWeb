import dayjs from 'dayjs';
import { useTranslation } from 'react-i18next';

import { getRelativeTimeUnit } from './getRelativeTimeUnit';

interface DiffForHumansOptions {
  /**
   * The reference date to compare against. Defaults to now.
   */
  from?: string;

  /**
   * Maximum time unit to display. Prevents rolling up to larger units.
   * @example `maxUnit: 'day'` will show "14 days ago" instead of "2 weeks ago".
   */
  maxUnit?: 'day' | 'week' | 'month';

  style?: Intl.RelativeTimeFormatStyle;
}

export function useDiffForHumans() {
  const { t, i18n } = useTranslation();

  const diffForHumans = (date: string, options: DiffForHumansOptions = {}) => {
    const { from, maxUnit, style } = options;

    const diffInSeconds = dayjs(from).diff(dayjs(date), 'second');
    const isPast = diffInSeconds > 0;
    const seconds = Math.abs(diffInSeconds);

    const formatter = new Intl.RelativeTimeFormat(i18n.language.replace('_', '-'), {
      numeric: 'always',
      style: style ?? 'long',
    });

    if (style === 'narrow' && seconds < 60) {
      return formatter.format(isPast ? -seconds : seconds, 'second');
    }

    // Very recent times are handled manually.
    if (seconds === 0) {
      return t('just now');
    }
    if (seconds < 10) {
      return isPast ? t('just now') : t('in a few seconds');
    }
    if (seconds < 60) {
      return isPast ? t('less than a minute ago') : t('in less than a minute');
    }

    const { unit, value } = getRelativeTimeUnit(seconds, maxUnit);

    return formatter.format(isPast ? -value : value, unit);
  };

  return { diffForHumans };
}
