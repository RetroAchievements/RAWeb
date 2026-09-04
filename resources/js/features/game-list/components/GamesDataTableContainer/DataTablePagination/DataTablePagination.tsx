import type { Table } from '@tanstack/react-table';
import type { ReactNode } from 'react';
import type { RouteName } from 'ziggy-js';

import {
  DataTablePaginationControls,
  scrollToPaginationScrollTarget,
} from '@/common/components/DataTablePaginationControls';

import { useDataTablePrefetchPagination } from '../../../hooks/useDataTablePrefetchPagination';
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
  const { pagination } = table.getState();

  const { prefetchPagination } = useDataTablePrefetchPagination(
    table,
    tableApiRouteName,
    tableApiRouteParams,
  );

  const handlePageSizeChange = (newPageSize: number) => {
    // If the user is changing the page size and they're not on the first page,
    // auto-scroll to the top. Otherwise, things can quickly get disorienting.
    if (pagination.pageIndex !== 0) {
      scrollToPaginationScrollTarget();
    }

    table.setPagination({ pageIndex: 0, pageSize: newPageSize });
  };

  return (
    <div className="flex items-center justify-center sm:justify-between">
      {/* TODO X of Y rows selected */}
      <div />

      <div className="flex flex-col items-center gap-2 sm:flex-row sm:gap-6 lg:gap-8">
        <PageSizeSelect
          value={pagination.pageSize}
          onMouseEnterPageSizeOption={(pageSize) => {
            prefetchPagination({ newPageIndex: 0, newPageSize: pageSize });
          }}
          onChange={handlePageSizeChange}
        />

        <DataTablePaginationControls
          currentPage={pagination.pageIndex + 1}
          lastPage={table.getPageCount()}
          onPageChange={(pageNumber) => table.setPageIndex(pageNumber - 1)}
          onPrefetchPage={(pageNumber) =>
            prefetchPagination({
              newPageIndex: pageNumber - 1,
              newPageSize: pagination.pageSize,
            })
          }
        />
      </div>
    </div>
  );
}
