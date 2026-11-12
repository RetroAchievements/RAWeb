import type { TranslationKey } from '@/types/i18next';

/**
 * Reason labels shown to developers inside the close dialog.
 */
export const TICKET_CLOSE_REASON_LABELS = {
  resolved: 'I fixed it',
  'already-fixed': 'It was already fixed',
  demoted: "I'm demoting it",
  'closed-mistaken': 'Player error or works as designed',
  'emulator-problem': 'Emulator or core problem',
  'wrong-rom': 'Wrong ROM',
  'closed-other': 'Something else',
  'unable-to-reproduce': 'Unable to reproduce',
  network: 'Network problems',
  'not-enough-info': 'Not enough information',
  'unable-to-debug': 'Unable to debug (no toolkit support)',
  'covered-by-another-ticket': 'Covered by another ticket',
} as const satisfies Partial<Record<App.Community.Enums.TicketAction, TranslationKey>>;

export type TicketCloseAction = keyof typeof TICKET_CLOSE_REASON_LABELS;
