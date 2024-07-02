import { Head } from '@inertiajs/react';

import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';
import { RecentPostsMainRoot } from '@/forums/components/RecentPostsMainRoot';

const RecentPosts: AppPage = () => {
  return (
    <>
      <Head title="Recent Posts">
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
