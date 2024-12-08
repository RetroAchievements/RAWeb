import { Head } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';
import { LeaderboardCommentsMainRoot } from '@/features/comments/LeaderboardCommentsMainRoot';

const LeaderboardComments: AppPage<App.Community.Data.LeaderboardCommentsPageProps> = ({
  leaderboard,
}) => {
  const { t } = useTranslation();

  const metaDescription = `General discussion about the leaderboard ${leaderboard.title}`;

  return (
    <>
      <Head title={t('Comments - {{leaderboardTitle}}', { leaderboardTitle: leaderboard.title })}>
        <meta name="description" content={metaDescription} />
        <meta name="og:description" content={metaDescription} />

        <meta property="og:image" content={leaderboard.game?.badgeUrl} />
        <meta property="og:type" content="retroachievements:comment-list" />
      </Head>

      <AppLayout.Main>
        <LeaderboardCommentsMainRoot />
      </AppLayout.Main>
    </>
  );
};

LeaderboardComments.layout = (page) => <AppLayout withSidebar={false}>{page}</AppLayout>;

export default LeaderboardComments;
