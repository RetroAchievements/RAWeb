import dayjs from 'dayjs';
import duration from 'dayjs/plugin/duration';
import { useTranslation } from 'react-i18next';

import type { TranslatedString } from '@/types/i18next';

dayjs.extend(duration);

export function useFormatDuration() {
  const { t } = useTranslation();

  const formatDuration = (
    intDuration: number,
    options?: Partial<{ shouldTruncateSeconds: boolean }>,
  ): TranslatedString => {
    const duration = dayjs.duration(intDuration, 'seconds');

    const hours = Math.floor(duration.asHours());
    const minutes = duration.minutes();
    const seconds = duration.seconds();

    const shouldTruncateSeconds = options?.shouldTruncateSeconds ?? false;

    // Select the appropriate format based on non-zero components.
    if (hours > 0) {
      if (shouldTruncateSeconds) {
        return t('{{hours}}h {{minutes}}m', { hours, minutes });
      }

      return t('{{hours}}h {{minutes}}m {{seconds}}s', { hours, minutes, seconds });
    } else if (minutes > 0) {
      if (shouldTruncateSeconds) {
        return t('{{minutes}}m', { minutes });
      }

      return t('{{minutes}}m {{seconds}}s', { minutes, seconds });
    }

    // If we only have seconds, always show them regardless of the shouldTruncateSeconds option.
    return t('{{seconds}}s', { seconds });
  };

  return { formatDuration };
}
