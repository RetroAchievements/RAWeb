import type { ColumnFiltersState } from '@tanstack/react-table';

export function buildGameListQueryFilterParams(
  columnFilters: ColumnFiltersState,
): Record<string, string> {
  const params: Record<string, string> = {};

  for (const columnFilter of columnFilters) {
    const filterKey = `filter[${columnFilter.id}]`;

    if (Array.isArray(columnFilter.value)) {
      params[filterKey] = columnFilter.value.join(',');
    } else {
      params[filterKey] = columnFilter.value as string;
    }
  }

  return params;
}
