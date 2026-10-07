import { route } from 'ziggy-js';

/**
 * Builds the href of the full ticket list for the ticket's achievement.
 *
 * The status filter is set to "all" because the list page defaults to open
 * tickets only, which would hide a closed ticket that links back to the list.
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
