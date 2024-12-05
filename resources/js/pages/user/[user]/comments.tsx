import { Head } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';
import { UserCommentsMainRoot } from '@/features/comments/UserCommentsMainRoot';

const UserComments: AppPage<App.Community.Data.UserCommentsPageProps> = ({ targetUser }) => {
  const { t } = useTranslation();

  const metaDescription = `Comments left on ${targetUser.displayName}'s wall`;

  return (
    <>
      <Head title={t('Comments - {{user}}', { user: targetUser.displayName })}>
        <meta name="description" content={metaDescription} />
        <meta name="og:description" content={metaDescription} />

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
