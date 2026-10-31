import type { ParseKeys } from 'i18next';
import type { FC } from 'react';
import { Trans } from 'react-i18next';

import { useFormatCoarseDuration } from '@/common/hooks/useFormatCoarseDuration';
import { useServerRenderTime } from '@/common/hooks/useServerRenderTime';

const sentenceKeys = {
  beforeLogicChange: {
    followUp: 'playersUnlockedBeforeLogicChangeFollowUp',
    standalone: 'playersUnlockedBeforeLogicChange',
  },
  sinceReport: {
    followUp: 'playersUnlockedSinceReportFollowUp',
    standalone: 'playersUnlockedSinceReport',
  },
} as const satisfies Record<string, Record<'followUp' | 'standalone', ParseKeys>>;

interface UnlockCountFactProps {
  reportedAt: string;
  unlocksSinceReported: number;

  isFollowUp?: boolean;
  logicChangedAt?: string | null;
}

export const TicketUnlockCountFact: FC<UnlockCountFactProps> = ({
  isFollowUp = false,
  logicChangedAt,
  reportedAt,
  unlocksSinceReported,
}) => {
  const { renderedAt } = useServerRenderTime();
  const { formatCoarseDuration } = useFormatCoarseDuration();

  const sentences = logicChangedAt ? sentenceKeys.beforeLogicChange : sentenceKeys.sinceReport;

  return (
    <Trans
      i18nKey={isFollowUp ? sentences.followUp : sentences.standalone}
      count={unlocksSinceReported}
      values={{ duration: formatCoarseDuration(reportedAt, logicChangedAt ?? renderedAt) }}
      components={{ 1: <span suppressHydrationWarning={true} /> }}
    />
  );
};
