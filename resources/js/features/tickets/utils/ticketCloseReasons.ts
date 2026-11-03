import type { TicketCloseReason } from '../models';

export const TICKET_CLOSE_REASONS: TicketCloseReason[] = [
  'network',
  'not-enough-info',
  'wrong-rom',
  'unable-to-reproduce',
  'unable-to-debug',
  'emulator-problem',
  'already-fixed',
  'manual-unlock-request',
  'closed-mistaken',
  'closed-other',
];
