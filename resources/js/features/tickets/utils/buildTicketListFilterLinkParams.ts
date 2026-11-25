import type { ColumnFiltersState } from '@tanstack/react-table';

import { normalizeTicketListFilterValue } from './normalizeTicketListFilterValue';

export function buildTicketListFilterLinkParams(
  columnFilters: ColumnFiltersState,
  serverDefaultColumnFilters: ColumnFiltersState,
): Record<string, string> {
  const defaultFilterValues = new Map(
    serverDefaultColumnFilters.map((filter) => [
      filter.id,
      normalizeTicketListFilterValue(filter.value),
    ]),
  );

  const params: Record<string, string> = {};

  for (const columnFilter of columnFilters) {
    const filterValue = normalizeTicketListFilterValue(columnFilter.value);
    if (filterValue === null) {
      continue;
    }

    const isStatus = columnFilter.id === 'status';
    const isDefaultValue = defaultFilterValues.get(columnFilter.id) === filterValue;
    if (!isStatus && isDefaultValue) {
      continue;
    }

    params[`filter[${columnFilter.id}]`] = filterValue;
  }

  return params;
}
