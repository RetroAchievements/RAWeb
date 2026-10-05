export function getIsTicketStateOpen(ticketState: App.Community.Enums.TicketState): boolean {
  return ticketState === 'open' || ticketState === 'request';
}
