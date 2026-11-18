import type { TicketCloseAction } from '../utils/ticketCloseReasonLabels';
import type { TicketCloseAnswer } from './ticket-close-answer.model';

export interface TicketCloseAnswerGroup {
  answer: TicketCloseAnswer;
  actions: TicketCloseAction[];
}
