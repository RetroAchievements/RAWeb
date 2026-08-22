import type { QueryClient } from '@tanstack/react-query';
import { keepPreviousData, useQuery } from '@tanstack/react-query';

import type { TicketListQueryOptionsInput } from './ticketListQueryOptions';
import { buildTicketListQueryOptions } from './ticketListQueryOptions';

interface UseTicketListPaginatedQueryProps extends TicketListQueryOptionsInput {
  queryClient?: QueryClient;
}

export function useTicketListPaginatedQuery({
  queryClient,
  ...queryOptionsInput
}: UseTicketListPaginatedQueryProps) {
  return useQuery(
    {
      ...buildTicketListQueryOptions(queryOptionsInput),

      placeholderData: keepPreviousData,

      refetchOnWindowFocus: false,
      refetchOnReconnect: false,
    },
    queryClient,
  );
}
