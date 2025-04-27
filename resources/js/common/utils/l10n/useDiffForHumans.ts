import dayjs from 'dayjs';
import { useTranslation } from 'react-i18next';

import { mapDayjsLocaleToIntlLocale } from '@/common/utils/l10n/mapDayjsLocaleToIntlLocale';

export function useDiffForHumans() {
  const { t } = useTranslation();

  const diffForHumans = (date: string, from?: string) => {
    const diffInSeconds = dayjs(from).diff(dayjs(date), 'second');
    const isPast = diffInSeconds > 0;
    const seconds = Math.abs(diffInSeconds);

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

    // Use Intl.RelativeTimeFormat for everything else.
    const formatter = new Intl.RelativeTimeFormat(mapDayjsLocaleToIntlLocale(dayjs.locale()), {
      numeric: 'always',
    });

    // Convert seconds to appropriate unit and get formatted string.
    switch (true) {
      case seconds < 3600:
        return formatter.format(
          isPast ? -Math.floor(seconds / 60) : Math.floor(seconds / 60),
          'minute',
        );

      case seconds < 86_400:
        return formatter.format(
          isPast ? -Math.floor(seconds / 3600) : Math.floor(seconds / 3600),
          'hour',
        );

      case seconds < 604_800:
        return formatter.format(
          isPast ? -Math.floor(seconds / 86_400) : Math.floor(seconds / 86_400),
          'day',
        );

      case seconds < 2_629_743:
        return formatter.format(
          isPast ? -Math.floor(seconds / 597_800) : Math.floor(seconds / 597_800),
          'week',
        );

      case seconds < 31_556_926:
        return formatter.format(
          isPast ? -Math.floor(seconds / 2_628_243) : Math.floor(seconds / 2_628_243),
          'month',
        );

      default:
        return formatter.format(
          isPast ? -Math.floor(seconds / 31_556_926) : Math.floor(seconds / 31_556_926),
          'year',
        );
    }
  };

  return { diffForHumans };
}
