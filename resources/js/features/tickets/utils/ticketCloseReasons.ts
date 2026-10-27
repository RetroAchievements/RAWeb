import type { TicketCloseReason } from '../models';

export const TICKET_CLOSE_REASONS: TicketCloseReason[] = [
  'network',
  'not-enough-info',
  'wrong-rom',
  'unable-to-reproduce',
  'unable-to-debug',
  'closed-mistaken',
  'closed-other',
];
