import type { ColumnFiltersState } from '@tanstack/react-table';
import axios from 'axios';
import { route } from 'ziggy-js';

import { buildTicketListFilterParams } from '../utils/buildTicketListFilterParams';

const ONE_MINUTE = 1 * 60 * 1000;

export interface TicketListQueryData {
  paginatedTickets: App.Data.PaginatedData<App.Platform.Data.TicketListEntry>;
  stateCounts: App.Platform.Data.TicketListStateCounts;
  facetCounts: Record<string, Record<string, number>>;
}

export interface TicketListQueryOptionsInput {
  columnFilters: ColumnFiltersState;
  pageNumber: number;
  scope: App.Platform.Enums.TicketListScope;
  sortParam: string | null;
}

export function buildTicketListQueryOptions({
  columnFilters,
  pageNumber,
  scope,
  sortParam,
}: TicketListQueryOptionsInput) {
  return {
    queryKey: ['ticket-list', scope, sortParam, columnFilters, pageNumber],

    queryFn: async (): Promise<TicketListQueryData> => {
      const response = await axios.get<TicketListQueryData>(
        route('api.ticket.index', {
          scope,
          ...(sortParam ? { sort: sortParam } : {}),
          ...buildTicketListFilterParams(columnFilters),
          'page[number]': pageNumber,
        }),
      );

      return response.data;
    },

    staleTime: ONE_MINUTE,
  };
}
