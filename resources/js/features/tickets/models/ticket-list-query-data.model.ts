export interface TicketListQueryData {
  facetCounts: Record<string, Record<string, number>>;
  paginatedTickets: App.Data.PaginatedData<App.Platform.Data.TicketListEntry>;
  stateCounts: App.Platform.Data.TicketListStateCounts;
}
