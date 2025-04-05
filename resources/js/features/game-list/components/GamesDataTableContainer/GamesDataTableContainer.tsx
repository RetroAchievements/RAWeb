import type {
  ColumnDef,
  ColumnFiltersState,
  PaginationState,
  SortingState,
  Table,
  VisibilityState,
} from '@tanstack/react-table';
import { getCoreRowModel, useReactTable } from '@tanstack/react-table';
import { type Dispatch, type FC, lazy, type SetStateAction, Suspense } from 'react';
import type { RouteName } from 'ziggy-js';

import { usePageProps } from '@/common/hooks/usePageProps';
import { useGameListPaginatedQuery } from '@/features/game-list/hooks/useGameListPaginatedQuery';

import { GameListItemsSuspenseFallback } from '../GameListItems/GameListItemsSuspenseFallback';
import { DataTablePagination } from './DataTablePagination';
import { DataTableToolbar } from './DataTableToolbar';
import { GameListDataTable } from './GameListDataTable';

const GameListItems = lazy(() => import('../GameListItems'));

interface GamesDataTableContainerProps {
  // Table state
  columnFilters: ColumnFiltersState;
  columnVisibility: VisibilityState;
  pagination: PaginationState;
  sorting: SortingState;

  // State setters
  setColumnFilters: Dispatch<SetStateAction<ColumnFiltersState>>;
  setColumnVisibility: Dispatch<SetStateAction<VisibilityState>>;
  setPagination: Dispatch<SetStateAction<PaginationState>>;
  setSorting: Dispatch<SetStateAction<SortingState>>;

  // Table configuration
  defaultColumnFilters: ColumnFiltersState;
  columnDefinitions: ColumnDef<App.Platform.Data.GameListEntry>[];

  // API configuration
  apiRouteName?: RouteName;
  apiRouteParams?: Record<string, unknown>;
  randomGameApiRouteName?: RouteName;
  shouldHideItemIfNotInBacklog?: boolean;
}

export const GamesDataTableContainer: FC<GamesDataTableContainerProps> = ({
  // Table state
  columnFilters,
  columnVisibility,
  pagination,
  sorting,

  // State setters
  setColumnFilters,
  setColumnVisibility,
  setPagination,
  setSorting,

  // Table configuration
  defaultColumnFilters,
  columnDefinitions,

  // API configuration
  apiRouteName = 'api.game.index', // All Games
  apiRouteParams = {},
  randomGameApiRouteName,
  shouldHideItemIfNotInBacklog = false,
}) => {
  const { ziggy } = usePageProps();

  const gameListQuery = useGameListPaginatedQuery({
    columnFilters,
    pagination,
    sorting,
    apiRouteName,
    apiRouteParams,
    isEnabled: ziggy.device === 'desktop',
  });

  const table = useReactTable({
    columns: columnDefinitions,
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
        defaultColumnFilters={defaultColumnFilters}
        randomGameApiRouteName={randomGameApiRouteName}
        tableApiRouteName={apiRouteName}
        tableApiRouteParams={apiRouteParams}
      />

      {ziggy.device === 'mobile' ? (
        <div className="mt-3">
          <Suspense fallback={<GameListItemsSuspenseFallback />}>
            <GameListItems
              columnFilters={columnFilters}
              pagination={pagination}
              sorting={sorting}
              apiRouteName={apiRouteName}
              apiRouteParams={apiRouteParams}
              shouldHideItemIfNotInBacklog={shouldHideItemIfNotInBacklog}
            />
          </Suspense>
        </div>
      ) : null}

      {ziggy.device === 'desktop' ? (
        <div className="flex flex-col gap-3">
          <GameListDataTable
            table={table}
            isLoading={gameListQuery.isLoading || gameListQuery.isFetching}
          />

          <DataTablePagination
            table={table as Table<unknown>}
            tableApiRouteName={apiRouteName}
            tableApiRouteParams={apiRouteParams}
          />
        </div>
      ) : null}
    </div>
  );
};
