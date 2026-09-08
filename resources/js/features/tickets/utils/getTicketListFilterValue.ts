import type { ColumnFiltersState } from '@tanstack/react-table';

import { normalizeTicketListFilterValue } from './normalizeTicketListFilterValue';

/**
 * The current value of a single filter. Null when it's unset.
 */
export function getTicketListFilterValue(
  columnFilters: ColumnFiltersState,
  filterId: string,
): string | null {
  const columnFilter = columnFilters.find((filter) => filter.id === filterId);

  return columnFilter ? normalizeTicketListFilterValue(columnFilter.value) : null;
}
