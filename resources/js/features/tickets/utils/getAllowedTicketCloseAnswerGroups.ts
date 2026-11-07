import type { TicketCloseAnswerGroup } from '../models';
import { TICKET_CLOSE_ANSWER_GROUPS } from './ticketCloseAnswerGroups';

export function getAllowedTicketCloseAnswerGroups(
  allowedTicketActions: App.Community.Enums.TicketAction[],
): TicketCloseAnswerGroup[] {
  return TICKET_CLOSE_ANSWER_GROUPS.map((group) => ({
    ...group,
    actions: group.actions.filter((action) => allowedTicketActions.includes(action)),
  })).filter((remainingGroup) => remainingGroup.actions.length > 0);
}
