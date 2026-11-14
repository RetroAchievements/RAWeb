import type { TicketCloseAnswerGroup } from '../models';

export const TICKET_CLOSE_ANSWER_GROUPS: TicketCloseAnswerGroup[] = [
  { answer: 'yes', actions: ['resolved', 'already-fixed', 'demoted'] },
  {
    answer: 'no',
    actions: ['closed-mistaken', 'emulator-problem', 'wrong-rom', 'network', 'closed-other'],
  },
  {
    answer: 'cant-tell',
    actions: ['unable-to-reproduce', 'not-enough-info', 'unable-to-debug', 'closed-other'],
  },
  { answer: 'covered', actions: ['covered-by-another-ticket'] },
];
