import { usePage } from '@inertiajs/react';
import type { FC } from 'react';

import {
  BasePagination,
  BasePaginationContent,
  BasePaginationItem,
  BasePaginationNext,
  BasePaginationPrevious,
} from '@/common/components/+vendor/BasePagination';
import type { RecentPostsPageProps } from '@/forums/models';

export const RecentPostsPagination: FC = () => {
  const { previousPageUrl, nextPageUrl, maxPerPage } = usePage<RecentPostsPageProps>().props;

  if (!previousPageUrl && !nextPageUrl) {
    return null;
  }

  return (
    <BasePagination>
      <BasePaginationContent>
        {previousPageUrl ? (
          <BasePaginationItem>
            <BasePaginationPrevious href={previousPageUrl}>
              Previous {maxPerPage}
            </BasePaginationPrevious>
          </BasePaginationItem>
        ) : null}

        {nextPageUrl ? (
          <BasePaginationItem>
            <BasePaginationNext href={nextPageUrl}>Next {maxPerPage}</BasePaginationNext>
          </BasePaginationItem>
        ) : null}
      </BasePaginationContent>
    </BasePagination>
  );
};
