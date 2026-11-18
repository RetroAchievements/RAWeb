import type { ColumnFiltersState } from '@tanstack/react-table';

import type { AppGlobalProps } from '@/common/models';

/**
 * Resolves the filters the list starts with. The URL wins, and the scope
 * defaults fill in whatever the URL is silent about, so the first client
 * render matches the page the server just sent.
 */
export function resolveInitialTicketListColumnFilters(
  query: AppGlobalProps['ziggy']['query'],
  serverDefaultColumnFilters: ColumnFiltersState,
): ColumnFiltersState {
  const filterQuery = getFilterQuery(query);
  if (!filterQuery) {
    return serverDefaultColumnFilters;
  }

  const columnFilters: ColumnFiltersState = Object.entries(filterQuery).map(
    ([filterKey, filterValue]) => ({ id: filterKey, value: [filterValue] }),
  );

  for (const defaultColumnFilter of serverDefaultColumnFilters) {
    const isAlreadySet = columnFilters.some((filter) => filter.id === defaultColumnFilter.id);
    if (!isAlreadySet) {
      columnFilters.push(defaultColumnFilter);
    }
  }

  return columnFilters;
}

function getFilterQuery(query: AppGlobalProps['ziggy']['query']): Record<string, string> | null {
  const filterQuery = query.filter;

  if (!filterQuery || typeof filterQuery !== 'object') {
    return null;
  }

  const stringEntries = Object.entries(filterQuery).filter(
    (entry): entry is [string, string] => typeof entry[1] === 'string' && entry[1].length > 0,
  );

  return stringEntries.length ? Object.fromEntries(stringEntries) : null;
}
