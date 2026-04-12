import { dehydrate } from '@tanstack/react-query';
import { useAtomValue } from 'jotai';
import type { RouteName } from 'ziggy-js';

import type { DefaultColumnState } from '@/features/game-list/models';

import { isCurrentlyPersistingViewAtom } from '../state/game-list.atoms';
import { useGameListState } from './useGameListState';
import { usePreloadedTableDataQueryClient } from './usePreloadedTableDataQueryClient';
import { useTableSync } from './useTableSync';

interface Options extends DefaultColumnState {
  paginatedGameListEntries: App.Data.PaginatedData<App.Platform.Data.GameListEntry>;
  defaultPageSize: number;
  apiRouteName: RouteName;
  apiRouteParams?: Record<string, unknown>;
}

export function useGameListTableRoot({
  paginatedGameListEntries,
  defaultColumnFilters,
  defaultColumnSort,
  defaultColumnVisibility,
  defaultPageSize,
  apiRouteName,
  apiRouteParams = {},
}: Options) {
  const {
    columnFilters,
    columnVisibility,
    pagination,
    setColumnFilters,
    setColumnVisibility,
    setPagination,
    setSorting,
    sorting,
  } = useGameListState(paginatedGameListEntries, {
    defaultColumnSort,
    defaultColumnFilters,
    defaultColumnVisibility,
  });

  const { queryClientWithInitialData } = usePreloadedTableDataQueryClient({
    columnFilters,
    pagination,
    sorting,
    apiRouteName,
    apiRouteParams,
    paginatedData: paginatedGameListEntries,
  });

  const isCurrentlyPersistingView = useAtomValue(isCurrentlyPersistingViewAtom);

  useTableSync({
    columnFilters,
    columnVisibility,
    defaultColumnFilters,
    defaultColumnSort,
    pagination,
    sorting,
    defaultPageSize,
    isUserPersistenceEnabled: isCurrentlyPersistingView,
  });

  return {
    hydrationState: dehydrate(queryClientWithInitialData),
    gameListTableProps: {
      apiRouteName,
      apiRouteParams,
      columnFilters,
      columnVisibility,
      pagination,
      sorting,
      setColumnFilters,
      setColumnVisibility,
      setPagination,
      setSorting,
      defaultColumnFilters,
      defaultColumnSort,
    },
  };
}
