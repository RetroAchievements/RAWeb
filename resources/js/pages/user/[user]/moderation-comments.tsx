import { Head } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

import { usePageProps } from '@/common/hooks/usePageProps';
import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';
import { UserModerationCommentsMainRoot } from '@/features/comments/UserModerationCommentsMainRoot';

const UserModerationComments: AppPage = () => {
  const { targetUser } = usePageProps<App.Community.Data.UserModerationCommentsPageProps>();

  const { t } = useTranslation();

  const metaDescription = `Comments left on ${targetUser.displayName}'s moderation wall`;

  return (
    <>
      <Head title={t('Moderation Comments - {{user}}', { user: targetUser.displayName })}>
        <meta name="description" content={metaDescription} />
        <meta name="og:description" content={metaDescription} />

        <meta property="og:image" content={targetUser.avatarUrl} />
        <meta property="og:type" content="retroachievements:comment-list" />
      </Head>

      <AppLayout.Main>
        <UserModerationCommentsMainRoot />
      </AppLayout.Main>
    </>
  );
};

UserModerationComments.layout = (page) => <AppLayout withSidebar={false}>{page}</AppLayout>;

export default UserModerationComments;
