export function normalizeTicketListFilterValue(value: unknown): string | null {
  const flatValue = Array.isArray(value) ? value.join(',') : value;

  if (flatValue === null || flatValue === undefined || flatValue === '') {
    return null;
  }

  return String(flatValue);
}
