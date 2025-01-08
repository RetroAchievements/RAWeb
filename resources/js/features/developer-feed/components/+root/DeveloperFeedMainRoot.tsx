import { type FC, memo } from 'react';
import { useTranslation } from 'react-i18next';

import { ActivePlayerFeed } from '@/common/components/ActivePlayerFeed';
import { UserBreadcrumbs } from '@/common/components/UserBreadcrumbs';
import { UserHeading } from '@/common/components/UserHeading';
import { useFormatNumber } from '@/common/hooks/useFormatNumber';
import { usePageProps } from '@/common/hooks/usePageProps';

import { FeedStatCard } from '../FeedStatCard';
import { RecentAwardsTable } from '../RecentAwardsTable';
import { RecentLeaderboardEntriesTable } from '../RecentLeaderboardEntriesTable';
import { RecentUnlocksTable } from '../RecentUnlocksTable';

export const DeveloperFeedMainRoot: FC = memo(() => {
  const {
    activePlayers,
    awardsContributed,
    developer,
    leaderboardEntriesContributed,
    pointsContributed,
    recentLeaderboardEntries,
    recentPlayerBadges,
    recentUnlocks,
    unlocksContributed,
  } = usePageProps<App.Community.Data.DeveloperFeedPageProps>();

  const { t } = useTranslation();

  const { formatNumber } = useFormatNumber();

  return (
    <div>
      <UserBreadcrumbs user={developer} t_currentPageLabel={t('Developer Feed')} />
      <UserHeading user={developer} wrapperClassName="!mb-1">
        {t('Developer Feed')}
      </UserHeading>

      <div className="flex flex-col gap-12">
        <div className="flex flex-col gap-6">
          <div className="grid gap-1 sm:grid-cols-2 sm:gap-3 lg:grid-cols-4">
            <FeedStatCard t_label={t('Unlocks Contributed')}>
              {formatNumber(unlocksContributed)}
            </FeedStatCard>

            <FeedStatCard t_label={t('Points Contributed')}>
              {formatNumber(pointsContributed)}
            </FeedStatCard>

            <FeedStatCard t_label={t('Awards Contributed')}>
              {formatNumber(awardsContributed)}
            </FeedStatCard>

            <FeedStatCard t_label={t('Leaderboard Entries Contributed')}>
              {formatNumber(leaderboardEntriesContributed)}
            </FeedStatCard>
          </div>

          <div className="flex flex-col">
            <h2 className="border-b-0 text-xl font-semibold">{t('Current Players')}</h2>
            <ActivePlayerFeed initialActivePlayers={activePlayers} hasSearchBar={false} />
          </div>
        </div>

        <RecentUnlocksTable recentUnlocks={recentUnlocks} />
        <RecentAwardsTable recentPlayerBadges={recentPlayerBadges} />
        <RecentLeaderboardEntriesTable recentLeaderboardEntries={recentLeaderboardEntries} />
      </div>
    </div>
  );
});
