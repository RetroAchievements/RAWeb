import type { ColumnFiltersState } from '@tanstack/react-table';

import type { TicketListSortParam } from './ticket-list-sort-param.model';

export interface TicketListUrlState {
  columnFilters: ColumnFiltersState;
  groupField: App.Platform.Enums.TicketListGroupField;
  pageNumber: number;
  sortParam: TicketListSortParam;
}
