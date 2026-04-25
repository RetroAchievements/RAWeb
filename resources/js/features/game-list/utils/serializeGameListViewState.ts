import type {
  ColumnFiltersState,
  ColumnSort,
  PaginationState,
  SortingState,
} from '@tanstack/react-table';

import { buildGameListQuerySortParam } from './buildGameListQuerySortParam';
import { getIsDefaultSorting } from './getIsDefaultSorting';

interface SerializeGameListViewStateOptions {
  columnFilters: ColumnFiltersState;
  pagination: PaginationState;
  sorting: SortingState;

  currentSearch?: string;
  defaultColumnFilters?: ColumnFiltersState;
  defaultColumnSort?: ColumnSort;
  defaultPageSize?: number;
}

const DEFAULT_COLUMN_SORT: ColumnSort = { id: 'title', desc: false };

export function serializeGameListViewState({
  currentSearch = '',
  columnFilters,
  pagination,
  sorting,
  defaultColumnFilters = [],
  defaultColumnSort = DEFAULT_COLUMN_SORT,
  defaultPageSize = 25,
}: SerializeGameListViewStateOptions): URLSearchParams {
  const searchParams = new URLSearchParams(currentSearch);

  updatePagination(searchParams, pagination, defaultPageSize);
  updateFilters(searchParams, columnFilters, defaultColumnFilters);
  updateSorting(searchParams, sorting, defaultColumnSort);

  return searchParams;
}

function updatePagination(
  searchParams: URLSearchParams,
  pagination: PaginationState,
  defaultPageSize: number,
): void {
  if (pagination.pageIndex > 0) {
    searchParams.set('page[number]', String(pagination.pageIndex + 1));
  } else {
    searchParams.delete('page[number]');
  }

  if (pagination.pageSize !== defaultPageSize) {
    searchParams.set('page[size]', String(pagination.pageSize));
  } else {
    searchParams.delete('page[size]');
  }
}

function updateSorting(
  searchParams: URLSearchParams,
  sorting: SerializeGameListViewStateOptions['sorting'],
  defaultColumnSort: ColumnSort,
): void {
  const [activeSort] = sorting;

  if (!activeSort) {
    searchParams.delete('sort');
    return;
  }

  if (getIsDefaultSorting(sorting, defaultColumnSort)) {
    searchParams.delete('sort');
  } else {
    searchParams.set('sort', buildGameListQuerySortParam(sorting)!);
  }
}

function updateFilters(
  searchParams: URLSearchParams,
  columnFilters: ColumnFiltersState,
  defaultColumnFilters: ColumnFiltersState,
): void {
  const activeFilterIds = new Set(columnFilters.map((filter) => `filter[${filter.id}]`));
  const defaultFilterMap = new Map(defaultColumnFilters.map((filter) => [filter.id, filter.value]));

  for (const columnFilter of columnFilters) {
    const filterKey = `filter[${columnFilter.id}]`;
    const defaultValue = defaultFilterMap.get(columnFilter.id);

    if (defaultValue !== undefined && areFilterValuesEqual(columnFilter.value, defaultValue)) {
      searchParams.delete(filterKey);
      continue;
    }

    if (Array.isArray(columnFilter.value) && columnFilter.value.length > 0) {
      searchParams.set(filterKey, columnFilter.value.join(','));
    } else if (columnFilter.value) {
      searchParams.set(filterKey, String(columnFilter.value));
    } else {
      searchParams.delete(filterKey);
    }
  }

  for (const paramKey of searchParams.keys()) {
    if (paramKey.startsWith('filter[') && !activeFilterIds.has(paramKey)) {
      searchParams.delete(paramKey);
    }
  }
}

function areFilterValuesEqual(a: unknown, b: unknown): boolean {
  if (Array.isArray(a) && Array.isArray(b)) {
    if (a.length !== b.length) {
      return false;
    }

    return a.every((value, index) => value === b[index]);
  }

  return a === b;
}
