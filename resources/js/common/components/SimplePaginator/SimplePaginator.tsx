import { useLaravelReactI18n } from 'laravel-react-i18n';
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
  const { t } = useLaravelReactI18n();

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
