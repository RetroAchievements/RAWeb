import { useTranslation } from 'react-i18next';

import { SEO } from '@/common/components/SEO';
import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';
import { UserCommentsMainRoot } from '@/features/comments/UserCommentsMainRoot';

const UserComments: AppPage<App.Community.Data.UserCommentsPageProps> = ({ targetUser }) => {
  const { t } = useTranslation();

  return (
    <>
      <SEO
        title={t('Comments - {{user}}', { user: targetUser.displayName })}
        description={`Comments left on ${targetUser.displayName}'s wall`}
        ogImage={targetUser.avatarUrl}
      />

      <AppLayout.Main>
        <UserCommentsMainRoot />
      </AppLayout.Main>
    </>
  );
};

UserComments.layout = (page) => <AppLayout withSidebar={false}>{page}</AppLayout>;

export default UserComments;
