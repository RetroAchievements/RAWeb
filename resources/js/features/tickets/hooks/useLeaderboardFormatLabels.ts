import { useTranslation } from 'react-i18next';

import type { TranslatedString } from '@/types/i18next';

/**
 * @see https://docs.retroachievements.org/developer-docs/rich-presence.html#format
 */
export function useLeaderboardFormatLabels(): Record<string, TranslatedString> {
  const { t } = useTranslation();

  return {
    SCORE: t('Score'),
    TIME: t('Time (Frames)'),
    MILLISECS: t('Time (Centiseconds)'),
    TIMESECS: t('Time (Seconds)'),
    MINUTES: t('Time (Minutes)'),
    SECS_AS_MINS: t('Time (Seconds as Minutes)'),
    VALUE: t('Value'),
    UNSIGNED: t('Value (Unsigned)'),
    TENS: t('Value (Tens)'),
    HUNDREDS: t('Value (Hundreds)'),
    THOUSANDS: t('Value (Thousands)'),
    FIXED1: t('Value (Fixed1)'),
    FIXED2: t('Value (Fixed2)'),
    FIXED3: t('Value (Fixed3)'),
  };
}
