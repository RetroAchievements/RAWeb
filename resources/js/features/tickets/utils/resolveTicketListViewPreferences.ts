import type { VisibilityState } from '@tanstack/react-table';

import type { TicketListSortParam, TicketListViewPreferences } from '../models';
import { resolveTicketListGroupField } from './resolveTicketListGroupField';
import { TICKET_LIST_COLUMN_IDS } from './ticketListColumnIds';
import { ticketListSort } from './ticketListSort';

export function resolveTicketListViewPreferences(
  preferences: Partial<TicketListViewPreferences> | null,
  serverDefaultSortParam: TicketListSortParam,
  groupFields: App.Platform.Enums.TicketListGroupField[],
): TicketListViewPreferences {
  const rawColumnVisibility = preferences?.columnVisibility ?? {};

  const columnVisibility = Object.fromEntries(
    TICKET_LIST_COLUMN_IDS.flatMap((columnId) =>
      columnId !== 'id' && typeof rawColumnVisibility[columnId] === 'boolean'
        ? [[columnId, rawColumnVisibility[columnId]]]
        : [],
    ),
  ) as VisibilityState;

  return {
    columnVisibility,
    groupField: resolveTicketListGroupField(preferences?.groupField, 'none', groupFields),
    sortParam: ticketListSort.resolve(preferences?.sortParam, serverDefaultSortParam),
  };
}
