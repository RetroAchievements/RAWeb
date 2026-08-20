import { QueryClient } from '@tanstack/react-query';
import { useMemo, useState } from 'react';

interface UsePreloadedTicketListQueryClientProps {
  pageNumber: number;
  paginatedTickets: App.Data.PaginatedData<App.Platform.Data.TicketListEntry>;
  passthroughParams: Record<string, string>;
  scope: App.Platform.Enums.TicketListScope;
}

export function usePreloadedTicketListQueryClient({
  pageNumber,
  paginatedTickets,
  passthroughParams,
  scope,
}: UsePreloadedTicketListQueryClientProps) {
  const [queryClient] = useState(() => new QueryClient());

  /**
   * It's very important to memoize the queryClient.
   * If we don't, the whole queryClient will be reset on every single re-render.
   * From the user's perspective, it'll appear that they can never page, filter, sort, etc.
   */
  useMemo(() => {
    // This seed must use the exact key and payload shape the paginated
    // query reads, otherwise the client refetches data it already has.
    queryClient.setQueryData(['ticket-list', scope, passthroughParams, pageNumber], {
      paginatedTickets,
    });

    /* eslint-disable react-compiler/react-compiler -- exhaustive-deps is intentionally constrained */
    /* eslint-disable-next-line react-hooks/exhaustive-deps -- needed for ssr */
  }, [queryClient]);

  return { queryClientWithInitialData: queryClient };
}
