import { useTranslation } from 'react-i18next';

import { SEO } from '@/common/components/SEO';
import { usePageProps } from '@/common/hooks/usePageProps';
import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';
import { UserModerationCommentsMainRoot } from '@/features/comments/UserModerationCommentsMainRoot';

const UserModerationComments: AppPage = () => {
  const { targetUser } = usePageProps<App.Community.Data.UserModerationCommentsPageProps>();

  const { t } = useTranslation();

  return (
    <>
      <SEO
        title={t('Moderation Comments - {{user}}', { user: targetUser.displayName })}
        description={`Comments left on ${targetUser.displayName}'s moderation wall`}
        ogImage={targetUser.avatarUrl}
      />

      <AppLayout.Main>
        <UserModerationCommentsMainRoot />
      </AppLayout.Main>
    </>
  );
};

UserModerationComments.layout = (page) => <AppLayout withSidebar={false}>{page}</AppLayout>;

export default UserModerationComments;
