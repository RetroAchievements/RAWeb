import type { ColumnFiltersState } from '@tanstack/react-table';

/**
 * Replaces one filter's value, or appends it when the filter is not yet
 * set. Values are always wrapped in an array because that is the shape the
 * URL resolver produces.
 */
export function setTicketListColumnFilterValue(
  columnFilters: ColumnFiltersState,
  filterId: string,
  value: string,
): ColumnFiltersState {
  const hasFilter = columnFilters.some((filter) => filter.id === filterId);

  if (!hasFilter) {
    return [...columnFilters, { id: filterId, value: [value] }];
  }

  return columnFilters.map((filter) =>
    filter.id === filterId ? { ...filter, value: [value] } : filter,
  );
}
