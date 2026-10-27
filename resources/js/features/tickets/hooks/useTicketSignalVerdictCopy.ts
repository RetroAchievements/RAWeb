import dayjs from 'dayjs';
import { useTranslation } from 'react-i18next';

import { useFormatCoarseDuration } from '@/common/hooks/useFormatCoarseDuration';
import type { TranslatedString } from '@/types/i18next';

export function useTicketSignalVerdictCopy(
  signals: App.Platform.Data.TicketSignals,
  reportedAt: string,
): { label: TranslatedString; reasons: string[]; accuracy: TranslatedString } {
  const { t, i18n } = useTranslation();
  const { formatCoarseDuration } = useFormatCoarseDuration();

  const verdictLabels: Record<App.Community.Enums.TicketSignalVerdict, TranslatedString> = {
    'probably-not-defect': t('Probably not a bug'),
    'probably-defect': t('Probably a real bug'),
  };

  /**
   * TODO these numbers come from a one-off backtest run on Oct 22 2026
   *      eventually, the backtest should run on a schedule and its results and trends should live in the DB
   */
  const verdictAccuracies: Record<App.Community.Enums.TicketSignalVerdict, TranslatedString> = {
    'probably-not-defect': t(
      'Nearly 9 in 10 past tickets with this verdict were closed without a logic change.',
    ),
    'probably-defect': t('Nearly 4 in 5 tickets with this verdict receive a logic change.'),
  };

  const formatWindowSpan = (start: string, end: string): string => {
    const spanInDays = dayjs(end).diff(dayjs(start), 'day');
    if (spanInDays < 1) {
      return formatCoarseDuration(start, end);
    }

    return new Intl.NumberFormat(i18n.language.replace('_', '-'), {
      style: 'unit',
      unit: 'day',
      unitDisplay: 'long',
    }).format(spanInDays);
  };

  const describeSignal = (kind: App.Community.Enums.TicketSignal): string => {
    if (kind === 'many-recent-unlocks' || kind === 'no-recent-unlocks') {
      return t(
        // A logic change shortens the window.
        signals.hasRecentLogicChange
          ? 'playersUnlockedSinceLogicChange'
          : 'playersUnlockedBeforeReport',
        {
          count: signals.unlockCount,
          duration: formatWindowSpan(signals.windowStartedAt, reportedAt),
        },
      );
    }

    const duration = formatCoarseDuration(reportedAt, signals.reporterEarnedAt!);

    if (kind === 'earned-with-same-logic') {
      return t(
        'The reporter unlocked this achievement {{duration}} after the report, with the same logic they reported.',
        { duration },
      );
    }

    if (kind === 'earned-after-logic-change') {
      return t(
        'The reporter unlocked this achievement {{duration}} after the report, after its logic changed. This was probably fixed already.',
        { duration },
      );
    }

    return t('The reporter unlocked this achievement {{duration}} after the report.', {
      duration,
    });
  };

  return {
    accuracy: verdictAccuracies[signals.verdict],
    label: verdictLabels[signals.verdict],
    reasons: signals.kinds.map(describeSignal),
  };
}
