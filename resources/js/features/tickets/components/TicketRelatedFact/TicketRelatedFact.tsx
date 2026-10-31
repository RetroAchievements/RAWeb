import type { FC } from 'react';
import { Trans, useTranslation } from 'react-i18next';

import { InertiaLink } from '@/common/components/InertiaLink';
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

  const resolvedCount = relatedTickets.filter(
    (relatedTicket) => relatedTicket.state === 'resolved',
  ).length;

  // TODO point these counts at the leaderboard's ticket list once one exists
  const unresolvedListHref = buildAchievementTicketListHref(ticket, 'unresolved');
  const resolvedListHref = buildAchievementTicketListHref(ticket, 'resolved');

  return (
    <>
      <Trans
        i18nKey={
          isLeaderboardTicket ? 'leaderboardOtherOpenTickets' : 'achievementOtherOpenTickets'
        }
        count={unresolvedCount}
        components={{
          1: unresolvedListHref ? <InertiaLink href={unresolvedListHref} /> : <span />,
        }}
      />

      {resolvedCount ? (
        <>
          {' '}
          <Trans
            i18nKey="otherResolvedTickets"
            count={resolvedCount}
            components={{
              1: resolvedListHref ? <InertiaLink href={resolvedListHref} /> : <span />,
            }}
          />
        </>
      ) : null}
    </>
  );
};
