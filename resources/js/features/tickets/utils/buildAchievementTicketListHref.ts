import { route } from 'ziggy-js';

/**
 * Builds the href of the achievement's ticket list for a given ticket.
 *
 * The status filter is "all" because the list page opens on open tickets
 * only, which would hide a closed ticket that links back to the list.
 */
export function buildAchievementTicketListHref(
  ticket: App.Platform.Data.TicketListEntry,
): string | null {
  // FIXME in a subsequent change -- leaderboards don't have a ticket list of their own yet
  if (ticket.ticketableType !== 'achievement') {
    return null;
  }

  return route('achievement.tickets', {
    achievement: ticket.ticketableId,
    filter: { status: 'all' },
  });
}
