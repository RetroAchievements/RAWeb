import type {
  ColumnDef,
  ColumnFiltersState,
  ColumnSort,
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
  defaultChipOfInterest?: App.Platform.Enums.GameListSortField;
  defaultColumnFilters: ColumnFiltersState;
  defaultColumnSort: ColumnSort;
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
  defaultChipOfInterest = 'achievementsPublished',
  defaultColumnFilters,
  defaultColumnSort,
  columnDefinitions,

  // API configuration
  apiRouteName = 'api.game.index', // All Games
  apiRouteParams = {},
  randomGameApiRouteName,
  shouldHideItemIfNotInBacklog = false,
}) => {
  'use no memo';

  const { ziggy } = usePageProps();

  const gameListQuery = useGameListPaginatedQuery({
    columnFilters,
    pagination,
    sorting,
    apiRouteName,
    apiRouteParams,
    isEnabled: ziggy.device === 'desktop',
  });

  // eslint-disable-next-line react-hooks/incompatible-library -- https://github.com/TanStack/table/issues/5567
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
        defaultColumnFilters={defaultColumnFilters}
        isTableQueryLoading={gameListQuery.isLoading || gameListQuery.isFetching}
        randomGameApiRouteName={randomGameApiRouteName}
        table={table}
        tableApiRouteName={apiRouteName}
        tableApiRouteParams={apiRouteParams}
        unfilteredTotal={gameListQuery.data?.unfilteredTotal ?? null}
      />

      {ziggy.device === 'mobile' ? (
        <div className="mt-3">
          <Suspense fallback={<GameListItemsSuspenseFallback />}>
            <GameListItems
              apiRouteName={apiRouteName}
              apiRouteParams={apiRouteParams}
              columnFilters={columnFilters}
              defaultChipOfInterest={defaultChipOfInterest}
              defaultColumnSort={defaultColumnSort}
              pagination={pagination}
              shouldHideItemIfNotInBacklog={shouldHideItemIfNotInBacklog}
              sorting={sorting}
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
