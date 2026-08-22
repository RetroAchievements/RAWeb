import { dehydrate } from '@tanstack/react-query';
import type { ColumnFiltersState } from '@tanstack/react-table';
import { useState } from 'react';

import { usePageProps } from '@/common/hooks/usePageProps';

import { usePreloadedTicketListQueryClient } from './usePreloadedTicketListQueryClient';
import { useTicketListPaginatedQuery } from './useTicketListPaginatedQuery';
import { useTicketListPrefetchPagination } from './useTicketListPrefetchPagination';
import { useTicketListState } from './useTicketListState';
import { useTicketListTableSync } from './useTicketListTableSync';

interface UseTicketListTableRootOptions {
  serverDefaultColumnFilters: ColumnFiltersState;
  facetCounts: Record<string, Record<string, number>>;
  paginatedTickets: App.Data.PaginatedData<App.Platform.Data.TicketListEntry>;
  scope: App.Platform.Enums.TicketListScope;
  stateCounts: App.Platform.Data.TicketListStateCounts;
}

export function useTicketListTableRoot({
  serverDefaultColumnFilters,
  facetCounts,
  paginatedTickets,
  scope,
  stateCounts,
}: UseTicketListTableRootOptions) {
  const {
    ziggy: { query },
  } = usePageProps<App.Platform.Data.TicketListPageProps>();

  const [sortParam] = useState<string | null>(() =>
    typeof query.sort === 'string' && query.sort.length > 0 ? query.sort : null,
  );

  const { columnFilters, pageNumber, setColumnFilters, setPageNumber } = useTicketListState(
    paginatedTickets,
    serverDefaultColumnFilters,
  );

  const { queryClientWithInitialData } = usePreloadedTicketListQueryClient({
    columnFilters,
    facetCounts,
    pageNumber,
    paginatedTickets,
    scope,
    sortParam,
    stateCounts,
  });

  useTicketListTableSync({ columnFilters, serverDefaultColumnFilters, pageNumber });

  const ticketListQuery = useTicketListPaginatedQuery({
    columnFilters,
    pageNumber,
    scope,
    sortParam,
    queryClient: queryClientWithInitialData,
  });

  const { prefetchPage } = useTicketListPrefetchPagination({
    columnFilters,
    scope,
    sortParam,
    queryClient: queryClientWithInitialData,
  });

  return {
    hydrationState: dehydrate(queryClientWithInitialData),
    ticketListTableProps: {
      columnFilters,
      prefetchPage,
      setColumnFilters,
      setPageNumber,
      isFetching: ticketListQuery.isFetching,
      ...(ticketListQuery.data ?? { paginatedTickets, stateCounts, facetCounts }),
    },
  };
}
