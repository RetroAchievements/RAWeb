import type {
  ColumnFiltersState,
  PaginationState,
  SortingState,
  Table,
  VisibilityState,
} from '@tanstack/react-table';
import { getCoreRowModel, useReactTable } from '@tanstack/react-table';
import { type Dispatch, type FC, lazy, type SetStateAction, Suspense } from 'react';

import { usePageProps } from '@/common/hooks/usePageProps';
import { useGameListPaginatedQuery } from '@/features/game-list/hooks/useGameListPaginatedQuery';

import { DataTablePagination } from '../DataTablePagination';
import { DataTableToolbar } from '../DataTableToolbar';
import { GameListDataTable } from '../GameListDataTable';
import { GameListItemsSuspenseFallback } from '../GameListItems/GameListItemsSuspenseFallback';
import { useHubGamesDefaultColumnState } from '../HubMainRoot/useHubGamesDefaultColumnState';
import { useColumnDefinitions } from './useColumnDefinitions';

const GameListItems = lazy(() => import('../GameListItems'));

// These values are all generated from `useGameListState`.
interface HubGamesDataTableProps {
  columnFilters: ColumnFiltersState;
  columnVisibility: VisibilityState;
  pagination: PaginationState;
  setColumnFilters: Dispatch<SetStateAction<ColumnFiltersState>>;
  setColumnVisibility: Dispatch<SetStateAction<VisibilityState>>;
  setPagination: Dispatch<SetStateAction<PaginationState>>;
  setSorting: Dispatch<SetStateAction<SortingState>>;
  sorting: SortingState;
}

export const HubGamesDataTable: FC<HubGamesDataTableProps> = ({
  columnFilters,
  columnVisibility,
  pagination,
  setColumnFilters,
  setColumnVisibility,
  setPagination,
  setSorting,
  sorting,
}) => {
  const { can, hub, ziggy } = usePageProps<App.Platform.Data.HubPageProps>();

  const { defaultColumnFilters } = useHubGamesDefaultColumnState();

  const gameListQuery = useGameListPaginatedQuery({
    columnFilters,
    pagination,
    sorting,
    apiRouteParams: { gameSet: hub.id },
    apiRouteName: 'api.hub.game.index',
    isEnabled: ziggy.device === 'desktop',
  });

  const table = useReactTable({
    columns: useColumnDefinitions({ canSeeOpenTicketsColumn: !!can.develop }),
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
        randomGameApiRouteName="api.hub.game.random"
        tableApiRouteName="api.hub.game.index"
        tableApiRouteParams={{ gameSet: hub.id }}
      />

      {ziggy.device === 'mobile' ? (
        <div className="mt-3">
          <Suspense fallback={<GameListItemsSuspenseFallback />}>
            <GameListItems
              columnFilters={columnFilters}
              pagination={pagination}
              sorting={sorting}
              apiRouteName="api.hub.game.index"
              apiRouteParams={{ gameSet: hub.id }}
            />
          </Suspense>
        </div>
      ) : null}

      {ziggy.device === 'desktop' ? (
        <div className="flex flex-col gap-3">
          <GameListDataTable table={table} />

          <DataTablePagination
            table={table as Table<unknown>}
            tableApiRouteName="api.hub.game.index"
            tableApiRouteParams={{ gameSet: hub.id }}
          />
        </div>
      ) : null}
    </div>
  );
};
