import type { ColumnFiltersState } from '@tanstack/react-table';

export function getAreNonDefaultFiltersSet(
  currentFilters: ColumnFiltersState,
  defaultColumnFilters?: ColumnFiltersState,
): boolean {
  if (currentFilters.length !== defaultColumnFilters?.length) {
    return true;
  }

  return currentFilters.some((filter, index) => {
    const defaultFilter = defaultColumnFilters[index];

    return filter.id !== defaultFilter.id || filter.value !== defaultFilter.value;
  });
}
