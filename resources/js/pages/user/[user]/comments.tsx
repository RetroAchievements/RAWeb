import { Head } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';

import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';
import { UserCommentsMainRoot } from '@/features/comments/UserCommentsMainRoot';

const UserComments: AppPage<App.Community.Data.UserCommentsPageProps> = ({ targetUser }) => {
  const { t } = useLaravelReactI18n();

  return (
    <>
      <Head title={t('Comments - :user', { user: targetUser.displayName })}>
        <meta name="description" content={`Comments left on ${targetUser.displayName}'s wall`} />

        <meta property="og:image" content={targetUser.avatarUrl} />
        <meta property="og:type" content="retroachievements:comment-list" />
      </Head>

      <AppLayout.Main>
        <UserCommentsMainRoot />
      </AppLayout.Main>
    </>
  );
};

UserComments.layout = (page) => <AppLayout withSidebar={false}>{page}</AppLayout>;

export default UserComments;
