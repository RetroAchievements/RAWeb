import type {
  ColumnFiltersState,
  PaginationState,
  SortingState,
  VisibilityState,
} from '@tanstack/react-table';
import { flexRender, getCoreRowModel, useReactTable } from '@tanstack/react-table';
import { type Dispatch, type FC, type SetStateAction } from 'react';
import { useMemo } from 'react';

import {
  BaseTable,
  BaseTableBody,
  BaseTableCell,
  BaseTableHead,
  BaseTableHeader,
  BaseTableRow,
} from '@/common/components/+vendor/BaseTable';
import { useGameListQuery } from '@/common/hooks/useGameListQuery';
import { usePageProps } from '@/common/hooks/usePageProps';
import { cn } from '@/utils/cn';

import { buildColumnDefinitions } from './buildColumnDefinitions';
import { DataTablePagination } from './DataTablePagination';
import { WantToPlayGamesDataTableToolbar } from './WantToPlayGamesDataTableToolbar';

// These values are all generated from `useGameListState`.
interface WantToPlayGamesDataTableProps {
  columnFilters: ColumnFiltersState;
  columnVisibility: VisibilityState;
  pagination: PaginationState;
  setColumnFilters: Dispatch<SetStateAction<ColumnFiltersState>>;
  setColumnVisibility: Dispatch<SetStateAction<VisibilityState>>;
  setPagination: Dispatch<SetStateAction<PaginationState>>;
  setSorting: Dispatch<SetStateAction<SortingState>>;
  sorting: SortingState;
}

export const WantToPlayGamesDataTable: FC<WantToPlayGamesDataTableProps> = ({
  columnFilters,
  columnVisibility,
  pagination,
  setColumnFilters,
  setColumnVisibility,
  setPagination,
  setSorting,
  sorting,
}) => {
  const { can } = usePageProps<App.Community.Data.UserGameListPageProps>();

  const gameListQuery = useGameListQuery({ columnFilters, pagination, sorting });

  const table = useReactTable({
    columns: useMemo(
      () =>
        buildColumnDefinitions({
          canSeeOpenTicketsColumn: can.develop ?? false,
        }),
      [can.develop],
    ),
    data: gameListQuery.data?.items ?? [],
    manualPagination: true,
    manualSorting: true,
    manualFiltering: true,
    rowCount: gameListQuery.data?.total,
    pageCount: gameListQuery.data?.lastPage,
    onColumnVisibilityChange: setColumnVisibility,
    onColumnFiltersChange: (updateOrValue) => {
      table.setPageIndex(0);

      setColumnFilters(updateOrValue);
    },
    onPaginationChange: (newPaginationValue) => {
      setPagination(newPaginationValue);
    },
    onSortingChange: (newSortingValue) => {
      table.setPageIndex(0);

      setSorting(newSortingValue);
    },
    getCoreRowModel: getCoreRowModel(),
    state: { columnFilters, columnVisibility, pagination, sorting },
  });

  const visibleColumnCount = table.getVisibleFlatColumns().length;

  return (
    <div className="flex flex-col gap-3">
      <WantToPlayGamesDataTableToolbar
        table={table}
        unfilteredTotal={gameListQuery.data?.unfilteredTotal ?? null}
      />

      <BaseTable
        containerClassName={cn(
          'overflow-auto rounded-md border border-neutral-700/80 bg-embed',
          'light:border-neutral-300 lg:overflow-visible lg:rounded-sm',

          // A sticky header cannot support this many columns. We have to drop stickiness.
          visibleColumnCount > 8 ? 'lg:!overflow-x-scroll' : '',
          visibleColumnCount > 10 ? 'xl:!overflow-x-scroll' : '',
        )}
      >
        <BaseTableHeader>
          {table.getHeaderGroups().map((headerGroup) => (
            <BaseTableRow
              key={headerGroup.id}
              className={cn(
                'do-not-highlight bg-embed lg:sticky lg:top-[41px] lg:z-10',

                // A sticky header cannot support this many columns. We have to drop stickiness.
                visibleColumnCount > 8 ? 'lg:!top-0' : '',
                visibleColumnCount > 10 ? 'xl:!top-0' : '',
              )}
            >
              {headerGroup.headers.map((header) => {
                return (
                  <BaseTableHead key={header.id}>
                    {header.isPlaceholder
                      ? null
                      : flexRender(header.column.columnDef.header, header.getContext())}
                  </BaseTableHead>
                );
              })}
            </BaseTableRow>
          ))}
        </BaseTableHeader>

        <BaseTableBody>
          {table.getRowModel().rows?.length ? (
            table.getRowModel().rows.map((row) => (
              <BaseTableRow key={row.id} data-state={row.getIsSelected() && 'selected'}>
                {row.getVisibleCells().map((cell) => (
                  <BaseTableCell
                    key={cell.id}
                    className={cn(
                      cell.column.columnDef.meta?.align === 'right' ? 'pr-6 text-right' : '',
                      cell.column.columnDef.meta?.align === 'center' ? 'text-center' : '',
                    )}
                  >
                    {flexRender(cell.column.columnDef.cell, cell.getContext())}
                  </BaseTableCell>
                ))}
              </BaseTableRow>
            ))
          ) : (
            <BaseTableRow>
              <BaseTableCell
                colSpan={table.getAllColumns().length}
                className="h-24 bg-embed text-center"
              >
                No results.
              </BaseTableCell>
            </BaseTableRow>
          )}
        </BaseTableBody>
      </BaseTable>

      <DataTablePagination table={table} />
    </div>
  );
};
