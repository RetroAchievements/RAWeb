import type { Table } from '@tanstack/react-table';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import type { ReactNode } from 'react';
import { LuArrowLeft, LuArrowLeftToLine, LuArrowRight, LuArrowRightToLine } from 'react-icons/lu';
import type { RouteName } from 'ziggy-js';

import { BaseButton } from '@/common/components/+vendor/BaseButton';
import { BasePagination, BasePaginationContent } from '@/common/components/+vendor/BasePagination';
import { useFormatNumber } from '@/common/hooks/useFormatNumber';

import { useDataTablePrefetchPagination } from '../../hooks/useDataTablePrefetchPagination';

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

  const { formatNumber } = useFormatNumber();

  // Given the user hovers over a pagination button, it is very likely they will
  // wind up clicking the button. Queries are cheap, so prefetch the destination page.
  const { prefetchPagination } = useDataTablePrefetchPagination(table, tableApiRouteName);

  const handlePageChange = (newPageIndex: number, isNext: boolean) => {
    table.setPageIndex(newPageIndex);
    const nextPageIndex = isNext ? newPageIndex + 1 : newPageIndex - 1;

    // Prefetch the next or previous page after setting the new page.
    if (nextPageIndex >= 0 && nextPageIndex < table.getPageCount()) {
      prefetchPagination(nextPageIndex);
    }

    // Scroll the user to the top of the page.
    setTimeout(() => {
      window.scrollTo({
        top: document.getElementById('pagination-scroll-target')?.offsetTop,
        behavior: 'smooth',
      });
    });
  };

  return (
    <div className="flex items-center justify-between">
      <div />

      <div className="flex items-center gap-6 lg:gap-8">
        <p className="text-neutral-200 light:text-neutral-900">
          {t('Page :count of :total', {
            count: formatNumber(pagination.pageIndex + 1),
            total: formatNumber(table.getPageCount()),
          })}
        </p>

        <BasePagination>
          <BasePaginationContent className="gap-2">
            <BaseButton
              className="h-8 w-8 p-0"
              onClick={() => handlePageChange(0, false)}
              onMouseEnter={() => prefetchPagination(0)}
              disabled={!table.getCanPreviousPage()}
            >
              <span className="sr-only">{t('Go to first page')}</span>
              <LuArrowLeftToLine className="h-4 w-4" />
            </BaseButton>

            <BaseButton
              className="h-8 w-8 p-0"
              onClick={() => handlePageChange(pagination.pageIndex - 1, false)}
              onMouseEnter={() => prefetchPagination(pagination.pageIndex - 1)}
              disabled={!table.getCanPreviousPage()}
            >
              <span className="sr-only">{t('Go to previous page')}</span>
              <LuArrowLeft className="h-4 w-4" />
            </BaseButton>

            <BaseButton
              className="h-8 w-8 p-0"
              onClick={() => handlePageChange(pagination.pageIndex + 1, true)}
              onMouseEnter={() => prefetchPagination(pagination.pageIndex + 1)}
              disabled={!table.getCanNextPage()}
            >
              <span className="sr-only">{t('Go to next page')}</span>
              <LuArrowRight className="h-4 w-4" />
            </BaseButton>

            <BaseButton
              className="h-8 w-8 p-0"
              onClick={() => handlePageChange(table.getPageCount() - 1, true)}
              onMouseEnter={() => prefetchPagination(table.getPageCount() - 1)}
              disabled={!table.getCanNextPage()}
            >
              <span className="sr-only">{t('Go to last page')}</span>
              <LuArrowRightToLine className="h-4 w-4" />
            </BaseButton>
          </BasePaginationContent>
        </BasePagination>
      </div>
    </div>
  );
}
