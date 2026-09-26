import type { ColumnFiltersState } from '@tanstack/react-table';

import { getDoesTicketListFilterApplyToStatus } from './getDoesTicketListFilterApplyToStatus';
import { getTicketListFilterValue } from './getTicketListFilterValue';

export function getTicketListColumnFiltersForStatus(
  columnFilters: ColumnFiltersState,
): ColumnFiltersState {
  const statusValue = getTicketListFilterValue<App.Platform.Enums.TicketListStatusFilter>(
    columnFilters,
    'status',
  );

  if (!statusValue) {
    return columnFilters;
  }

  return columnFilters.filter((columnFilter) =>
    getDoesTicketListFilterApplyToStatus(
      columnFilter.id as App.Platform.Enums.TicketListFilterKind,
      statusValue,
    ),
  );
}
