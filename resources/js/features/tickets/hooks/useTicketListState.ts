import type { ColumnFiltersState, Updater } from '@tanstack/react-table';
import { useState } from 'react';

import { usePageProps } from '@/common/hooks/usePageProps';

import type { TicketListSortParam, TicketListUrlState } from '../models';
import { getTicketListColumnFiltersForSort } from '../utils/getTicketListColumnFiltersForSort';
import { resolveInitialTicketListColumnFilters } from '../utils/resolveInitialTicketListColumnFilters';
import { resolveTicketListViewPreferences } from '../utils/resolveTicketListViewPreferences';
import { ticketListSort } from '../utils/ticketListSort';

/**
 * 🔴 You should only use this hook once in the entire component tree.
 *    It is a factory. The state is not global.
 *    Every invocation will create entirely new state values.
 */

export function useTicketListState(
  paginatedTickets: App.Data.PaginatedData<App.Platform.Data.TicketListEntry>,
  serverDefaultColumnFilters: ColumnFiltersState,
  serverDefaultSortParam: TicketListSortParam,
) {
  const {
    persistedViewPreferences,
    ziggy: { query },
  } = usePageProps<App.Platform.Data.TicketListPageProps>();

  const [initialViewPreferences] = useState(() =>
    resolveTicketListViewPreferences(persistedViewPreferences, serverDefaultSortParam),
  );

  const [initialColumnFilters] = useState<ColumnFiltersState>(() =>
    resolveInitialTicketListColumnFilters(query, serverDefaultColumnFilters),
  );

  const [columnFilters, setColumnFilters] = useState<ColumnFiltersState>(initialColumnFilters);
  const [pageNumber, setPageNumber] = useState(paginatedTickets.currentPage);
  const [sortParam, setSortParam] = useState<TicketListSortParam>(() =>
    ticketListSort.resolve(query.sort, initialViewPreferences.sortParam),
  );

  const [columnVisibilityOverrides, setColumnVisibilityOverrides] = useState(
    initialViewPreferences.columnVisibility,
  );

  const setColumnFiltersAndResetPage = (updaterOrValue: Updater<ColumnFiltersState>) => {
    setPageNumber(1);
    setColumnFilters(updaterOrValue);
  };

  const setSortParamAndResetPage = (nextSortParam: TicketListSortParam) => {
    if (nextSortParam === sortParam) {
      return;
    }

    setPageNumber(1);
    setSortParam(nextSortParam);
    setColumnFilters((previousFilters) =>
      getTicketListColumnFiltersForSort(previousFilters, nextSortParam),
    );
  };

  const restoreState = (urlState: TicketListUrlState) => {
    setColumnFilters(urlState.columnFilters);
    setPageNumber(urlState.pageNumber);
    setSortParam(urlState.sortParam);
  };

  return {
    columnFilters,
    columnVisibilityOverrides,
    pageNumber,
    restoreState,
    setColumnVisibilityOverrides,
    setPageNumber,
    sortParam,
    setColumnFilters: setColumnFiltersAndResetPage,
    setSortParam: setSortParamAndResetPage,
  };
}
