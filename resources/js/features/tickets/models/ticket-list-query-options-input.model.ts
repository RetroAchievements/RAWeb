import type { ColumnFiltersState } from '@tanstack/react-table';

import type { TicketListSortParam } from './ticket-list-sort-param.model';

export interface TicketListQueryOptionsInput {
  columnFilters: ColumnFiltersState;
  pageNumber: number;
  scope: App.Platform.Enums.TicketListScope;
  sortParam: TicketListSortParam;
}
