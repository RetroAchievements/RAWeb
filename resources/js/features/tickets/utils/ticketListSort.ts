import type { TicketListSortParam } from '../models';

export const ticketListSort = {
  build(field: App.Platform.Enums.TicketListSortField, isAscending: boolean): TicketListSortParam {
    return isAscending ? field : `-${field}`;
  },

  field(sortParam: TicketListSortParam): App.Platform.Enums.TicketListSortField {
    return sortParam.replace(/^-/, '') as App.Platform.Enums.TicketListSortField;
  },

  isAscending(sortParam: TicketListSortParam): boolean {
    return !sortParam.startsWith('-');
  },

  isGroupingField(field: App.Platform.Enums.TicketListSortField): boolean {
    return field === 'ticketable' || field === 'game';
  },

  resolve(
    value: unknown,
    fallback: TicketListSortParam,
    sortFields: App.Platform.Enums.TicketListSortField[],
  ): TicketListSortParam {
    const isKnown = sortFields.some((field) => value === field || value === `-${field}`);

    return isKnown ? (value as TicketListSortParam) : fallback;
  },
};
