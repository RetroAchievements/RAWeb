import type { FC } from 'react';
import { Trans } from 'react-i18next';

import { useFormatCoarseDuration } from '@/common/hooks/useFormatCoarseDuration';
import { useServerRenderTime } from '@/common/hooks/useServerRenderTime';

interface UnlockCountFactProps {
  reportedAt: string;
  unlocksSinceReported: number;
}

export const TicketUnlockCountFact: FC<UnlockCountFactProps> = ({
  reportedAt,
  unlocksSinceReported,
}) => {
  const { renderedAt } = useServerRenderTime();
  const { formatCoarseDuration } = useFormatCoarseDuration();

  return (
    <Trans
      i18nKey="playersUnlockedSinceReport"
      count={unlocksSinceReported}
      values={{ duration: formatCoarseDuration(reportedAt, renderedAt) }}
      components={{ 1: <span suppressHydrationWarning={true} /> }}
    />
  );
};
