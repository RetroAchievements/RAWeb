export function getIsTicketStateFinished(ticketState: App.Community.Enums.TicketState): boolean {
  return ticketState === 'resolved' || ticketState === 'closed';
}
