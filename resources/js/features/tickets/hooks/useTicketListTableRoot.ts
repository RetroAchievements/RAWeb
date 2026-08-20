import { dehydrate } from '@tanstack/react-query';
import { useState } from 'react';

import { usePageProps } from '@/common/hooks/usePageProps';

import { buildTicketListPassthroughParams } from '../utils/buildTicketListPassthroughParams';
import { usePreloadedTicketListQueryClient } from './usePreloadedTicketListQueryClient';
import { useTicketListPaginatedQuery } from './useTicketListPaginatedQuery';
import { useTicketListPrefetchPagination } from './useTicketListPrefetchPagination';
import { useTicketListTableSync } from './useTicketListTableSync';

interface UseTicketListTableRootOptions {
  paginatedTickets: App.Data.PaginatedData<App.Platform.Data.TicketListEntry>;
  scope: App.Platform.Enums.TicketListScope;
}

export function useTicketListTableRoot({ paginatedTickets, scope }: UseTicketListTableRootOptions) {
  const {
    ziggy: { query },
  } = usePageProps<App.Platform.Data.TicketListPageProps>();

  // temporary safeguard
  const [passthroughParams] = useState(() => buildTicketListPassthroughParams(query));

  const [pageNumber, setPageNumber] = useState(paginatedTickets.currentPage);

  const { queryClientWithInitialData } = usePreloadedTicketListQueryClient({
    pageNumber,
    paginatedTickets,
    passthroughParams,
    scope,
  });

  useTicketListTableSync(pageNumber);

  const ticketListQuery = useTicketListPaginatedQuery({
    pageNumber,
    passthroughParams,
    scope,
    queryClient: queryClientWithInitialData,
  });

  const { prefetchPage } = useTicketListPrefetchPagination({
    passthroughParams,
    scope,
    queryClient: queryClientWithInitialData,
  });

  return {
    hydrationState: dehydrate(queryClientWithInitialData),
    ticketListTableProps: {
      isFetching: ticketListQuery.isFetching,
      paginatedTickets: ticketListQuery.data?.paginatedTickets ?? paginatedTickets,
      prefetchPage,
      setPageNumber,
    },
  };
}
