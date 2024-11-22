import { Head } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';
import { RecentPostsMainRoot } from '@/features/forums/components/RecentPostsMainRoot';

const RecentPosts: AppPage = () => {
  const { t } = useTranslation();

  return (
    <>
      <Head title={t('Recent Posts')}>
        <meta
          name="description"
          content="A list of recent posts that have been made on the RetroAchievements forum."
        />
      </Head>

      <AppLayout.Main>
        <RecentPostsMainRoot />
      </AppLayout.Main>
    </>
  );
};

RecentPosts.layout = (page) => <AppLayout withSidebar={false}>{page}</AppLayout>;

export default RecentPosts;
