import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { EmptyState } from '@/common/components/EmptyState';
import { RecentPostsCards } from '@/common/components/RecentPostsCards';
import { RecentPostsTable } from '@/common/components/RecentPostsTable';
import { SimplePaginator } from '@/common/components/SimplePaginator';
import { UserHeading } from '@/common/components/UserHeading';
import { usePageProps } from '@/common/hooks/usePageProps';

import { UserBreadcrumbs } from '../UserBreadcrumbs';

export const UserPostsMainRoot: FC = () => {
  const { targetUser, paginatedTopics } =
    usePageProps<App.Community.Data.UserRecentPostsPageProps>();

  const { t } = useTranslation();

  return (
    <div>
      <UserBreadcrumbs t_currentPageLabel={t('Forum Posts')} user={targetUser} />
      <UserHeading user={targetUser}>
        {t("{{user}}'s Forum Posts", { user: targetUser.displayName })}
      </UserHeading>

      {paginatedTopics.items.length > 0 ? (
        <>
          <div className="lg:hidden">
            <RecentPostsCards paginatedTopics={paginatedTopics} showUser={false} />
          </div>

          <div className="hidden lg:block">
            <RecentPostsTable
              paginatedTopics={paginatedTopics}
              showAdditionalPosts={false}
              showLastPostBy={false}
            />
          </div>
        </>
      ) : (
        <EmptyState>
          {t("{{user}} doesn't have any forum posts.", { user: targetUser.displayName })}
        </EmptyState>
      )}

      <div className="mt-2 flex w-full justify-end">
        <SimplePaginator paginatedData={paginatedTopics} />
      </div>
    </div>
  );
};
