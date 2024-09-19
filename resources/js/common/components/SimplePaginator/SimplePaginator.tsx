import type { FC } from 'react';

import {
  BasePagination,
  BasePaginationContent,
  BasePaginationItem,
  BasePaginationNext,
  BasePaginationPrevious,
} from '@/common/components/+vendor/BasePagination';

interface SimplePaginatorProps<TData = unknown> {
  paginatedData: App.Data.PaginatedData<TData>;
}

export const SimplePaginator: FC<SimplePaginatorProps> = ({ paginatedData }) => {
  const {
    perPage,
    links: { nextPageUrl, previousPageUrl },
  } = paginatedData;

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
