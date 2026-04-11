import type {
  ColumnFiltersState,
  ColumnSort,
  PaginationState,
  SortingState,
  TableState,
} from '@tanstack/react-table';

import type { AppGlobalProps } from '@/common/models';

import type { DefaultColumnState } from '../models';
import type { GameListViewState } from '../models/game-list-view-state.model';

interface ResolveInitialGameListViewStateOptions<
  TData = unknown,
> extends Partial<DefaultColumnState> {
  paginatedData: App.Data.PaginatedData<TData>;
  query: AppGlobalProps['ziggy']['query'];
  persistedViewPreferences: Partial<TableState> | null;
}

const DEFAULT_COLUMN_SORT: ColumnSort = { id: 'title', desc: false };

export function resolveInitialGameListViewState<TData = unknown>({
  paginatedData,
  query,
  persistedViewPreferences,
  defaultColumnFilters = [],
  defaultColumnSort = DEFAULT_COLUMN_SORT,
  defaultColumnVisibility = {},
}: ResolveInitialGameListViewStateOptions<TData>): GameListViewState {
  return {
    columnFilters: resolveInitialColumnFilters(
      query,
      persistedViewPreferences,
      defaultColumnFilters,
    ),
    columnVisibility: {
      ...defaultColumnVisibility,
      ...(persistedViewPreferences?.columnVisibility ?? null),
    },
    pagination: resolveInitialPagination(paginatedData, persistedViewPreferences),
    sorting: resolveInitialSorting(query, persistedViewPreferences, defaultColumnSort),
  };
}

function resolveInitialColumnFilters(
  query: AppGlobalProps['ziggy']['query'],
  persistedViewPreferences: Partial<TableState> | null,
  defaultColumnFilters: ColumnFiltersState,
): ColumnFiltersState {
  if (hasFilterQuery(query)) {
    return mapQueryParamsToColumnFilters(query.filter, defaultColumnFilters);
  }

  if (persistedViewPreferences?.columnFilters) {
    return persistedViewPreferences.columnFilters;
  }

  return defaultColumnFilters;
}

function resolveInitialPagination<TData = unknown>(
  paginatedData: App.Data.PaginatedData<TData>,
  persistedViewPreferences: Partial<TableState> | null,
): PaginationState {
  if (persistedViewPreferences?.pagination) {
    return {
      pageIndex: paginatedData.currentPage - 1,
      pageSize: persistedViewPreferences.pagination.pageSize,
    };
  }

  return {
    pageIndex: paginatedData.currentPage - 1,
    pageSize: paginatedData.perPage,
  };
}

function resolveInitialSorting(
  query: AppGlobalProps['ziggy']['query'],
  persistedViewPreferences: Partial<TableState> | null,
  defaultColumnSort: ColumnSort,
): SortingState {
  if (hasSortQuery(query)) {
    return mapQueryParamsToSorting(query.sort);
  }

  if (persistedViewPreferences?.sorting) {
    return persistedViewPreferences.sorting;
  }

  return [defaultColumnSort];
}

function hasFilterQuery(
  query: AppGlobalProps['ziggy']['query'],
): query is AppGlobalProps['ziggy']['query'] & { filter: Record<string, string> } {
  return !!query.filter && typeof query.filter !== 'function' && isStringRecord(query.filter);
}

function hasSortQuery(
  query: AppGlobalProps['ziggy']['query'],
): query is AppGlobalProps['ziggy']['query'] & { sort: unknown } {
  return !!query.sort && typeof query.sort !== 'function';
}

function isStringRecord(value: unknown): value is Record<string, string> {
  if (!value || typeof value !== 'object' || Array.isArray(value)) {
    return false;
  }

  return Object.values(value).every((entry) => typeof entry === 'string');
}

function mapQueryParamsToSorting(sortValue: unknown): SortingState {
  if (typeof sortValue !== 'string') {
    return [];
  }

  if (sortValue.startsWith('-')) {
    return [{ id: sortValue.slice(1), desc: true }];
  }

  return [{ id: sortValue, desc: false }];
}

function mapQueryParamsToColumnFilters(
  filterQuery: Record<string, string>,
  defaultColumnFilters: ColumnFiltersState,
): ColumnFiltersState {
  const columnFilters: ColumnFiltersState = Object.entries(filterQuery).map(
    ([filterKey, filterValue]) => ({
      id: filterKey,
      value: filterValue.split(','),
    }),
  );

  for (const defaultColumnFilter of defaultColumnFilters) {
    const existingFilter = columnFilters.some(
      (columnFilter) => columnFilter.id === defaultColumnFilter.id,
    );

    if (!existingFilter) {
      columnFilters.push(defaultColumnFilter);
    }
  }

  return columnFilters;
}
