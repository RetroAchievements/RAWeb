import type { QueryClient } from '@tanstack/react-query';

import type { TicketListQueryOptionsInput } from './ticketListQueryOptions';
import { buildTicketListQueryOptions } from './ticketListQueryOptions';

interface UseTicketListPrefetchPaginationProps extends Omit<
  TicketListQueryOptionsInput,
  'pageNumber'
> {
  queryClient: QueryClient;
}

/**
 * Given the user hovers over a pagination button, it is very likely they will
 * wind up clicking the button. Queries are cheap, so prefetch the destination page.
 */
export function useTicketListPrefetchPagination({
  queryClient,
  ...queryOptionsInput
}: UseTicketListPrefetchPaginationProps) {
  const prefetchPage = (pageNumber: number) => {
    queryClient.prefetchQuery(buildTicketListQueryOptions({ ...queryOptionsInput, pageNumber }));
  };

  return { prefetchPage };
}
