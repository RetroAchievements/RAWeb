import type { ColumnFiltersState } from '@tanstack/react-table';

import type { TicketListFilterProperty } from '../models';
import { getTicketListFilterValue } from './getTicketListFilterValue';

interface ActiveTicketListFilterProperty {
  property: TicketListFilterProperty;
  value: string;
}

export function getActiveTicketListFilterProperties(
  properties: TicketListFilterProperty[],
  columnFilters: ColumnFiltersState,
): ActiveTicketListFilterProperty[] {
  const active: ActiveTicketListFilterProperty[] = [];

  for (const property of properties) {
    const value = getTicketListFilterValue(columnFilters, property.id);

    if (value !== null && value !== property.noFilterValue) {
      active.push({ property, value });
    }
  }

  return active;
}
