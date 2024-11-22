import { Head } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

import { usePageProps } from '@/common/hooks/usePageProps';
import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';
import { UserPostsMainRoot } from '@/features/users/components/UserPostsMainRoot';

const UserPosts: AppPage = () => {
  const { targetUser } = usePageProps<App.Community.Data.UserRecentPostsPageProps>();

  const { t } = useTranslation();

  return (
    <>
      <Head title={t('Forum Posts - {{user}}', { user: targetUser.displayName })}>
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
