import type {
  ColumnFiltersState,
  PaginationState,
  SortingState,
  VisibilityState,
} from '@tanstack/react-table';
import { useState } from 'react';

import { usePageProps } from '@/common/hooks/usePageProps';
import type { AppGlobalProps } from '@/common/models';

/**
 * 🔴 You should only use this hook once in the entire component tree.
 *    It is a factory. The state is not global.
 *    Every invocation will create entirely new state values.
 */

export function useGameListState<TData = unknown>(
  paginatedGames: App.Data.PaginatedData<TData>,
  options: {
    /**
     * Should be set to truthy if the user is authenticated.
     * If the user is not authenticated, the player count column will
     * be shown instead.
     */
    canShowProgressColumn: boolean;

    alwaysShowPlayersTotal?: boolean;
    defaultColumnFilters?: ColumnFiltersState;
  },
) {
  const {
    ziggy: { query },
  } = usePageProps<App.Community.Data.UserGameListPageProps>();

  const [pagination, setPagination] = useState<PaginationState>(
    mapPaginatedGamesToPaginationState(paginatedGames),
  );

  const [sorting, setSorting] = useState<SortingState>(mapQueryParamsToSorting(query));

  const [columnVisibility, setColumnVisibility] = useState<VisibilityState>({
    hasActiveOrInReviewClaims: false,
    lastUpdated: false,
    numUnresolvedTickets: false,
    numVisibleLeaderboards: false,
    playersTotal: options?.alwaysShowPlayersTotal ?? !options.canShowProgressColumn,
    progress: options.canShowProgressColumn,
  });

  const [columnFilters, setColumnFilters] = useState<ColumnFiltersState>(
    mapQueryParamsToColumnFilters(query, options?.defaultColumnFilters),
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

function mapPaginatedGamesToPaginationState<TData = unknown>(
  paginatedGames: App.Data.PaginatedData<TData>,
): PaginationState {
  // tanstack-table uses 0-indexed page numbers.
  const targetPageIndex = paginatedGames.currentPage - 1;

  return { pageIndex: targetPageIndex, pageSize: paginatedGames.perPage };
}

function mapQueryParamsToSorting(query: AppGlobalProps['ziggy']['query']): SortingState {
  const sorting: SortingState = [];

  // `sort` is actually part of `query`'s prototype, so we have to be
  // extra explicit in how we check for the presence of the param.
  if (typeof query.sort === 'function' || typeof query.sort === 'undefined') {
    sorting.push({ id: 'title', desc: false });

    return sorting;
  }

  // If it's an array, we must have a sort query param. Process it.
  const sortValue = query.sort;

  if (typeof sortValue === 'string') {
    if (sortValue[0] === '-') {
      const split = sortValue.split('-');
      sorting.push({ id: split[1], desc: true });
    } else {
      sorting.push({ id: sortValue, desc: false });
    }
  }

  return sorting;
}

function mapQueryParamsToColumnFilters(
  query: AppGlobalProps['ziggy']['query'],
  defaultColumnFilters?: ColumnFiltersState,
): ColumnFiltersState {
  const columnFilters: ColumnFiltersState = [];

  for (const [filterKey, filterValue] of Object.entries(query.filter ?? {})) {
    columnFilters.push({
      id: filterKey,
      value: filterValue.split(','),
    });
  }

  // Set any default (implicitly-enabled) filter values as necessary.
  if (defaultColumnFilters) {
    for (const defaultColumnFilter of defaultColumnFilters) {
      const existingFilter = columnFilters.some((f) => f.id === defaultColumnFilter.id);
      if (!existingFilter) {
        columnFilters.push(defaultColumnFilter);
      }
    }
  }

  return columnFilters;
}
