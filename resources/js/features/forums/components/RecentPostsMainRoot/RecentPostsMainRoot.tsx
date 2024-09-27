import type { FC } from 'react';

import { RecentPostsCards } from '@/common/components/RecentPostsCards';
import { RecentPostsTable } from '@/common/components/RecentPostsTable';
import { SimplePaginator } from '@/common/components/SimplePaginator';
import { usePageProps } from '@/common/hooks/usePageProps';
import { useLaravelReactI18n } from '@/lib/laravel-react-i18n';

import { ForumBreadcrumbs } from '../ForumBreadcrumbs';

export const RecentPostsMainRoot: FC = () => {
  const { paginatedTopics } = usePageProps<App.Community.Data.RecentPostsPageProps>();

  const { t } = useLaravelReactI18n();

  return (
    <div>
      <ForumBreadcrumbs currentPageLabel={t('Recent Posts')} />

      <h1 className="w-full">{t('Recent Posts')}</h1>

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
