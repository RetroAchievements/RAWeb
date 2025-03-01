import type {
  ColumnFiltersState,
  ColumnSort,
  PaginationState,
  SortingState,
  TableState,
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
    defaultColumnFilters?: ColumnFiltersState;
    defaultColumnSort?: ColumnSort;
    defaultColumnVisibility?: Partial<Record<App.Platform.Enums.GameListSortField, boolean>>;
  },
) {
  const {
    persistedViewPreferences,
    ziggy: { query },
  } = usePageProps<{ persistedViewPreferences: Partial<TableState> | null }>();

  const [pagination, setPagination] = useState<PaginationState>(
    generateInitialPaginationState(paginatedGames, persistedViewPreferences),
  );

  const [sorting, setSorting] = useState<SortingState>(
    generateInitialSortingState(query, persistedViewPreferences, options?.defaultColumnSort),
  );

  const [columnVisibility, setColumnVisibility] = useState<VisibilityState>({
    ...options.defaultColumnVisibility,
    ...(persistedViewPreferences?.columnVisibility ?? null),
  });

  const [columnFilters, setColumnFilters] = useState<ColumnFiltersState>(
    generateInitialColumnFilters(query, persistedViewPreferences, options?.defaultColumnFilters),
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

function generateInitialColumnFilters(
  query: AppGlobalProps['ziggy']['query'],
  persistedViewPreferences: Partial<TableState> | null,
  defaultColumnFilters?: ColumnFiltersState,
): ColumnFiltersState {
  if (query.filter && typeof query.filter !== 'function') {
    return mapQueryParamsToColumnFilters(query, defaultColumnFilters);
  }

  if (persistedViewPreferences?.columnFilters) {
    return persistedViewPreferences.columnFilters;
  }

  return defaultColumnFilters ?? [];
}

function generateInitialPaginationState<TData = unknown>(
  paginatedGames: App.Data.PaginatedData<TData>,
  persistedViewPreferences: Partial<TableState> | null,
): PaginationState {
  if (persistedViewPreferences?.pagination) {
    return {
      // The page index is persisted to keep types fully in sync between
      // the cookie store and the data table library. However, we'll always
      // use whatever the server says the page should be.
      pageIndex: paginatedGames.currentPage - 1,

      pageSize: persistedViewPreferences.pagination.pageSize,
    };
  }

  return mapPaginatedGamesToPaginationState(paginatedGames);
}

function generateInitialSortingState(
  query: AppGlobalProps['ziggy']['query'],
  persistedViewPreferences: Partial<TableState> | null,
  defaultColumnSort?: ColumnSort,
): SortingState {
  // `sort` is actually part of `query`'s prototype, so we have to be
  // extra explicit in how we check for the presence of the param.
  if (query.sort && typeof query.sort !== 'function') {
    return mapQueryParamsToSorting(query);
  }

  if (persistedViewPreferences?.sorting) {
    return persistedViewPreferences.sorting;
  }

  return defaultColumnSort ? [defaultColumnSort] : [{ id: 'title', desc: false }];
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

  for (const [filterKey, filterValue] of Object.entries(query.filter)) {
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
