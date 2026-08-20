import type { AppGlobalProps } from '@/common/models';

// temporary - this will be deleted
// there aren't any client-side controls for filtering and sorting
// if we don't have this temporary code, pagination will discard manually
// provided filter+sort params on the url

export function buildTicketListPassthroughParams(
  query: AppGlobalProps['ziggy']['query'],
): Record<string, string> {
  const params: Record<string, string> = {};

  if (typeof query.sort === 'string' && query.sort.length) {
    params.sort = query.sort;
  }

  const filterQuery = query.filter;
  if (filterQuery && typeof filterQuery === 'object') {
    for (const [filterKey, filterValue] of Object.entries(filterQuery)) {
      if (typeof filterValue === 'string' && filterValue.length) {
        params[`filter[${filterKey}]`] = filterValue;
      }
    }
  }

  return params;
}
