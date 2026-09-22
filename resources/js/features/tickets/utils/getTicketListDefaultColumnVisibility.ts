import type { VisibilityState } from '@tanstack/react-table';

import type { TicketListColumnId, TicketListSortParam } from '../models';
import { TICKET_LIST_COLUMN_IDS } from './ticketListColumnIds';
import { ticketListSort } from './ticketListSort';

export function getTicketListDefaultColumnVisibility(
  scope: App.Platform.Enums.TicketListScope,
  statusValue: App.Platform.Enums.TicketListStatusFilter,
  sortParam: TicketListSortParam,
): VisibilityState {
  const visibleColumnIds = new Set<TicketListColumnId>(visibleColumnIdsByScope[scope]);

  if (scope !== 'resolvedBy' && statusValuesShowingResolver.includes(statusValue)) {
    visibleColumnIds.add('resolver');
    visibleColumnIds.add('resolvedAt');
  }

  if (ticketListSort.field(sortParam) === 'resolvedAt') {
    visibleColumnIds.add('resolvedAt');
  }

  return Object.fromEntries(
    TICKET_LIST_COLUMN_IDS.map((columnId) => [columnId, visibleColumnIds.has(columnId)]),
  );
}

const visibleColumnIdsByScope: Record<
  App.Platform.Enums.TicketListScope,
  readonly TicketListColumnId[]
> = {
  all: ['id', 'ticketable', 'developer', 'reporter', 'age'],
  game: ['id', 'ticketable', 'developer', 'reporter', 'age'],
  achievement: ['id', 'type', 'reporter', 'age'],
  assignedTo: ['id', 'ticketable', 'reporter', 'age'],
  reportedBy: ['id', 'ticketable', 'developer', 'age'],
  awaitingReporter: ['id', 'ticketable', 'developer', 'age'],
  resolvedBy: ['id', 'ticketable', 'reporter', 'age', 'resolvedAt'],
};

const statusValuesShowingResolver: App.Platform.Enums.TicketListStatusFilter[] = [
  'all',
  'resolved',
  'closed',
];
