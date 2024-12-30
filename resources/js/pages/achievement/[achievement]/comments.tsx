import { useTranslation } from 'react-i18next';

import { SEO } from '@/common/components/SEO';
import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';
import { AchievementCommentsMainRoot } from '@/features/comments/AchievementCommentsMainRoot';

const AchievementComments: AppPage<App.Community.Data.AchievementCommentsPageProps> = ({
  achievement,
}) => {
  const { t } = useTranslation();

  return (
    <>
      <SEO
        title={t('Comments - {{achievementTitle}}', { achievementTitle: achievement.title })}
        description={`General discussion about the achievement ${achievement.title}`}
        ogImage={achievement.badgeUnlockedUrl}
      />

      <AppLayout.Main>
        <AchievementCommentsMainRoot />
      </AppLayout.Main>
    </>
  );
};

AchievementComments.layout = (page) => <AppLayout withSidebar={false}>{page}</AppLayout>;

export default AchievementComments;
