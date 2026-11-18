import type { ColumnFiltersState } from '@tanstack/react-table';

/**
 * Replaces one filter's value, or appends it when the filter is not yet
 * set. Values are always wrapped in an array because that is the shape the
 * URL resolver produces. An empty value removes the filter.
 */
export function setTicketListColumnFilterValue(
  columnFilters: ColumnFiltersState,
  filterId: string,
  value: string,
): ColumnFiltersState {
  if (value === '') {
    return columnFilters.filter((filter) => filter.id !== filterId);
  }

  const hasFilter = columnFilters.some((filter) => filter.id === filterId);

  if (!hasFilter) {
    return [...columnFilters, { id: filterId, value: [value] }];
  }

  return columnFilters.map((filter) =>
    filter.id === filterId ? { ...filter, value: [value] } : filter,
  );
}
