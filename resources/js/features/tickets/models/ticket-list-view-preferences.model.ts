import type { VisibilityState } from '@tanstack/react-table';

import type { TicketListSortParam } from './ticket-list-sort-param.model';

export interface TicketListViewPreferences {
  columnVisibility: VisibilityState;
  sortParam: TicketListSortParam;
}
