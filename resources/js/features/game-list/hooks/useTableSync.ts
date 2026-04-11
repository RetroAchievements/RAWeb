import type {
  ColumnFiltersState,
  ColumnSort,
  PaginationState,
  SortingState,
  VisibilityState,
} from '@tanstack/react-table';
import { useCookie, useUpdateEffect } from 'react-use';

import { usePageProps } from '@/common/hooks/usePageProps';

import { buildPersistedGameListViewState } from '../utils/buildPersistedGameListViewState';
import { serializeGameListViewState } from '../utils/serializeGameListViewState';

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
      const tableState = buildPersistedGameListViewState({
        columnFilters,
        columnVisibility,
        pagination,
        sorting,
      });

      setCookie(JSON.stringify(tableState), { expires: 180 });
    } else if (cookie) {
      deleteCookie();
    }
  }, [isUserPersistenceEnabled, columnFilters, columnVisibility, pagination, sorting]);

  useUpdateEffect(() => {
    const searchParams = serializeGameListViewState({
      currentSearch: window.location.search,
      columnFilters,
      pagination,
      sorting,
      defaultColumnFilters,
      defaultColumnSort,
      defaultPageSize,
    });

    const searchParamsSize = Array.from(searchParams).length;
    const newUrl = searchParamsSize
      ? `${window.location.pathname}?${searchParams.toString()}`
      : window.location.pathname;

    const currentUrl = `${window.location.pathname}${window.location.search}`;

    if (newUrl === currentUrl) {
      return;
    }

    window.history.pushState({ inertia: true }, '', newUrl);
  }, [pagination, sorting, columnFilters]);
}
