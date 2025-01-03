import dayjs from 'dayjs';
import duration from 'dayjs/plugin/duration';
import { useTranslation } from 'react-i18next';

import type { TranslatedString } from '@/types/i18next';

dayjs.extend(duration);

export function useFormatDuration() {
  const { t } = useTranslation();

  const formatDuration = (intDuration: number): TranslatedString => {
    const duration = dayjs.duration(intDuration, 'seconds');

    const hours = Math.floor(duration.asHours());
    const minutes = duration.minutes();
    const seconds = duration.seconds();

    // Select the appropriate format based on non-zero components.
    if (hours > 0) {
      return t('{{hours}}h {{minutes}}m {{seconds}}s', { hours, minutes, seconds });
    } else if (minutes > 0) {
      return t('{{minutes}}m {{seconds}}s', { minutes, seconds });
    }

    return t('{{seconds}}s', { seconds });
  };

  return { formatDuration };
}
