import type { ColumnFiltersState, Updater } from '@tanstack/react-table';
import { useState } from 'react';

import { usePageProps } from '@/common/hooks/usePageProps';

import { resolveInitialTicketListColumnFilters } from '../utils/resolveInitialTicketListColumnFilters';

/**
 * 🔴 You should only use this hook once in the entire component tree.
 *    It is a factory. The state is not global.
 *    Every invocation will create entirely new state values.
 */

export function useTicketListState(
  paginatedTickets: App.Data.PaginatedData<App.Platform.Data.TicketListEntry>,
  serverDefaultColumnFilters: ColumnFiltersState,
) {
  const {
    ziggy: { query },
  } = usePageProps<App.Platform.Data.TicketListPageProps>();

  const [initialColumnFilters] = useState<ColumnFiltersState>(() =>
    resolveInitialTicketListColumnFilters(query, serverDefaultColumnFilters),
  );

  const [columnFilters, setColumnFilters] = useState<ColumnFiltersState>(initialColumnFilters);
  const [pageNumber, setPageNumber] = useState(paginatedTickets.currentPage);

  const setColumnFiltersAndResetPage = (updaterOrValue: Updater<ColumnFiltersState>) => {
    setPageNumber(1);
    setColumnFilters(updaterOrValue);
  };

  return {
    columnFilters,
    pageNumber,
    setColumnFilters: setColumnFiltersAndResetPage,
    setPageNumber,
  };
}
