import type { ColumnFiltersState, PaginationState, SortingState } from '@tanstack/react-table';
import { useUpdateEffect } from 'react-use';

interface UseAutoUpdatingQueryParamsProps {
  columnFilters: ColumnFiltersState;
  defaultFilters: ColumnFiltersState;
  pagination: PaginationState;
  sorting: SortingState;

  defaultPageSize?: number;
}

/**
 * This hook is designed to keep the URL query params in sync with the table state.
 */
export function useAutoUpdatingQueryParams({
  columnFilters,
  pagination,
  sorting,
  defaultFilters = [],
  defaultPageSize = 25,
}: UseAutoUpdatingQueryParamsProps) {
  useUpdateEffect(() => {
    const searchParams = new URLSearchParams(window.location.search);

    // Update individual components of the query params.
    updatePagination(searchParams, pagination, defaultPageSize);
    updateFilters(searchParams, columnFilters, defaultFilters);
    updateSorting(searchParams, sorting);

    // `searchParams.size` is not supported in all envs, especially Node.js (Vitest).
    const searchParamsSize = Array.from(searchParams).length;

    const newUrl = searchParamsSize
      ? `${window.location.pathname}?${searchParams.toString()}`
      : window.location.pathname;

    window.history.replaceState(null, '', newUrl);
  }, [pagination, sorting, columnFilters]);
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

function updateSorting(searchParams: URLSearchParams, sorting: SortingState): void {
  // We only support a single active sort. The table is always sorted,
  // so it's fine to assume index 0 (activeSort) is always present.
  const [activeSort] = sorting;

  if (activeSort) {
    if (activeSort.id === 'title' && !activeSort.desc) {
      searchParams.delete('sort');
    } else {
      searchParams.set('sort', `${activeSort.desc ? '-' : ''}${activeSort.id}`);
    }
  }
}

function updateFilters(
  searchParams: URLSearchParams,
  columnFilters: ColumnFiltersState,
  defaultFilters: ColumnFiltersState = [],
): void {
  const activeFilterIds = new Set(columnFilters.map((filter) => `filter[${filter.id}]`));
  const defaultFilterMap = new Map(defaultFilters.map((filter) => [filter.id, filter.value]));

  for (const columnFilter of columnFilters) {
    const filterKey = `filter[${columnFilter.id}]`;
    const defaultValue = defaultFilterMap.get(columnFilter.id);

    // Skip if the current filter value matches the default.
    if (defaultValue !== undefined && areFilterValuesEqual(columnFilter.value, defaultValue)) {
      searchParams.delete(filterKey);
      continue;
    }

    if (Array.isArray(columnFilter.value) && columnFilter.value.length > 0) {
      searchParams.set(filterKey, columnFilter.value.join(','));
    } else if (columnFilter.value) {
      searchParams.set(filterKey, columnFilter.value as string);
    } else {
      searchParams.delete(filterKey);
    }
  }

  // Remove any filters that are no longer active.
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
