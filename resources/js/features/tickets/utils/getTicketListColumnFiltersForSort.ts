import type { ColumnFiltersState } from '@tanstack/react-table';

import type { TicketListSortParam } from '../models';
import { getTicketListFilterValue } from './getTicketListFilterValue';
import { setTicketListColumnFilterValue } from './setTicketListColumnFilterValue';
import { ticketListSort } from './ticketListSort';

const statusValuesWithoutResolvedDate: App.Platform.Enums.TicketListStatusFilter[] = [
  'unresolved',
  'open',
  'request',
  'quarantined',
];

export function getTicketListColumnFiltersForSort(
  columnFilters: ColumnFiltersState,
  nextSortParam: TicketListSortParam,
): ColumnFiltersState {
  const statusValue = getTicketListFilterValue<App.Platform.Enums.TicketListStatusFilter>(
    columnFilters,
    'status',
  );

  const isResolvedSort = ticketListSort.field(nextSortParam) === 'resolvedAt';
  if (isResolvedSort && statusValue && statusValuesWithoutResolvedDate.includes(statusValue)) {
    return setTicketListColumnFilterValue(columnFilters, 'status', 'resolved');
  }

  return columnFilters;
}
