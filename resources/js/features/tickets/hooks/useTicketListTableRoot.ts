import type { ColumnFiltersState } from '@tanstack/react-table';

import type { TicketListColumnId } from '../models';
import { getTicketListDefaultColumnVisibility } from '../utils/getTicketListDefaultColumnVisibility';
import { getTicketListFilterValue } from '../utils/getTicketListFilterValue';
import { ticketListSort } from '../utils/ticketListSort';
import { toggleTicketListColumnOverride } from '../utils/toggleTicketListColumnOverride';
import { useTicketListPaginatedQuery } from './useTicketListPaginatedQuery';
import { useTicketListState } from './useTicketListState';
import { useTicketListTableSync } from './useTicketListTableSync';

interface UseTicketListTableRootOptions {
  serverDefaultColumnFilters: ColumnFiltersState;
  facetCounts: Record<string, Record<string, number>>;
  paginatedTickets: App.Data.PaginatedData<App.Platform.Data.TicketListEntry>;
  scope: App.Platform.Enums.TicketListScope;
  stateCounts: App.Platform.Data.TicketListStateCounts;
  targetParams: Record<string, number>;
}

export function useTicketListTableRoot({
  serverDefaultColumnFilters,
  facetCounts,
  paginatedTickets,
  scope,
  stateCounts,
  targetParams,
}: UseTicketListTableRootOptions) {
  const {
    columnFilters,
    columnVisibilityOverrides,
    pageNumber,
    restoreState,
    setColumnFilters,
    setColumnVisibilityOverrides,
    setPageNumber,
    setSortParam,
    sortParam,
  } = useTicketListState(paginatedTickets, serverDefaultColumnFilters);

  const statusValue = getTicketListFilterValue(
    columnFilters,
    'status',
  ) as App.Platform.Enums.TicketListStatusFilter;

  const defaultColumnVisibility = getTicketListDefaultColumnVisibility(scope, statusValue);

  const toggleColumnVisibility = (columnId: TicketListColumnId) => {
    setColumnVisibilityOverrides((previousOverrides) =>
      toggleTicketListColumnOverride(previousOverrides, defaultColumnVisibility, columnId),
    );
  };

  const resetDisplay = () => {
    setColumnVisibilityOverrides({});
    setSortParam(ticketListSort.defaultParam);
  };

  useTicketListTableSync({
    columnFilters,
    columnVisibilityOverrides,
    serverDefaultColumnFilters,
    pageNumber,
    restoreState,
    sortParam,
  });

  const ticketListQuery = useTicketListPaginatedQuery({
    columnFilters,
    initialData: { facetCounts, paginatedTickets, stateCounts },
    pageNumber,
    scope,
    sortParam,
    targetParams,
  });

  return {
    ticketListTableProps: {
      columnFilters,
      resetDisplay,
      setColumnFilters,
      setPageNumber,
      setSortParam,
      sortParam,
      toggleColumnVisibility,
      prefetchPage: ticketListQuery.prefetchPage,
      columnVisibility: { ...defaultColumnVisibility, ...columnVisibilityOverrides },
      hasColumnVisibilityOverrides: Object.keys(columnVisibilityOverrides).length > 0,
      isFetching: ticketListQuery.isFetching,
      ...ticketListQuery.data,
    },
  };
}
