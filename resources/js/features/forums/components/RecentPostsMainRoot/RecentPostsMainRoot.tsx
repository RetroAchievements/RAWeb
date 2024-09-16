import type { FC } from 'react';

import { RecentPostsCards } from '@/common/components/RecentPostsCards';
import { RecentPostsTable } from '@/common/components/RecentPostsTable';
import { SimplePaginator } from '@/common/components/SimplePaginator';
import { usePageProps } from '@/common/hooks/usePageProps';

import { ForumBreadcrumbs } from '../ForumBreadcrumbs';

export const RecentPostsMainRoot: FC = () => {
  const { paginatedTopics } = usePageProps<App.Community.Data.RecentPostsPageProps>();

  return (
    <div>
      <ForumBreadcrumbs currentPageLabel="Recent Posts" />

      <h1 className="w-full">Recent Posts</h1>

      <div className="lg:hidden">
        <RecentPostsCards paginatedTopics={paginatedTopics} />
      </div>

      <div className="hidden lg:block">
        <RecentPostsTable paginatedTopics={paginatedTopics} />
      </div>

      <div className="mt-2 flex w-full justify-end">
        <SimplePaginator paginatedData={paginatedTopics} />
      </div>
    </div>
  );
};
