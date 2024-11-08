import { Head } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';

import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';
import { LeaderboardCommentsMainRoot } from '@/features/comments/LeaderboardCommentsMainRoot';

const LeaderboardComments: AppPage<App.Community.Data.LeaderboardCommentsPageProps> = ({
  leaderboard,
}) => {
  const { t } = useLaravelReactI18n();

  return (
    <>
      <Head title={t('Comments - :leaderboardTitle', { leaderboardTitle: leaderboard.title })}>
        <meta
          name="description"
          content={`General discussion about the leaderboard ${leaderboard.title}`}
        />

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
