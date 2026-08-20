export const TICKET_LIST_COLUMN_IDS = [
  'id',
  'ticketable',
  'game',
  'developer',
  'reporter',
  'age',
] as const;

export type TicketListColumnId = (typeof TICKET_LIST_COLUMN_IDS)[number];
