import type { ColumnFiltersState } from '@tanstack/react-table';

import { normalizeTicketListFilterValue } from './normalizeTicketListFilterValue';

export function getAreTicketListFiltersNonDefault(
  columnFilters: ColumnFiltersState,
  serverDefaultColumnFilters: ColumnFiltersState,
): boolean {
  const defaultValuesById = new Map(
    serverDefaultColumnFilters.map((filter) => [
      filter.id,
      normalizeTicketListFilterValue(filter.value),
    ]),
  );

  return columnFilters.some(
    (filter) =>
      !defaultValuesById.has(filter.id) ||
      defaultValuesById.get(filter.id) !== normalizeTicketListFilterValue(filter.value),
  );
}
