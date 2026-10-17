import type { FC } from 'react';
import { Trans } from 'react-i18next';

import { useFormatCoarseDuration } from '@/common/hooks/useFormatCoarseDuration';

import { TicketDateTooltip } from '../TicketDateTooltip';
import { TicketUserValue } from '../TicketUserValue';

const unlockSentenceKeys = {
  manual: {
    after:
      '<1>{{awarder}}</1> manually unlocked this achievement for <2>{{user}}</2> <3>{{duration}}</3> after the report.',
    before:
      '<1>{{awarder}}</1> manually unlocked this achievement for <2>{{user}}</2> <3>{{duration}}</3> before the report.',
  },
  hardcore: {
    after:
      '<2>{{user}}</2> unlocked this achievement in hardcore mode <3>{{duration}}</3> after the report.',
    before:
      '<2>{{user}}</2> unlocked this achievement in hardcore mode <3>{{duration}}</3> before the report.',
  },
  casual: {
    after:
      '<2>{{user}}</2> unlocked this achievement in casual mode <3>{{duration}}</3> after the report.',
    before:
      '<2>{{user}}</2> unlocked this achievement in casual mode <3>{{duration}}</3> before the report.',
  },
} as const;

interface ReporterUnlockFactProps {
  reporterUnlock: App.Platform.Data.TicketReporterUnlock;
  ticket: App.Platform.Data.TicketListEntry;
}

export const TicketReporterUnlockFact: FC<ReporterUnlockFactProps> = ({
  reporterUnlock,
  ticket,
}) => {
  const { formatCoarseDuration } = useFormatCoarseDuration();

  const didUnlockFollowReport = reporterUnlock.unlockedAt > ticket.createdAt;

  let unlockKind: keyof typeof unlockSentenceKeys = 'casual';
  if (reporterUnlock.unlocker) {
    unlockKind = 'manual';
  } else if (reporterUnlock.isHardcore) {
    unlockKind = 'hardcore';
  }

  return (
    <Trans
      i18nKey={unlockSentenceKeys[unlockKind][didUnlockFollowReport ? 'after' : 'before']}
      values={{ duration: formatCoarseDuration(ticket.createdAt, reporterUnlock.unlockedAt) }}
      components={{
        1: <TicketUserValue user={reporterUnlock.unlocker} />,
        2: <TicketUserValue user={ticket.reporter} />,
        3: <TicketDateTooltip date={reporterUnlock.unlockedAt} />,
      }}
    />
  );
};
