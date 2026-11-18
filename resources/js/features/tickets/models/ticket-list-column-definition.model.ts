import type { ColumnDef } from '@tanstack/react-table';

import type { TicketListColumnId } from './ticket-list-column-id.model';

export type TicketListColumnDefinition = ColumnDef<App.Platform.Data.TicketListEntry> & {
  id: TicketListColumnId;
  meta: NonNullable<ColumnDef<App.Platform.Data.TicketListEntry>['meta']>;
};
