import { useTranslation } from 'react-i18next';

import { SEO } from '@/common/components/SEO';
import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';
import { LeaderboardCommentsMainRoot } from '@/features/comments/LeaderboardCommentsMainRoot';

const LeaderboardComments: AppPage<App.Community.Data.LeaderboardCommentsPageProps> = ({
  leaderboard,
}) => {
  const { t } = useTranslation();

  return (
    <>
      <SEO
        title={t('Comments - {{leaderboardTitle}}', { leaderboardTitle: leaderboard.title })}
        description={`General discussion about the leaderboard ${leaderboard.title}`}
        ogImage={leaderboard.game?.badgeUrl}
      />

      <AppLayout.Main>
        <LeaderboardCommentsMainRoot />
      </AppLayout.Main>
    </>
  );
};

LeaderboardComments.layout = (page) => <AppLayout withSidebar={false}>{page}</AppLayout>;

export default LeaderboardComments;
