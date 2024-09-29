import type { FC } from 'react';

import { RecentPostsCards } from '@/common/components/RecentPostsCards';
import { RecentPostsTable } from '@/common/components/RecentPostsTable';
import { SimplePaginator } from '@/common/components/SimplePaginator';
import { UserHeading } from '@/common/components/UserHeading';
import { usePageProps } from '@/common/hooks/usePageProps';

import { UserBreadcrumbs } from '../UserBreadcrumbs';

export const UserPostsMainRoot: FC = () => {
  const { targetUser, paginatedTopics } =
    usePageProps<App.Community.Data.UserRecentPostsPageProps>();

  return (
    <div>
      <UserBreadcrumbs currentPageLabel="Forum Posts" user={targetUser} />
      <UserHeading user={targetUser}>{targetUser.displayName}'s Forum Posts</UserHeading>

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

      <div className="mt-2 flex w-full justify-end">
        <SimplePaginator paginatedData={paginatedTopics} />
      </div>
    </div>
  );
};
