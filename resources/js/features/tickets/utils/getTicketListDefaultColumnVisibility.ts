import type { VisibilityState } from '@tanstack/react-table';

import type { TicketListColumnId, TicketListSortParam } from '../models';
import { TICKET_LIST_COLUMN_IDS } from './ticketListColumnIds';
import { ticketListSort } from './ticketListSort';

export function getTicketListDefaultColumnVisibility(
  scope: App.Platform.Enums.TicketListScope,
  statusValue: App.Platform.Enums.TicketListStatusFilter,
  sortParam: TicketListSortParam = ticketListSort.defaultParam,
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
  all: ['id', 'ticketable', 'game', 'developer', 'reporter', 'age'],
  game: ['id', 'ticketable', 'developer', 'reporter', 'age'],
  achievement: ['id', 'type', 'reporter', 'age'],
  assignedTo: ['id', 'ticketable', 'game', 'reporter', 'age'],
  reportedBy: ['id', 'ticketable', 'game', 'developer', 'age'],
  awaitingReporter: ['id', 'ticketable', 'game', 'developer', 'age'],
  resolvedBy: ['id', 'ticketable', 'game', 'reporter', 'age', 'resolvedAt'],
};

const statusValuesShowingResolver: App.Platform.Enums.TicketListStatusFilter[] = [
  'all',
  'resolved',
  'closed',
];
