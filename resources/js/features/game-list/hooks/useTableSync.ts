import type {
  ColumnFiltersState,
  ColumnSort,
  PaginationState,
  SortingState,
  TableState,
  VisibilityState,
} from '@tanstack/react-table';
import { useCookie, useUpdateEffect } from 'react-use';

import { usePageProps } from '@/common/hooks/usePageProps';

interface UseAutoUpdatingQueryParamsProps {
  columnFilters: ColumnFiltersState;
  columnVisibility: VisibilityState;
  defaultColumnFilters: ColumnFiltersState;
  pagination: PaginationState;
  sorting: SortingState;

  defaultColumnSort?: ColumnSort;
  defaultPageSize?: number;
  isUserPersistenceEnabled?: boolean;
}

/**
 * This hook is designed to keep the URL query params and
 * user's persistence cookie in sync with the table state.
 */
export function useTableSync({
  columnFilters,
  columnVisibility,
  pagination,
  sorting,
  defaultColumnSort = { id: 'title', desc: false },
  defaultColumnFilters = [],
  defaultPageSize = 25,
  isUserPersistenceEnabled = false,
}: UseAutoUpdatingQueryParamsProps) {
  const { persistenceCookieName } = usePageProps<{ persistenceCookieName: string }>();

  const [cookie, setCookie, deleteCookie] = useCookie(persistenceCookieName);

  useUpdateEffect(() => {
    if (isUserPersistenceEnabled) {
      // Don't persist filtering by title.
      const persistedFilters = columnFilters.filter((filter) => filter.id !== 'title');

      const tableState: Partial<TableState> = {
        columnVisibility,
        sorting,
        columnFilters: persistedFilters,
        pagination: { ...pagination, pageIndex: 0 }, // don't persist the page index
      };

      setCookie(JSON.stringify(tableState), { expires: 180 }); // 180 day (6 month) expiry
    } else if (cookie) {
      // Clean up the cookie if persistence is not enabled.
      deleteCookie();
    }
  }, [isUserPersistenceEnabled, columnFilters, columnVisibility, pagination, sorting]);

  useUpdateEffect(() => {
    const searchParams = new URLSearchParams(window.location.search);

    // Update individual components of the query params.
    updatePagination(searchParams, pagination, defaultPageSize);
    updateFilters(searchParams, columnFilters, defaultColumnFilters);
    updateSorting(searchParams, sorting, defaultColumnSort);

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

function updateSorting(
  searchParams: URLSearchParams,
  sorting: SortingState,
  defaultColumnSort: ColumnSort,
): void {
  // We only support a single active sort. The table is always sorted,
  // so it's fine to assume index 0 (activeSort) is always present.
  const [activeSort] = sorting;

  if (activeSort) {
    if (activeSort.id === defaultColumnSort.id && activeSort.desc === defaultColumnSort.desc) {
      searchParams.delete('sort');
    } else {
      searchParams.set('sort', `${activeSort.desc ? '-' : ''}${activeSort.id}`);
    }
  }
}

function updateFilters(
  searchParams: URLSearchParams,
  columnFilters: ColumnFiltersState,
  defaultFilters: ColumnFiltersState,
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
