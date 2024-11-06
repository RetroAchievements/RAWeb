import { Head } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';

import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';
import { AchievementCommentsMainRoot } from '@/features/comments/AchievementCommentsMainRoot';

const AchievementComments: AppPage<App.Community.Data.AchievementCommentsPageProps> = ({
  achievement,
}) => {
  const { t } = useLaravelReactI18n();

  return (
    <>
      <Head title={t('Comments - :achievementTitle', { achievementTitle: achievement.title })}>
        <meta
          name="description"
          content={`General discussion about the achievement ${achievement.title}`}
        />

        <meta property="og:image" content={achievement.badgeUnlockedUrl} />
        <meta property="og:type" content="retroachievements:comment-list" />
      </Head>

      <AppLayout.Main>
        <AchievementCommentsMainRoot />
      </AppLayout.Main>
    </>
  );
};

AchievementComments.layout = (page) => <AppLayout withSidebar={false}>{page}</AppLayout>;

export default AchievementComments;
