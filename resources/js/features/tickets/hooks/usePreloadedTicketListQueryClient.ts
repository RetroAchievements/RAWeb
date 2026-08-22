import { QueryClient } from '@tanstack/react-query';
import { useMemo, useState } from 'react';

import type { TicketListQueryData, TicketListQueryOptionsInput } from './ticketListQueryOptions';
import { buildTicketListQueryOptions } from './ticketListQueryOptions';

/**
 * We need to populate tanstack-query with an initial value during the
 * server render, otherwise it will immediately fetch data we already
 * have as soon as client-side hydration hits.
 *
 * This hook, combined with <HydrationBoundary />, lets us avoid this
 * erroneous extra fetch for data we already have.
 */

interface UsePreloadedTicketListQueryClientProps
  extends TicketListQueryOptionsInput, TicketListQueryData {}

export function usePreloadedTicketListQueryClient({
  facetCounts,
  paginatedTickets,
  stateCounts,
  ...queryOptionsInput
}: UsePreloadedTicketListQueryClientProps) {
  const [queryClient] = useState(() => new QueryClient());

  /**
   * It's very important to memoize the queryClient.
   * If we don't, the whole queryClient will be reset on every single re-render.
   * From the user's perspective, it'll appear that they can never page, filter, sort, etc.
   */
  useMemo(() => {
    queryClient.setQueryData(buildTicketListQueryOptions(queryOptionsInput).queryKey, {
      paginatedTickets,
      stateCounts,
      facetCounts,
    });

    /* eslint-disable react-compiler/react-compiler -- exhaustive-deps is intentionally constrained */
    /* eslint-disable-next-line react-hooks/exhaustive-deps -- needed for ssr */
  }, [queryClient]);

  return { queryClientWithInitialData: queryClient };
}
