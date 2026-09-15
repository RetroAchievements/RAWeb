import type { ColumnFiltersState, Updater } from '@tanstack/react-table';
import { useState } from 'react';

import { usePageProps } from '@/common/hooks/usePageProps';

import type { TicketListSortParam, TicketListUrlState } from '../models';
import { getTicketListFilterValue } from '../utils/getTicketListFilterValue';
import { resolveInitialTicketListColumnFilters } from '../utils/resolveInitialTicketListColumnFilters';
import { resolveTicketListViewPreferences } from '../utils/resolveTicketListViewPreferences';
import { setTicketListColumnFilterValue } from '../utils/setTicketListColumnFilterValue';
import { ticketListSort } from '../utils/ticketListSort';

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
    persistedViewPreferences,
    ziggy: { query },
  } = usePageProps<App.Platform.Data.TicketListPageProps>();

  const [initialViewPreferences] = useState(() =>
    resolveTicketListViewPreferences(persistedViewPreferences),
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

  const autoChangeStatusFilterValue = (nextSortParam: TicketListSortParam) => {
    const statusValuesWithoutResolvedDate: App.Platform.Enums.TicketListStatusFilter[] = [
      'unresolved',
      'open',
      'request',
      'quarantined',
    ];

    const statusValue = getTicketListFilterValue<App.Platform.Enums.TicketListStatusFilter>(
      columnFilters,
      'status',
    );

    const isResolvedSort = ticketListSort.field(nextSortParam) === 'resolvedAt';
    if (isResolvedSort && statusValue && statusValuesWithoutResolvedDate.includes(statusValue)) {
      setColumnFilters((previousFilters) =>
        setTicketListColumnFilterValue(previousFilters, 'status', 'resolved'),
      );
    }
  };

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

    autoChangeStatusFilterValue(nextSortParam);
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
