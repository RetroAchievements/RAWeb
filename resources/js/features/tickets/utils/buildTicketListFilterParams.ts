import type { ColumnFiltersState } from '@tanstack/react-table';

export function buildTicketListFilterParams(
  columnFilters: ColumnFiltersState,
): Record<string, string> {
  const params: Record<string, string> = {};

  for (const columnFilter of columnFilters) {
    params[`filter[${columnFilter.id}]`] = String(columnFilter.value);
  }

  return params;
}
