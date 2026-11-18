export function shouldTicketTypeOfferManualUnlock(
  ticketType: App.Community.Enums.TicketType,
): boolean {
  return ticketType === 'did_not_trigger';
}
