import dayjs from 'dayjs';
import duration from 'dayjs/plugin/duration';
import { useTranslation } from 'react-i18next';

import { useFormatNumber } from '@/common/hooks/useFormatNumber';
import type { TranslatedString } from '@/types/i18next';

dayjs.extend(duration);

interface FormatSessionsInfoProps {
  sessionCount: number;
  totalUnlockTime: number;
}

export function useFormatSessionsInfo() {
  const { t } = useTranslation();

  const { formatNumber } = useFormatNumber();

  const formatSessionsInfo = ({
    sessionCount,
    totalUnlockTime,
  }: FormatSessionsInfoProps): TranslatedString => {
    if (sessionCount === 0) {
      return t('No sessions');
    }

    if (sessionCount === 1) {
      return t('1 session');
    }

    // If there are multiple play sessions, calculate the time span.
    const unlockDuration = dayjs.duration(totalUnlockTime, 'seconds');
    const elapsedDays = Math.ceil(unlockDuration.asDays());

    const hours = Math.ceil(unlockDuration.asHours());
    if (hours < 24) {
      if (hours <= 1) {
        return t('{{count}} sessions over 1 hour', {
          count: formatNumber(sessionCount),
        });
      }

      return t('{{count}} sessions over {{hours}} hours', {
        hours: formatNumber(hours),
        count: formatNumber(sessionCount),
      });
    }

    if (elapsedDays === 1) {
      return t('{{count}} sessions over {{days}} day', {
        count: formatNumber(sessionCount),
        days: formatNumber(elapsedDays),
      });
    }

    return t('{{count}} sessions over {{days}} days', {
      count: formatNumber(sessionCount),
      days: formatNumber(elapsedDays),
    });
  };

  return { formatSessionsInfo };
}
