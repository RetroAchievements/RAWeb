import { route } from 'ziggy-js';

/**
 * Builds the href of the achievement's ticket list for a given ticket.
 *
 * The status filter defaults to "all" because the list page opens on open
 * tickets only, which would hide a closed ticket that links back to the list.
 * Callers that target one group of tickets, such as the open ones, pass that
 * group's filter instead.
 */
export function buildAchievementTicketListHref(
  ticket: App.Platform.Data.TicketListEntry,
  statusFilter: App.Platform.Enums.TicketListStatusFilter = 'all',
): string | null {
  // FIXME in a subsequent change -- leaderboards don't have a ticket list of their own yet
  if (ticket.ticketableType !== 'achievement') {
    return null;
  }

  return route('achievement.tickets', {
    achievement: ticket.ticketableId,
    filter: { status: statusFilter },
  });
}
