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

export function useGameListState<TData = unknown>(paginatedGames: App.Data.PaginatedData<TData>) {
  const {
    ziggy: { query },
  } = usePageProps<App.Community.Data.UserGameListPageProps>();

  const [pagination, setPagination] = useState<PaginationState>(
    mapPaginatedGamesToPaginationState(paginatedGames),
  );

  const [sorting, setSorting] = useState<SortingState>(mapQueryParamsToSorting(query));

  const [columnVisibility, setColumnVisibility] = useState<VisibilityState>({
    lastUpdated: false,
    playersTotal: false,
    numVisibleLeaderboards: false,
    numUnresolvedTickets: false,
  });

  const [columnFilters, setColumnFilters] = useState<ColumnFiltersState>(
    mapQueryParamsToColumnFilters(query),
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
  if (typeof query.sort === 'function') {
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
  } else {
    // TODO do we ever land in here..?
  }

  return sorting;
}

function mapQueryParamsToColumnFilters(
  query: AppGlobalProps['ziggy']['query'],
): ColumnFiltersState {
  const columnFilters: ColumnFiltersState = [];

  if (!query.filter) {
    return columnFilters;
  }

  for (const [filterKey, filterValue] of Object.entries(query.filter)) {
    columnFilters.push({
      id: filterKey,
      value: filterValue.split(','),
    });
  }

  return columnFilters;
}
