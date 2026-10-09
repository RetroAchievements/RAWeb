import type { FC } from 'react';
import { Trans, useTranslation } from 'react-i18next';
import {
  LuArrowUpRight,
  LuCode,
  LuInfo,
  LuLockOpen,
  LuMessageCircleQuestion,
  LuShieldAlert,
  LuTickets,
  LuTrophy,
} from 'react-icons/lu';

import { usePageProps } from '@/common/hooks/usePageProps';
import { cn } from '@/common/utils/cn';
import { getIsTicketStateFinished } from '@/common/utils/getIsTicketStateFinished';

import { TICKET_STATE_GLYPHS } from '../../utils/ticketStateGlyphs';
import { TicketFactRow } from '../TicketFactRow';
import { TicketLeaderboardEntryFact } from '../TicketLeaderboardEntryFact';
import { TicketLogicChangedFact } from '../TicketLogicChangedFact';
import { TicketOutcomeFact } from '../TicketOutcomeFact';
import { TicketRelatedFact } from '../TicketRelatedFact';
import { TicketReporterUnlockFact } from '../TicketReporterUnlockFact';
import { TicketUnlockCountFact } from '../TicketUnlockCountFact';
import { TicketUserValue } from '../TicketUserValue';

export const TicketFactsList: FC = () => {
  const {
    currentTriggerVersion,
    isViewerReporter,
    leaderboardEntryCount,
    relatedTickets,
    reportedTriggerVersion,
    reporterLeaderboardEntry,
    reporterUnlock,
    ticket,
    unlocksSinceReported,
  } = usePageProps<App.Platform.Data.TicketShowPageProps>();
  const { t } = useTranslation();

  const hasLogicChangedSinceReport =
    !getIsTicketStateFinished(ticket.state) &&
    typeof reportedTriggerVersion === 'number' &&
    typeof currentTriggerVersion === 'number' &&
    currentTriggerVersion !== reportedTriggerVersion;

  return (
    <ul
      className={cn(
        'flex flex-col divide-y divide-neutral-700 rounded-lg border border-neutral-700',
        'bg-embed light:divide-neutral-200 light:border-neutral-200 light:bg-white',
      )}
    >
      {ticket.state === 'request' ? (
        <TicketFactRow Icon={LuMessageCircleQuestion}>
          {isViewerReporter ? (
            t('We need more information from you. Reply in the comments below.')
          ) : (
            <Trans
              i18nKey="Waiting for <1>{{user}}</1> to reply."
              components={{ 1: <TicketUserValue user={ticket.reporter} /> }}
            />
          )}
        </TicketFactRow>
      ) : null}

      {ticket.state === 'quarantined' ? (
        <TicketFactRow Icon={LuShieldAlert}>
          {t(
            'We did not send this quarantined ticket to the author because we do not fully support this emulator or core.',
          )}
        </TicketFactRow>
      ) : null}

      {getIsTicketStateFinished(ticket.state) ? (
        <TicketFactRow Icon={TICKET_STATE_GLYPHS[ticket.state].Icon}>
          <TicketOutcomeFact ticket={ticket} />
        </TicketFactRow>
      ) : null}

      {ticket.state === 'resolved' && isViewerReporter ? (
        <TicketFactRow Icon={LuInfo}>
          {t('If the achievement still does not work for you, you can open a new ticket.')}
        </TicketFactRow>
      ) : null}

      {reporterUnlock ? (
        <TicketFactRow Icon={LuLockOpen}>
          <TicketReporterUnlockFact reporterUnlock={reporterUnlock} ticket={ticket} />
        </TicketFactRow>
      ) : null}

      {typeof unlocksSinceReported === 'number' ? (
        <TicketFactRow Icon={LuArrowUpRight}>
          <TicketUnlockCountFact
            reportedAt={ticket.createdAt}
            unlocksSinceReported={unlocksSinceReported}
          />
        </TicketFactRow>
      ) : null}

      <TicketFactRow Icon={LuTickets}>
        <TicketRelatedFact relatedTickets={relatedTickets} ticket={ticket} />
      </TicketFactRow>

      {hasLogicChangedSinceReport ? (
        <TicketFactRow Icon={LuCode}>
          <TicketLogicChangedFact
            achievementId={ticket.ticketableId}
            currentTriggerVersion={currentTriggerVersion}
            reportedTriggerVersion={reportedTriggerVersion}
          />
        </TicketFactRow>
      ) : null}

      {ticket.ticketableType === 'leaderboard' && ticket.reporter ? (
        <TicketFactRow Icon={LuTrophy}>
          <TicketLeaderboardEntryFact
            reporter={ticket.reporter}
            entry={
              reporterLeaderboardEntry
                ? {
                    leaderboardEntry: reporterLeaderboardEntry,
                    totalEntries: leaderboardEntryCount!,
                  }
                : undefined
            }
          />
        </TicketFactRow>
      ) : null}
    </ul>
  );
};
