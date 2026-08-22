import type { ColumnFiltersState } from '@tanstack/react-table';

import { normalizeTicketListFilterValue } from './normalizeTicketListFilterValue';

interface SerializeTicketListSearchParamsOptions {
  columnFilters: ColumnFiltersState;
  pageNumber: number;

  currentSearch?: string;
  serverDefaultColumnFilters?: ColumnFiltersState;
}

/**
 * Builds the URL search params for the current list state. Values that
 * equal the default params are omitted.
 */
export function serializeTicketListSearchParams({
  columnFilters,
  pageNumber,
  currentSearch = '',
  serverDefaultColumnFilters = [],
}: SerializeTicketListSearchParamsOptions): URLSearchParams {
  const searchParams = new URLSearchParams(currentSearch);

  if (pageNumber > 1) {
    searchParams.set('page[number]', String(pageNumber));
  } else {
    searchParams.delete('page[number]');
  }

  updateFilters(searchParams, columnFilters, serverDefaultColumnFilters);

  return searchParams;
}

function updateFilters(
  searchParams: URLSearchParams,
  columnFilters: ColumnFiltersState,
  serverDefaultColumnFilters: ColumnFiltersState,
): void {
  const activeFilterKeys = new Set(columnFilters.map((filter) => `filter[${filter.id}]`));
  const defaultFilterValues = new Map(
    serverDefaultColumnFilters.map((filter) => [
      filter.id,
      normalizeTicketListFilterValue(filter.value),
    ]),
  );

  for (const columnFilter of columnFilters) {
    const filterKey = `filter[${columnFilter.id}]`;
    const filterValue = normalizeTicketListFilterValue(columnFilter.value);

    const isDefaultValue = defaultFilterValues.get(columnFilter.id) === filterValue;

    if (isDefaultValue || filterValue === null) {
      searchParams.delete(filterKey);
      continue;
    }

    searchParams.set(filterKey, filterValue);
  }

  // Filters that were in the URL but are no longer active are stale.
  for (const paramKey of Array.from(searchParams.keys())) {
    if (paramKey.startsWith('filter[') && !activeFilterKeys.has(paramKey)) {
      searchParams.delete(paramKey);
    }
  }
}
