import type { AppGlobalProps } from '@/common/models';

interface TicketListSearchParams {
  pageNumber: number;
  query: AppGlobalProps['ziggy']['query'];
}

export function readTicketListSearchParams(search: string): TicketListSearchParams {
  const searchParams = new URLSearchParams(search);

  const filter: Record<string, string> = {};
  for (const [paramKey, paramValue] of searchParams) {
    const filterKind = paramKey.match(/^filter\[(.+)\]$/)?.[1];
    if (filterKind) {
      filter[filterKind] = paramValue;
    }
  }

  return {
    pageNumber: Number(searchParams.get('page[number]') ?? 1),
    query: { filter },
  };
}
