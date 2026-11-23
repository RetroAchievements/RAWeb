export function resolveTicketListGroupField(
  value: unknown,
  fallback: App.Platform.Enums.TicketListGroupField,
  groupFields: App.Platform.Enums.TicketListGroupField[],
): App.Platform.Enums.TicketListGroupField {
  const isKnown = value === 'none' || groupFields.some((groupField) => groupField === value);

  return isKnown ? (value as App.Platform.Enums.TicketListGroupField) : fallback;
}
