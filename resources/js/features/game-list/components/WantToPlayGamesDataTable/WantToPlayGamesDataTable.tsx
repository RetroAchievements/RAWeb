import type {
  ColumnFiltersState,
  PaginationState,
  SortingState,
  VisibilityState,
} from '@tanstack/react-table';
import { getCoreRowModel, useReactTable } from '@tanstack/react-table';
import { type Dispatch, type FC, type SetStateAction, useMemo } from 'react';

import { useGameListQuery } from '@/common/hooks/useGameListQuery';
import { usePageProps } from '@/common/hooks/usePageProps';

import { wantToPlayGamesDefaultFilters } from '../../utils/wantToPlayGamesDefaultFilters';
import { DataTablePagination } from '../DataTablePagination';
import { GameListDataTable } from '../GameListDataTable';
import { buildColumnDefinitions } from './buildColumnDefinitions';
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

  const gameListQuery = useGameListQuery({
    columnFilters,
    pagination,
    sorting,
    apiRouteName: 'api.user-game-list.index',
  });

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

  return (
    <div className="flex flex-col gap-3">
      <WantToPlayGamesDataTableToolbar
        table={table}
        unfilteredTotal={gameListQuery.data?.unfilteredTotal ?? null}
        defaultColumnFilters={wantToPlayGamesDefaultFilters}
      />

      <GameListDataTable table={table} />

      <DataTablePagination table={table} tableApiRouteName="api.user-game-list.index" />
    </div>
  );
};
