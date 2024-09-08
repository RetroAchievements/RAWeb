import { Head } from '@inertiajs/react';

import { usePageProps } from '@/common/hooks/usePageProps';
import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';
import { UserPostsMainRoot } from '@/features/users/components/UserPostsMainRoot';

const UserPosts: AppPage = () => {
  const { targetUser } = usePageProps<App.Community.Data.UserRecentPostsPageProps>();

  return (
    <>
      <Head title={`Forum Posts - ${targetUser.displayName}`}>
        <meta
          name="description"
          content={`A list of ${targetUser.displayName}'s forum posts that have been made on the RetroAchievements forum.`}
        />
      </Head>

      <AppLayout.Main>
        <UserPostsMainRoot />
      </AppLayout.Main>
    </>
  );
};

UserPosts.layout = (page) => <AppLayout withSidebar={false}>{page}</AppLayout>;

export default UserPosts;
