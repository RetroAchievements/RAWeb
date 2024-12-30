import { useTranslation } from 'react-i18next';

import { SEO } from '@/common/components/SEO';
import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';
import { RecentPostsMainRoot } from '@/features/forums/components/RecentPostsMainRoot';

const RecentPosts: AppPage = () => {
  const { t } = useTranslation();

  return (
    <>
      <SEO
        title={t('Recent Posts')}
        description="A list of recent posts that have been made on the RetroAchievements forum."
      />

      <AppLayout.Main>
        <RecentPostsMainRoot />
      </AppLayout.Main>
    </>
  );
};

RecentPosts.layout = (page) => <AppLayout withSidebar={false}>{page}</AppLayout>;

export default RecentPosts;
