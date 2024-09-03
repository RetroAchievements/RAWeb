import { useLaravelReactI18n } from 'laravel-react-i18n';
import type { FC } from 'react';

import { ForumBreadcrumbs } from '../ForumBreadcrumbs';
import { RecentPostsCards } from './RecentPostsCards';
import { RecentPostsPagination } from './RecentPostsPagination';
import { RecentPostsTable } from './RecentPostsTable';

export const RecentPostsMainRoot: FC = () => {
  const { t } = useLaravelReactI18n();

  return (
    <div>
      <ForumBreadcrumbs currentPageLabel="Recent Posts" />

      <h1 className="w-full">{t('Recent Posts')}</h1>

      <div className="lg:hidden">
        <RecentPostsCards />
      </div>

      <div className="hidden lg:block">
        <RecentPostsTable />
      </div>

      <div className="mt-2 flex w-full justify-end">
        <RecentPostsPagination />
      </div>
    </div>
  );
};
