import { getTicketListGroupKey } from './getTicketListGroupKey';

export interface TicketListGroupHeader {
  isContinued: boolean;
  ticketCount: number;
}

export function getTicketListGroupHeaders(
  grouping: App.Platform.Data.TicketListGrouping | null | undefined,
  entries: App.Platform.Data.TicketListEntry[],
): Array<TicketListGroupHeader | null> {
  if (!grouping) {
    return entries.map(() => null);
  }

  return entries.map((entry, index) => {
    const groupKey = getTicketListGroupKey(grouping.field, entry);

    if (index > 0 && groupKey === getTicketListGroupKey(grouping.field, entries[index - 1])) {
      return null;
    }

    return {
      isContinued: index === 0 && grouping.isFirstGroupContinued,
      ticketCount: grouping.counts[groupKey],
    };
  });
}
