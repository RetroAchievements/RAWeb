import type { TranslatedString } from '@/types/i18next';

import type { TicketListFilterPropertyOption } from './ticket-list-filter-property-option.model';

export interface TicketListFilterProperty {
  id: string;
  label: string;
  noFilterValue: string;
  options: TicketListFilterPropertyOption[];

  isFreeText?: boolean;
  placeholder?: TranslatedString;
}
