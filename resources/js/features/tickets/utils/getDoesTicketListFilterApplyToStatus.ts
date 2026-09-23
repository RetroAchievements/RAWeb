const RESOLUTION_STATUS_VALUES: App.Platform.Enums.TicketListStatusFilter[] = ['all', 'closed'];

export function getDoesTicketListFilterApplyToStatus(
  kind: App.Platform.Enums.TicketListFilterKind,
  statusValue: App.Platform.Enums.TicketListStatusFilter,
): boolean {
  return kind !== 'resolution' || RESOLUTION_STATUS_VALUES.includes(statusValue);
}
