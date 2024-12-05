import type { Table } from '@tanstack/react-table';
import type { ReactNode } from 'react';
import { useTranslation } from 'react-i18next';
import { LuArrowLeft, LuArrowLeftToLine, LuArrowRight, LuArrowRightToLine } from 'react-icons/lu';
import type { RouteName } from 'ziggy-js';

import { BaseButton } from '@/common/components/+vendor/BaseButton';
import { BasePagination, BasePaginationContent } from '@/common/components/+vendor/BasePagination';

import { useDataTablePrefetchPagination } from '../../hooks/useDataTablePrefetchPagination';
import { ManualPaginatorField } from './ManualPaginatorField';
import { PageSizeSelect } from './PageSizeSelect';

interface DataTablePaginationProps<TData> {
  table: Table<TData>;
  tableApiRouteName?: RouteName;
  tableApiRouteParams?: Record<string, unknown>;
}

export function DataTablePagination<TData>({
  table,
  tableApiRouteParams,
  tableApiRouteName = 'api.game.index',
}: DataTablePaginationProps<TData>): ReactNode {
  const { t } = useTranslation();

  const { pagination } = table.getState();

  // Given the user hovers over a pagination button, it is very likely they will
  // wind up clicking the button. Queries are cheap, so prefetch the destination page.
  const { prefetchPagination } = useDataTablePrefetchPagination(
    table,
    tableApiRouteName,
    tableApiRouteParams,
  );

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
      prefetchPagination({
        newPageIndex: Math.min(newPageIndex + 1, lastPageIndex),
        newPageSize: pagination.pageSize,
      });
    }
    if (canPrefetchPrevious) {
      prefetchPagination({
        newPageIndex: Math.max(newPageIndex - 1, 0),
        newPageSize: pagination.pageSize,
      });
    }

    scrollToTopOfPage();
  };

  const handlePageSizeChange = (newPageSize: number) => {
    // If the user is changing the page size and they're not on the first page,
    // auto-scroll to the top. Otherwise, things can quickly get disorienting.
    if (pagination.pageIndex !== 0) {
      scrollToTopOfPage();
    }

    table.setPagination({ pageIndex: 0, pageSize: newPageSize });
  };

  return (
    <div className="flex items-center justify-center sm:justify-between">
      {/* TODO X of Y rows selected */}
      <div />

      <div className="flex flex-col items-center gap-2 sm:flex-row sm:gap-6 lg:gap-8">
        <PageSizeSelect
          value={table.getState().pagination.pageSize}
          onMouseEnterPageSizeOption={(pageSize) => {
            prefetchPagination({ newPageIndex: 0, newPageSize: pageSize });
          }}
          onChange={handlePageSizeChange}
        />

        <BasePagination className="flex items-center gap-6 lg:gap-8">
          <ManualPaginatorField table={table} onPageChange={handlePageChange} />

          <BasePaginationContent className="gap-2" role="group">
            <BaseButton
              className="size-8 p-0"
              onClick={() => handlePageChange(0, ['next'])}
              onMouseEnter={() =>
                prefetchPagination({ newPageIndex: 0, newPageSize: pagination.pageSize })
              }
              disabled={!table.getCanPreviousPage()}
              aria-label={t('Go to first page')}
            >
              <LuArrowLeftToLine className="size-4" aria-hidden={true} />
            </BaseButton>

            <BaseButton
              className="size-8 p-0"
              onClick={() => handlePageChange(pagination.pageIndex - 1, ['previous'])}
              onMouseEnter={() =>
                prefetchPagination({
                  newPageIndex: pagination.pageIndex - 1,
                  newPageSize: pagination.pageSize,
                })
              }
              disabled={!table.getCanPreviousPage()}
              aria-label={t('Go to previous page')}
            >
              <LuArrowLeft className="size-4" aria-hidden={true} />
            </BaseButton>

            <BaseButton
              className="size-8 p-0"
              onClick={() => handlePageChange(pagination.pageIndex + 1, ['next'])}
              onMouseEnter={() =>
                prefetchPagination({
                  newPageIndex: pagination.pageIndex + 1,
                  newPageSize: pagination.pageSize,
                })
              }
              disabled={!table.getCanNextPage()}
              aria-label={t('Go to next page')}
            >
              <LuArrowRight className="size-4" aria-hidden={true} />
            </BaseButton>

            <BaseButton
              className="size-8 p-0"
              onClick={() => handlePageChange(table.getPageCount() - 1, ['previous'])}
              onMouseEnter={() =>
                prefetchPagination({
                  newPageIndex: table.getPageCount() - 1,
                  newPageSize: pagination.pageSize,
                })
              }
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
