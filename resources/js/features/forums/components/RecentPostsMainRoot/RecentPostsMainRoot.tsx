import { useLaravelReactI18n } from 'laravel-react-i18n';
import type { FC } from 'react';

import { EmptyState } from '@/common/components/EmptyState';
import { RecentPostsCards } from '@/common/components/RecentPostsCards';
import { RecentPostsTable } from '@/common/components/RecentPostsTable';
import { SimplePaginator } from '@/common/components/SimplePaginator';
import { usePageProps } from '@/common/hooks/usePageProps';

import { ForumBreadcrumbs } from '../../../../common/components/ForumBreadcrumbs';

export const RecentPostsMainRoot: FC = () => {
  const { paginatedTopics } = usePageProps<App.Community.Data.RecentPostsPageProps>();

  const { t } = useLaravelReactI18n();

  return (
    <div>
      <ForumBreadcrumbs t_currentPageLabel={t('Recent Posts')} />

      <h1 className="w-full">{t('Recent Posts')}</h1>

      {paginatedTopics.items.length > 0 ? (
        <>
          <div className="lg:hidden">
            <RecentPostsCards paginatedTopics={paginatedTopics} />
          </div>

          <div className="hidden lg:block">
            <RecentPostsTable paginatedTopics={paginatedTopics} />
          </div>
        </>
      ) : (
        <EmptyState>{t('No recent posts could be found.')}</EmptyState>
      )}

      <div className="mt-2 flex w-full justify-end">
        <SimplePaginator paginatedData={paginatedTopics} />
      </div>
    </div>
  );
};
