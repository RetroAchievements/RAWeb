import type { TicketListSortParam } from '../models';

const defaultParam: TicketListSortParam = '-createdAt';
const fields: readonly App.Platform.Enums.TicketListSortField[] = [
  'createdAt',
  'state',
  'resolvedAt',
];

export const ticketListSort = {
  defaultParam,
  fields,

  build(field: App.Platform.Enums.TicketListSortField, isAscending: boolean): TicketListSortParam {
    return isAscending ? field : `-${field}`;
  },

  field(sortParam: TicketListSortParam): App.Platform.Enums.TicketListSortField {
    return sortParam.replace(/^-/, '') as App.Platform.Enums.TicketListSortField;
  },

  isAscending(sortParam: TicketListSortParam): boolean {
    return !sortParam.startsWith('-');
  },

  resolve(value: unknown, fallback: TicketListSortParam = defaultParam): TicketListSortParam {
    const isKnown = fields.some((field) => value === field || value === `-${field}`);

    return isKnown ? (value as TicketListSortParam) : fallback;
  },
};
