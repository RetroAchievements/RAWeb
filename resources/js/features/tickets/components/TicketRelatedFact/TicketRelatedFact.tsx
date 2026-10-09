import type { FC } from 'react';
import { Trans, useTranslation } from 'react-i18next';

import { getIsTicketStateFinished } from '@/common/utils/getIsTicketStateFinished';
import { getIsTicketStateOpen } from '@/common/utils/getIsTicketStateOpen';

import { buildAchievementTicketListHref } from '../../utils/buildAchievementTicketListHref';

interface RelatedFactProps {
  relatedTickets: App.Platform.Data.TicketRelatedEntry[];
  ticket: App.Platform.Data.TicketListEntry;
}

export const TicketRelatedFact: FC<RelatedFactProps> = ({ relatedTickets, ticket }) => {
  const { t } = useTranslation();

  const isLeaderboardTicket = ticket.ticketableType === 'leaderboard';

  if (!relatedTickets.length) {
    return isLeaderboardTicket
      ? t('This leaderboard has no other tickets.')
      : t('This achievement has no other tickets.');
  }

  // We assume that for quarantined tickets the client could be completely broken.
  const unresolvedCount = relatedTickets.filter((relatedTicket) =>
    getIsTicketStateOpen(relatedTicket.state),
  ).length;
  const finishedCount = relatedTickets.filter((relatedTicket) =>
    getIsTicketStateFinished(relatedTicket.state),
  ).length;

  // TODO point these counts at the leaderboard's ticket list once one exists
  const unresolvedListHref = buildAchievementTicketListHref(ticket, 'unresolved');
  const allTicketsListHref = buildAchievementTicketListHref(ticket, 'all');

  return (
    <>
      <Trans
        i18nKey={
          isLeaderboardTicket ? 'leaderboardOtherOpenTickets' : 'achievementOtherOpenTickets'
        }
        count={unresolvedCount}
        // eslint-disable-next-line jsx-a11y/anchor-has-content -- the Trans component passes in the link text.
        components={{ 1: unresolvedListHref ? <a href={unresolvedListHref} /> : <span /> }}
      />

      {finishedCount ? (
        <>
          {' '}
          <Trans
            i18nKey="otherClosedTickets"
            count={finishedCount}
            // eslint-disable-next-line jsx-a11y/anchor-has-content -- the Trans component passes in the link text.
            components={{ 1: allTicketsListHref ? <a href={allTicketsListHref} /> : <span /> }}
          />
        </>
      ) : null}
    </>
  );
};
