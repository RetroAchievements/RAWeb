import type { QueryClient } from '@tanstack/react-query';
import { keepPreviousData, useQuery } from '@tanstack/react-query';
import axios from 'axios';
import { route } from 'ziggy-js';

const ONE_MINUTE = 1 * 60 * 1000;

interface UseTicketListPaginatedQueryProps {
  pageNumber: number;
  passthroughParams: Record<string, string>;
  scope: App.Platform.Enums.TicketListScope;

  queryClient?: QueryClient;
}

export function useTicketListPaginatedQuery({
  pageNumber,
  passthroughParams,
  scope,
  queryClient,
}: UseTicketListPaginatedQueryProps) {
  return useQuery(
    {
      queryKey: ['ticket-list', scope, passthroughParams, pageNumber],

      queryFn: async () => {
        const response = await axios.get<{
          paginatedTickets: App.Data.PaginatedData<App.Platform.Data.TicketListEntry>;
        }>(
          route('api.ticket.index', {
            scope,
            ...passthroughParams,
            'page[number]': pageNumber,
          }),
        );

        return response.data;
      },

      staleTime: ONE_MINUTE,
      placeholderData: keepPreviousData,

      refetchOnWindowFocus: false,
      refetchOnReconnect: false,
    },
    queryClient,
  );
}
