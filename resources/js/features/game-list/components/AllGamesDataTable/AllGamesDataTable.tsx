import type {
  ColumnFiltersState,
  PaginationState,
  SortingState,
  VisibilityState,
} from '@tanstack/react-table';
import { getCoreRowModel, useReactTable } from '@tanstack/react-table';
import { type Dispatch, type FC, type SetStateAction } from 'react';

import { useGameListQuery } from '@/common/hooks/useGameListQuery';
import { usePageProps } from '@/common/hooks/usePageProps';

import { allGamesDefaultFilters } from '../../utils/allGamesDefaultFilters';
import { DataTablePagination } from '../DataTablePagination';
import { DataTableToolbar } from '../DataTableToolbar';
import { GameListDataTable } from '../GameListDataTable';
import { useColumnDefinitions } from './useColumnDefinitions';

// These values are all generated from `useGameListState`.
interface AllGamesDataTableProps {
  columnFilters: ColumnFiltersState;
  columnVisibility: VisibilityState;
  pagination: PaginationState;
  setColumnFilters: Dispatch<SetStateAction<ColumnFiltersState>>;
  setColumnVisibility: Dispatch<SetStateAction<VisibilityState>>;
  setPagination: Dispatch<SetStateAction<PaginationState>>;
  setSorting: Dispatch<SetStateAction<SortingState>>;
  sorting: SortingState;
}

export const AllGamesDataTable: FC<AllGamesDataTableProps> = ({
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
    columns: useColumnDefinitions({ canSeeOpenTicketsColumn: can.develop ?? false }),
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

  return (
    <div className="flex flex-col gap-3">
      <DataTableToolbar
        table={table}
        unfilteredTotal={gameListQuery.data?.unfilteredTotal ?? null}
        defaultColumnFilters={allGamesDefaultFilters}
      />

      <GameListDataTable table={table} />

      <DataTablePagination table={table} />
    </div>
  );
};
