import { type ChangeEvent, type FC, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { LuChevronFirst, LuChevronLast, LuChevronLeft, LuChevronRight } from 'react-icons/lu';

import {
  BasePagination,
  BasePaginationContent,
  BasePaginationItem,
  BasePaginationLink,
} from '@/common/components/+vendor/BasePagination';
import { cn } from '@/common/utils/cn';

import { baseButtonVariants } from '../+vendor/BaseButton';
import { BaseSelectNative } from '../+vendor/BaseSelectNative';

interface FullPaginatorProps<TData = unknown> {
  onPageSelectValueChange: (newPageValue: number) => void;
  paginatedData: App.Data.PaginatedData<TData>;
}

export const FullPaginator: FC<FullPaginatorProps> = ({
  onPageSelectValueChange,
  paginatedData,
}) => {
  const { t } = useTranslation();

  const {
    currentPage,
    lastPage,
    links: { firstPageUrl, lastPageUrl, nextPageUrl, previousPageUrl },
  } = paginatedData;

  const [internalValue, setInternalValue] = useState(String(currentPage));

  if (!previousPageUrl && !nextPageUrl) {
    return <span />;
  }

  // Generate an array of all page numbers. We'll use this to populate the select control.
  const pageOptions = Array.from({ length: lastPage }, (_, i) => i + 1);

  const handlePageSelect = (event: ChangeEvent<HTMLSelectElement>) => {
    setInternalValue(event.target.value);
    onPageSelectValueChange(Number(event.target.value));
  };

  const linkClassNames = cn(
    baseButtonVariants({
      size: 'sm',
    }),
    'border-none hover:outline hover:outline-1 hover:outline-neutral-300 hover:light:outline-neutral-200',
    'aria-disabled:pointer-events-none aria-disabled:opacity-50',
  );

  return (
    <BasePagination>
      <BasePaginationContent>
        {/* First page button */}
        <BasePaginationItem aria-label={t('Go to first page')}>
          <BasePaginationLink
            className={linkClassNames}
            href={firstPageUrl ?? '#'}
            aria-disabled={currentPage === 1 ? true : undefined}
            role={currentPage === 1 ? 'link' : undefined}
          >
            <LuChevronFirst className="size-4" aria-hidden="true" />
          </BasePaginationLink>
        </BasePaginationItem>

        {/* Previous page button */}
        <BasePaginationItem aria-label={t('Go to previous page')}>
          <BasePaginationLink
            className={linkClassNames}
            href={previousPageUrl ?? '#'}
            aria-disabled={currentPage === 1 ? true : undefined}
            role={currentPage !== 1 ? 'link' : undefined}
          >
            <LuChevronLeft className="size-4" aria-hidden="true" />
          </BasePaginationLink>
        </BasePaginationItem>

        {/* Page number select */}
        <BasePaginationItem className="flex items-center">
          <BaseSelectNative
            value={internalValue}
            onChange={handlePageSelect}
            className="h-8 min-w-[70px] text-xs leading-5"
          >
            {pageOptions.map((pageNumber) => (
              <option key={`page-value-${pageNumber}`} value={pageNumber.toString()}>
                {t('Page {{pageNumber, number}}', { pageNumber })}
              </option>
            ))}
          </BaseSelectNative>

          <span className="whitespace-nowrap pl-1.5 pr-1 text-neutral-500 light:text-neutral-700">
            {t('of {{pageNumber, number}}', { pageNumber: lastPage })}
          </span>
        </BasePaginationItem>

        {/* Next page button */}
        <BasePaginationItem aria-label={t('Go to next page')}>
          <BasePaginationLink
            className={linkClassNames}
            href={nextPageUrl ?? '#'}
            aria-disabled={currentPage === lastPage ? true : undefined}
            role={currentPage !== lastPage ? 'link' : undefined}
          >
            <LuChevronRight className="size-4" aria-hidden="true" />
          </BasePaginationLink>
        </BasePaginationItem>

        {/* Last page button */}
        <BasePaginationItem aria-label={t('Go to last page')}>
          <BasePaginationLink
            className={linkClassNames}
            href={lastPageUrl ?? '#'}
            aria-disabled={currentPage === lastPage ? true : undefined}
            role={currentPage !== lastPage ? 'link' : undefined}
          >
            <LuChevronLast className="size-4" aria-hidden="true" />
          </BasePaginationLink>
        </BasePaginationItem>
      </BasePaginationContent>
    </BasePagination>
  );
};
