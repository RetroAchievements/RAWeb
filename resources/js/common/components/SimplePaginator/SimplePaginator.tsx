import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

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
  const { t } = useTranslation();

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
              {t('Previous {{count, number}}', { count: perPage })}
            </BasePaginationPrevious>
          </BasePaginationItem>
        ) : null}

        {nextPageUrl ? (
          <BasePaginationItem>
            <BasePaginationNext href={nextPageUrl}>
              {t('Next {{count, number}}', { count: perPage })}
            </BasePaginationNext>
          </BasePaginationItem>
        ) : null}
      </BasePaginationContent>
    </BasePagination>
  );
};
