import { type ChangeEvent, type FC, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { LuChevronFirst, LuChevronLast, LuChevronLeft, LuChevronRight } from 'react-icons/lu';

import { baseButtonVariants } from '@/common/components/+vendor/BaseButton';
import {
  BasePagination,
  BasePaginationContent,
  BasePaginationItem,
} from '@/common/components/+vendor/BasePagination';
import { BaseSelectNative } from '@/common/components/+vendor/BaseSelectNative';
import { cn } from '@/common/utils/cn';

/**
 * This pagination component is styled to be similar to FullPaginator, but
 * it instead uses callback-based navigation instead of Inertia links.
 *
 * We can't use FullPaginator directly because it expects `App.Data.PaginatedData`
 * with Inertia URLs (firstPageUrl, previousPageUrl, etc) and renders anchor tags
 * that trigger full page loads. Search uses React Query for client-side data fetching,
 * so we need button-based navigation that updates state without page reloads.
 */

interface SearchPaginationProps {
  currentPage: number;
  lastPage: number;
  onPageChange: (page: number) => void;
}

export const SearchPagination: FC<SearchPaginationProps> = ({
  currentPage,
  lastPage,
  onPageChange,
}) => {
  const { t } = useTranslation();

  const [internalValue, setInternalValue] = useState(String(currentPage));

  // Don't render if there's only one page.
  if (lastPage <= 1) {
    return null;
  }

  // Generate an array of all page numbers for the select dropdown.
  const pageOptions = Array.from({ length: lastPage }, (_, i) => i + 1);

  const handleNavigation = (newPage: number) => {
    window.scrollTo({ top: 0, behavior: 'instant' });

    setInternalValue(String(newPage));
    onPageChange(newPage);
  };

  const handlePageSelect = (event: ChangeEvent<HTMLSelectElement>) => {
    handleNavigation(Number(event.target.value));
  };

  const buttonClassNames = cn(
    baseButtonVariants({ size: 'sm' }),
    'border-none hover:outline hover:outline-1 hover:outline-neutral-300 hover:light:outline-neutral-200',
    'disabled:pointer-events-none disabled:opacity-50',
  );

  return (
    <BasePagination className="flex w-full justify-end">
      <BasePaginationContent>
        {/* First page button */}
        <BasePaginationItem aria-label={t('Go to first page')}>
          <button
            className={buttonClassNames}
            disabled={currentPage === 1}
            onClick={() => handleNavigation(1)}
          >
            <LuChevronFirst className="size-4" aria-hidden="true" />
          </button>
        </BasePaginationItem>

        {/* Previous page button */}
        <BasePaginationItem aria-label={t('Go to previous page')}>
          <button
            className={buttonClassNames}
            disabled={currentPage === 1}
            onClick={() => handleNavigation(currentPage - 1)}
          >
            <LuChevronLeft className="size-4" aria-hidden="true" />
          </button>
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
          <button
            className={buttonClassNames}
            disabled={currentPage === lastPage}
            onClick={() => handleNavigation(currentPage + 1)}
          >
            <LuChevronRight className="size-4" aria-hidden="true" />
          </button>
        </BasePaginationItem>

        {/* Last page button */}
        <BasePaginationItem aria-label={t('Go to last page')}>
          <button
            className={buttonClassNames}
            disabled={currentPage === lastPage}
            onClick={() => handleNavigation(lastPage)}
          >
            <LuChevronLast className="size-4" aria-hidden="true" />
          </button>
        </BasePaginationItem>
      </BasePaginationContent>
    </BasePagination>
  );
};
