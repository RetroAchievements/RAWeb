import dayjs from 'dayjs';
import { useTranslation } from 'react-i18next';

import { mapDayjsLocaleToIntlLocale } from '@/common/utils/l10n/mapDayjsLocaleToIntlLocale';

type TimeUnit = 'second' | 'minute' | 'hour' | 'day' | 'week' | 'month' | 'year';

interface DiffForHumansOptions {
  /**
   * The reference date to compare against. Defaults to now.
   */
  from?: string;

  /**
   * Maximum time unit to display. Prevents rolling up to larger units.
   * @example `maxUnit: 'day'` will show "14 days ago" instead of "2 weeks ago".
   */
  maxUnit?: TimeUnit;

  style?: Intl.RelativeTimeFormatStyle;
}

export function useDiffForHumans() {
  const { t } = useTranslation();

  const diffForHumans = (date: string, options: DiffForHumansOptions = {}) => {
    const { from, maxUnit, style } = options;

    const diffInSeconds = dayjs(from).diff(dayjs(date), 'second');
    const isPast = diffInSeconds > 0;
    const seconds = Math.abs(diffInSeconds);

    const formatter = new Intl.RelativeTimeFormat(mapDayjsLocaleToIntlLocale(dayjs.locale()), {
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

    // First, determine what unit we would naturally use based on the time elapsed.
    let unit: TimeUnit;
    let divisor: number;

    if (seconds < 3600) {
      unit = 'minute';
      divisor = 60;
    } else if (seconds < 86_400) {
      unit = 'hour';
      divisor = 3600;
    } else if (seconds < 604_800) {
      unit = 'day';
      divisor = 86_400;
    } else if (seconds < 2_629_743) {
      unit = 'week';
      divisor = 604_800;
    } else if (seconds < 31_556_926) {
      unit = 'month';
      divisor = 2_629_743;
    } else {
      unit = 'year';
      divisor = 31_556_926;
    }

    // If maxUnit is specified, cap the unit to not exceed it.
    // For example, if maxUnit is 'day', then "2 weeks" becomes "14 days".
    if (maxUnit === 'day' && ['week', 'month', 'year'].includes(unit)) {
      unit = 'day';
      divisor = 86_400;
    } else if (maxUnit === 'week' && ['month', 'year'].includes(unit)) {
      unit = 'week';
      divisor = 604_800;
    } else if (maxUnit === 'month' && unit === 'year') {
      unit = 'month';
      divisor = 2_629_743;
    }

    return formatter.format(
      isPast ? -Math.floor(seconds / divisor) : Math.floor(seconds / divisor),
      unit,
    );
  };

  return { diffForHumans };
}
