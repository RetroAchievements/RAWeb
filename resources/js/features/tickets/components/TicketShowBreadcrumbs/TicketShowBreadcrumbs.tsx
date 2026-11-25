import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { AchievementBreadcrumbs } from '@/common/components/AchievementBreadcrumbs';
import { LeaderboardBreadcrumbs } from '@/common/components/LeaderboardBreadcrumbs';
import { usePageProps } from '@/common/hooks/usePageProps';

import { buildAchievementTicketListHref } from '../../utils/buildAchievementTicketListHref';

export const TicketShowBreadcrumbs: FC = () => {
  const { achievement, leaderboard, ticket } =
    usePageProps<App.Platform.Data.TicketShowPageProps>();
  const { t } = useTranslation();

  const { game } = ticket;
  const t_currentTicketCrumb = t('Ticket #{{ticketId}}', { ticketId: ticket.id });

  if (leaderboard) {
    return (
      <LeaderboardBreadcrumbs
        leaderboard={leaderboard}
        game={game}
        system={game.system}
        shouldCollapseRootCrumbs={true}
        t_currentPageLabel={t_currentTicketCrumb}
      />
    );
  }

  const achievementTicketsHref = buildAchievementTicketListHref(ticket);

  return (
    <AchievementBreadcrumbs
      achievement={achievement ?? undefined}
      game={game}
      system={game.system}
      parentPage={
        achievementTicketsHref ? { href: achievementTicketsHref, t_label: t('Tickets') } : undefined
      }
      shouldCollapseRootCrumbs={true}
      t_currentPageLabel={t_currentTicketCrumb}
    />
  );
};
