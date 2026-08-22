import type { ColumnFiltersState } from '@tanstack/react-table';
import { useEffect, useRef } from 'react';
import { useUpdateEffect } from 'react-use';

import { readTicketListSearchParams } from '../utils/readTicketListSearchParams';
import { resolveInitialTicketListColumnFilters } from '../utils/resolveInitialTicketListColumnFilters';
import { serializeTicketListSearchParams } from '../utils/serializeTicketListSearchParams';

// TODO user's persistence cookie support

interface UseTicketListTableSyncProps {
  columnFilters: ColumnFiltersState;
  serverDefaultColumnFilters: ColumnFiltersState;
  pageNumber: number;
  restoreState: (columnFilters: ColumnFiltersState, pageNumber: number) => void;
}

/**
 * Keeps the URL in step with the table, so a refresh or a shared link
 * lands on the same view.
 */
export function useTicketListTableSync({
  columnFilters,
  serverDefaultColumnFilters,
  pageNumber,
  restoreState,
}: UseTicketListTableSyncProps) {
  const restoredStateRef = useRef<{
    columnFilters: ColumnFiltersState;
    pageNumber: number;
  } | null>(null);

  useEffect(() => {
    const handlePopState = () => {
      const restored = readTicketListSearchParams(window.location.search);
      const restoredColumnFilters = resolveInitialTicketListColumnFilters(
        restored.query,
        serverDefaultColumnFilters,
      );

      restoredStateRef.current = {
        columnFilters: restoredColumnFilters,
        pageNumber: restored.pageNumber,
      };
      restoreState(restoredColumnFilters, restored.pageNumber);
    };

    window.addEventListener('popstate', handlePopState);

    return () => window.removeEventListener('popstate', handlePopState);
  });

  useUpdateEffect(() => {
    const restoredState = restoredStateRef.current;
    restoredStateRef.current = null;

    if (
      restoredState &&
      restoredState.columnFilters === columnFilters &&
      restoredState.pageNumber === pageNumber
    ) {
      return;
    }

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
