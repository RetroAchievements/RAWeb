import type { ColumnFiltersState } from '@tanstack/react-table';

/**
 * Checks if the current column filters differ from the default filters.
 *
 * @param currentFilters The current state of column filters.
 * @param defaultColumnFilters The default state of column filters to compare against.
 */
export function getAreNonDefaultFiltersSet(
  currentFilters: ColumnFiltersState,
  defaultColumnFilters?: ColumnFiltersState,
): boolean {
  if (currentFilters.length !== defaultColumnFilters?.length) {
    return true;
  }

  return currentFilters.some((filter, index) => {
    const defaultFilter = defaultColumnFilters[index];

    if (filter.id !== defaultFilter.id) {
      return true;
    }

    if (Array.isArray(filter.value) && Array.isArray(defaultFilter.value)) {
      const currentArray = filter.value as unknown[];
      const defaultArray = defaultFilter.value as unknown[];

      return (
        currentArray.length !== defaultArray.length ||
        !currentArray.every((val, i) => val === defaultArray[i])
      );
    }

    return filter.value !== defaultFilter.value;
  });
}
