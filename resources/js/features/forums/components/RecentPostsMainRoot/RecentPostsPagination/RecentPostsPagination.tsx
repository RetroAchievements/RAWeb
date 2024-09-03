import { useLaravelReactI18n } from 'laravel-react-i18n';
import type { FC } from 'react';

import {
  BasePagination,
  BasePaginationContent,
  BasePaginationItem,
  BasePaginationNext,
  BasePaginationPrevious,
} from '@/common/components/+vendor/BasePagination';
import { usePageProps } from '@/common/hooks/usePageProps';

export const RecentPostsPagination: FC = () => {
  const { paginatedTopics } = usePageProps<App.Community.Data.RecentPostsPageProps>();

  const { t } = useLaravelReactI18n();

  const {
    perPage,
    links: { nextPageUrl, previousPageUrl },
  } = paginatedTopics;

  if (!previousPageUrl && !nextPageUrl) {
    return null;
  }

  return (
    <BasePagination>
      <BasePaginationContent>
        {previousPageUrl ? (
          <BasePaginationItem>
            <BasePaginationPrevious href={previousPageUrl}>
              {t('Previous :count', { count: perPage })}
            </BasePaginationPrevious>
          </BasePaginationItem>
        ) : null}

        {nextPageUrl ? (
          <BasePaginationItem>
            <BasePaginationNext href={nextPageUrl}>
              {t('Next :count', { count: perPage })}
            </BasePaginationNext>
          </BasePaginationItem>
        ) : null}
      </BasePaginationContent>
    </BasePagination>
  );
};
