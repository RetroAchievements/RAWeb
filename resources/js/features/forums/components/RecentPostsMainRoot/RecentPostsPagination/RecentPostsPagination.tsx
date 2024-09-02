import { usePage } from '@inertiajs/react';
import type { FC } from 'react';

import {
  BasePagination,
  BasePaginationContent,
  BasePaginationItem,
  BasePaginationNext,
  BasePaginationPrevious,
} from '@/common/components/+vendor/BasePagination';
import type { RecentPostsPageProps } from '@/features/forums/models';

export const RecentPostsPagination: FC = () => {
  const { paginatedTopics } = usePage<RecentPostsPageProps>().props;

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
              Previous {perPage}
            </BasePaginationPrevious>
          </BasePaginationItem>
        ) : null}

        {nextPageUrl ? (
          <BasePaginationItem>
            <BasePaginationNext href={nextPageUrl}>Next {perPage}</BasePaginationNext>
          </BasePaginationItem>
        ) : null}
      </BasePaginationContent>
    </BasePagination>
  );
};
