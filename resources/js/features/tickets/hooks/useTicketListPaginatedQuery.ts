import { hashKey, keepPreviousData, QueryClient, useQuery } from '@tanstack/react-query';
import { useRef, useState } from 'react';

import type { TicketListQueryData, TicketListQueryOptionsInput } from '../models';
import { buildTicketListQueryOptions } from './ticketListQueryOptions';

interface UseTicketListPaginatedQueryProps extends TicketListQueryOptionsInput {
  initialData: TicketListQueryData;
}

export function useTicketListPaginatedQuery({
  initialData,
  ...queryOptionsInput
}: UseTicketListPaginatedQueryProps) {
  const [queryClient] = useState(() => new QueryClient());

  const queryOptions = buildTicketListQueryOptions(queryOptionsInput);

  const initialQueryHash = useRef(hashKey(queryOptions.queryKey)).current;

  const isInitialQuery = hashKey(queryOptions.queryKey) === initialQueryHash;

  const { data, isFetching } = useQuery(
    {
      ...queryOptions,

      initialData: isInitialQuery ? initialData : undefined,
      placeholderData: keepPreviousData,

      refetchOnWindowFocus: false,
      refetchOnReconnect: false,
    },
    queryClient,
  );

  const prefetchList = (
    overrides: Partial<
      Pick<TicketListQueryOptionsInput, 'columnFilters' | 'sortParam' | 'pageNumber'>
    >,
  ) => {
    queryClient.prefetchQuery(
      buildTicketListQueryOptions({ ...queryOptionsInput, pageNumber: 1, ...overrides }),
    );
  };

  return { data: data ?? initialData, isFetching, prefetchList };
}
