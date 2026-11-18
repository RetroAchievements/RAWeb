import dayjs from 'dayjs';
import { useTranslation } from 'react-i18next';

import { useFormatCoarseDuration } from '@/common/hooks/useFormatCoarseDuration';
import { useFormatPercentage } from '@/common/hooks/useFormatPercentage';
import type { TranslatedString } from '@/types/i18next';

import { getIsUnlockWindowSignal } from '../utils/getIsUnlockWindowSignal';
import { useTicketSignalVerdictLabels } from './useTicketSignalVerdictLabels';

export function useTicketSignalVerdictCopy(
  signals: App.Platform.Data.TicketSignals,
  reportedAt: string,
): { label: TranslatedString; reasons: string[]; accuracy: TranslatedString } {
  const { t, i18n } = useTranslation();
  const { formatCoarseDuration } = useFormatCoarseDuration();
  const { formatPercentage } = useFormatPercentage();

  const verdictLabels = useTicketSignalVerdictLabels();

  // This fallback copy comes from a one-off backtest that happened in Oct 2026
  // to develop the signals system itself. The fallback shows if there are no
  // accuracy metrics stored in our DB.
  const estimatedFallbackByVerdict: Record<
    App.Community.Enums.TicketSignalVerdict,
    TranslatedString
  > = {
    'probably-not-defect': t(
      'Nearly 9 in 10 past tickets with this verdict were closed without a logic change.',
    ),
    'probably-defect': t('Nearly 4 in 5 tickets with this verdict receive a logic change.'),
  };

  const buildMeasuredAccuracySentence = (
    verdict: App.Community.Enums.TicketSignalVerdict,
    { ticketCount, correctCount }: App.Platform.Data.TicketSignalAccuracy,
  ): TranslatedString => {
    const percentageValues = {
      percentage: formatPercentage(correctCount / ticketCount, {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
      }),
    };

    const sentenceByVerdict: Record<App.Community.Enums.TicketSignalVerdict, TranslatedString> = {
      'probably-not-defect': t(
        '{{percentage}} of past tickets with this verdict were closed without a logic change.',
        percentageValues,
      ),
      'probably-defect': t(
        '{{percentage}} of tickets with this verdict receive a logic change.',
        percentageValues,
      ),
    };

    return sentenceByVerdict[verdict];
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
    if (getIsUnlockWindowSignal(kind)) {
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
    accuracy: signals.accuracy
      ? buildMeasuredAccuracySentence(signals.verdict, signals.accuracy)
      : estimatedFallbackByVerdict[signals.verdict],
    label: verdictLabels[signals.verdict],
    reasons: signals.kinds.map(describeSignal),
  };
}
