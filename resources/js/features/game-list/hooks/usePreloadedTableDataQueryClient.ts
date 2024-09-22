import { QueryClient } from '@tanstack/react-query';
import type { ColumnFiltersState, PaginationState, SortingState } from '@tanstack/react-table';
import { useMemo, useState } from 'react';

/**
 * We need to populate tanstack-query with an initial value during the
 * server render, otherwise it will immediately fetch data we already
 * have as soon as client-side hydration hits.
 *
 * This hook, combined with <HydrationBoundary />, lets us avoid this
 * erroneous extra fetch for data we already have.
 */

interface UseSsrQueryClientHydrationProps<TData = unknown> {
  columnFilters: ColumnFiltersState;
  paginatedData: App.Data.PaginatedData<TData>;
  pagination: PaginationState;
  sorting: SortingState;
}

export function usePreloadedTableDataQueryClient<TData = unknown>({
  columnFilters,
  paginatedData,
  pagination,
  sorting,
}: UseSsrQueryClientHydrationProps<TData>) {
  const [queryClient] = useState(() => new QueryClient());

  /**
   * It's very important to memoize the queryClient.
   * If we don't, the whole queryClient will be reset on every single re-render.
   * From the user's perspective, it'll appear that they can never page, filter, sort, etc.
   */
  useMemo(() => {
    queryClient.setQueryData(['data', pagination, sorting, columnFilters], paginatedData);
    // eslint-disable-next-line react-hooks/exhaustive-deps -- needed for ssr
  }, [queryClient]);

  return { queryClientWithInitialData: queryClient };
}
