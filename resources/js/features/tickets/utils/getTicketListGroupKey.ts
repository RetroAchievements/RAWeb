/**
 * Matches the keys of the server's group counts. A game groups by its ID, and an
 * achievement or leaderboard groups by {type}+{ID}.
 */
export function getTicketListGroupKey(
  field: App.Platform.Enums.TicketListGroupField,
  entry: App.Platform.Data.TicketListEntry,
): string {
  if (field === 'game') {
    return String(entry.game.id);
  }

  return `${entry.ticketableType}:${entry.ticketableId}`;
}
