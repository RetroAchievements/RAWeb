import type { Table } from '@tanstack/react-table';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import type { ReactNode } from 'react';
import { LuArrowLeft, LuArrowLeftToLine, LuArrowRight, LuArrowRightToLine } from 'react-icons/lu';
import type { RouteName } from 'ziggy-js';

import { BaseButton } from '@/common/components/+vendor/BaseButton';
import { BasePagination, BasePaginationContent } from '@/common/components/+vendor/BasePagination';

import { useDataTablePrefetchPagination } from '../../hooks/useDataTablePrefetchPagination';
import { ManualPaginatorField } from './ManualPaginatorField';

interface DataTablePaginationProps<TData> {
  table: Table<TData>;
  tableApiRouteName?: RouteName;
}

// Lazy-loaded, so using a default export.
export default function DataTablePagination<TData>({
  table,
  tableApiRouteName = 'api.game.index',
}: DataTablePaginationProps<TData>): ReactNode {
  const { t } = useLaravelReactI18n();

  const { pagination } = table.getState();

  // Given the user hovers over a pagination button, it is very likely they will
  // wind up clicking the button. Queries are cheap, so prefetch the destination page.
  const { prefetchPagination } = useDataTablePrefetchPagination(table, tableApiRouteName);

  const scrollToTopOfPage = () => {
    const scrollTarget = document.getElementById('pagination-scroll-target');

    if (!scrollTarget) {
      return;
    }

    window.scrollTo({
      top: scrollTarget.offsetTop,
      behavior: 'smooth',
    });
  };

  /**
   * Handles page change and optional prefetching of adjacent pages for a smoother user experience.
   *
   * @param {Array<'next' | 'previous'>} [prefetchDirections] - Optional. Specifies whether to prefetch
   * adjacent pages. 'next' prefetches the following page, and 'previous' prefetches the previous page.
   * This is helpful for loading pages before the user actually clicks the pagination button.
   *
   * @example
   * // Navigate to page 3 and prefetch the next page:
   * handlePageChange(3, ['next']);
   *
   * // Navigate to page 2 and prefetch both next and previous pages:
   * handlePageChange(2, ['next', 'previous']);
   */
  const handlePageChange = (
    newPageIndex: number,
    prefetchDirections?: Array<'next' | 'previous'>,
  ) => {
    // Update the current page.
    table.setPageIndex(newPageIndex);

    // Handle prefetching of adjacent pages.
    const lastPageIndex = table.getPageCount() - 1;
    const canPrefetchNext = prefetchDirections?.includes('next') && newPageIndex < lastPageIndex;
    const canPrefetchPrevious = prefetchDirections?.includes('previous') && newPageIndex > 0;

    if (canPrefetchNext) {
      prefetchPagination(Math.min(newPageIndex + 1, lastPageIndex));
    }
    if (canPrefetchPrevious) {
      prefetchPagination(Math.max(newPageIndex - 1, 0));
    }

    scrollToTopOfPage();
  };

  return (
    <div className="flex items-center justify-between">
      <div />

      <div className="flex items-center gap-6 lg:gap-8">
        <BasePagination className="flex items-center gap-6 lg:gap-8">
          <ManualPaginatorField table={table} onPageChange={handlePageChange} />

          <BasePaginationContent className="gap-2" role="group">
            <BaseButton
              className="size-8 p-0"
              onClick={() => handlePageChange(0, ['next'])}
              onMouseEnter={() => prefetchPagination(0)}
              disabled={!table.getCanPreviousPage()}
              aria-label={t('Go to first page')}
            >
              <LuArrowLeftToLine className="size-4" aria-hidden={true} />
            </BaseButton>

            <BaseButton
              className="size-8 p-0"
              onClick={() => handlePageChange(pagination.pageIndex - 1, ['previous'])}
              onMouseEnter={() => prefetchPagination(pagination.pageIndex - 1)}
              disabled={!table.getCanPreviousPage()}
              aria-label={t('Go to previous page')}
            >
              <LuArrowLeft className="size-4" aria-hidden={true} />
            </BaseButton>

            <BaseButton
              className="size-8 p-0"
              onClick={() => handlePageChange(pagination.pageIndex + 1, ['next'])}
              onMouseEnter={() => prefetchPagination(pagination.pageIndex + 1)}
              disabled={!table.getCanNextPage()}
              aria-label={t('Go to next page')}
            >
              <LuArrowRight className="size-4" aria-hidden={true} />
            </BaseButton>

            <BaseButton
              className="size-8 p-0"
              onClick={() => handlePageChange(table.getPageCount() - 1, ['previous'])}
              onMouseEnter={() => prefetchPagination(table.getPageCount() - 1)}
              disabled={!table.getCanNextPage()}
              aria-label={t('Go to last page')}
            >
              <LuArrowRightToLine className="size-4" aria-hidden={true} />
            </BaseButton>
          </BasePaginationContent>
        </BasePagination>
      </div>
    </div>
  );
}
