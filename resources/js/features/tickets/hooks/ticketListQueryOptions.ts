import axios from 'axios';
import { route } from 'ziggy-js';

import type { TicketListQueryData, TicketListQueryOptionsInput } from '../models';
import { buildTicketListFilterParams } from '../utils/buildTicketListFilterParams';

const ONE_MINUTE = 1 * 60 * 1000;

export function buildTicketListQueryOptions({
  columnFilters,
  pageNumber,
  scope,
  sortParam,
  targetParams,
}: TicketListQueryOptionsInput) {
  return {
    queryKey: ['ticket-list', scope, targetParams, sortParam, columnFilters, pageNumber],

    queryFn: async (): Promise<TicketListQueryData> => {
      const response = await axios.get<TicketListQueryData>(
        route('api.ticket.index', {
          scope,
          ...targetParams,
          sort: sortParam,
          ...buildTicketListFilterParams(columnFilters),
          'page[number]': pageNumber,
        }),
      );

      return response.data;
    },

    staleTime: ONE_MINUTE,
  };
}
