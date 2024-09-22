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

    if (pagination.pageIndex > 0) {
      searchParams.set('page[number]', String(pagination.pageIndex + 1));
    } else {
      searchParams.delete('page[number]');
    }

    if (columnFilters.length > 0) {
      for (const columnFilter of columnFilters) {
        if (Array.isArray(columnFilter.value)) {
          searchParams.set(`filter[${columnFilter.id}]`, columnFilter.value.join(','));
        } else {
          searchParams.set(`filter[${columnFilter.id}]`, columnFilter.value as string);
        }
      }
    } else {
      for (const paramKey of searchParams.keys()) {
        if (paramKey.includes('filter[')) {
          searchParams.delete(paramKey);
        }
      }
    }

    const [activeSort] = sorting;
    if (activeSort) {
      if (activeSort.id === 'title' && activeSort.desc === false) {
        searchParams.delete('sort');
      } else {
        searchParams.set('sort', `${activeSort.desc ? '-' : ''}${activeSort.id}`);
      }
    }

    // `searchParams.size` is not supported in all envs, especially Node.js (Vitest).
    const searchParamsSize = Array.from(searchParams).length;

    const newUrl = searchParamsSize
      ? `${window.location.pathname}?${searchParams.toString()}`
      : window.location.pathname;
    window.history.replaceState(null, '', newUrl);
  }, [pagination, sorting, columnFilters]);
}
