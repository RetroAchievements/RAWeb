import type { TicketListFilterPropertyOption } from './ticket-list-filter-property-option.model';

export interface TicketListFilterProperty {
  id: string;
  label: string;
  noFilterValue: string;
  options: TicketListFilterPropertyOption[];
}
