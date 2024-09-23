import type { ColumnFiltersState, PaginationState, SortingState } from '@tanstack/react-table';
import { useUpdateEffect } from 'react-use';

interface UseAutoUpdatingQueryParamsProps {
  pagination: PaginationState;
  columnFilters: ColumnFiltersState;
  sorting: SortingState;
}

/**
 * This hook is designed to keep the URL query params in sync with the table state.
 */
export function useAutoUpdatingQueryParams({
  columnFilters,
  pagination,
  sorting,
}: UseAutoUpdatingQueryParamsProps) {
  useUpdateEffect(() => {
    const searchParams = new URLSearchParams(window.location.search);

    // Update individual components of the query params.
    updatePagination(searchParams, pagination);
    updateFilters(searchParams, columnFilters);
    updateSorting(searchParams, sorting);

    // `searchParams.size` is not supported in all envs, especially Node.js (Vitest).
    const searchParamsSize = Array.from(searchParams).length;

    const newUrl = searchParamsSize
      ? `${window.location.pathname}?${searchParams.toString()}`
      : window.location.pathname;

    window.history.replaceState(null, '', newUrl);
  }, [pagination, sorting, columnFilters]);
}

function updatePagination(searchParams: URLSearchParams, pagination: PaginationState): void {
  if (pagination.pageIndex > 0) {
    searchParams.set('page[number]', String(pagination.pageIndex + 1));
  } else {
    searchParams.delete('page[number]');
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

function updateFilters(searchParams: URLSearchParams, columnFilters: ColumnFiltersState) {
  const activeFilterIds = new Set(columnFilters.map((filter) => `filter[${filter.id}]`));

  for (const columnFilter of columnFilters) {
    const filterKey = `filter[${columnFilter.id}]`;

    // "filter[achievementsPublished]=has" is an implicitly-set default filter value.
    // Treat it as the default value and be sure to remove the query param.
    if (
      columnFilter.id === 'achievementsPublished' &&
      Array.isArray(columnFilter.value) &&
      columnFilter.value[0] === 'has'
    ) {
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
