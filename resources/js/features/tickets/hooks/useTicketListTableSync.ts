import type { ColumnFiltersState } from '@tanstack/react-table';
import { useUpdateEffect } from 'react-use';

import { serializeTicketListSearchParams } from '../utils/serializeTicketListSearchParams';

// TODO user's persistence cookie support

interface UseTicketListTableSyncProps {
  columnFilters: ColumnFiltersState;
  serverDefaultColumnFilters: ColumnFiltersState;
  pageNumber: number;
}

/**
 * Keeps the URL in step with the table, so a refresh or a shared link
 * lands on the same view.
 */
export function useTicketListTableSync({
  columnFilters,
  serverDefaultColumnFilters,
  pageNumber,
}: UseTicketListTableSyncProps) {
  useUpdateEffect(() => {
    const searchParams = serializeTicketListSearchParams({
      columnFilters,
      serverDefaultColumnFilters,
      pageNumber,
      currentSearch: window.location.search,
    });

    const newUrl = Array.from(searchParams).length
      ? `${window.location.pathname}?${searchParams.toString()}`
      : window.location.pathname;

    const currentUrl = `${window.location.pathname}${window.location.search}`;

    if (newUrl === currentUrl) {
      return;
    }

    window.history.pushState({ inertia: true }, '', newUrl);
  }, [columnFilters, pageNumber]);
}
