import type { QueryClient } from '@tanstack/react-query';
import axios from 'axios';
import { route } from 'ziggy-js';

const ONE_MINUTE = 1 * 60 * 1000;

interface UseTicketListPrefetchPaginationProps {
  passthroughParams: Record<string, string>;
  queryClient: QueryClient;
  scope: App.Platform.Enums.TicketListScope;
}

/**
 * Given the user hovers over a pagination button, it is very likely they will
 * wind up clicking the button. Queries are cheap, so prefetch the destination page.
 */
export function useTicketListPrefetchPagination({
  passthroughParams,
  queryClient,
  scope,
}: UseTicketListPrefetchPaginationProps) {
  const prefetchPage = (pageNumber: number) => {
    queryClient.prefetchQuery({
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
    });
  };

  return { prefetchPage };
}
