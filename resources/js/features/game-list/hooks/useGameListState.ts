import type {
  ColumnFiltersState,
  PaginationState,
  SortingState,
  TableState,
  VisibilityState,
} from '@tanstack/react-table';
import { useState } from 'react';

import { usePageProps } from '@/common/hooks/usePageProps';
import type { DefaultColumnState, GameListViewState } from '@/features/game-list/models';
import { resolveInitialGameListViewState } from '@/features/game-list/utils/resolveInitialGameListViewState';

/**
 * 🔴 You should only use this hook once in the entire component tree.
 *    It is a factory. The state is not global.
 *    Every invocation will create entirely new state values.
 */

export function useGameListState<TData = unknown>(
  paginatedGames: App.Data.PaginatedData<TData>,
  options: Partial<DefaultColumnState>,
) {
  const {
    persistedViewPreferences,
    ziggy: { query },
  } = usePageProps<{ persistedViewPreferences: Partial<TableState> | null }>();

  const [initialViewState] = useState<GameListViewState>(() =>
    resolveInitialGameListViewState({
      paginatedData: paginatedGames,
      query,
      persistedViewPreferences,
      defaultColumnFilters: options.defaultColumnFilters,
      defaultColumnSort: options.defaultColumnSort,
      defaultColumnVisibility: options.defaultColumnVisibility,
    }),
  );

  const [pagination, setPagination] = useState<PaginationState>(initialViewState.pagination);
  const [sorting, setSorting] = useState<SortingState>(initialViewState.sorting);
  const [columnVisibility, setColumnVisibility] = useState<VisibilityState>(
    initialViewState.columnVisibility,
  );
  const [columnFilters, setColumnFilters] = useState<ColumnFiltersState>(
    initialViewState.columnFilters,
  );

  return {
    columnFilters,
    columnVisibility,
    pagination,
    setColumnFilters,
    setColumnVisibility,
    setPagination,
    setSorting,
    sorting,
  };
}
