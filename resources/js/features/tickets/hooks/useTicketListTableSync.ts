import type { ColumnFiltersState, VisibilityState } from '@tanstack/react-table';
import { useEffect, useRef } from 'react';
import { useCookie, useUpdateEffect } from 'react-use';

import { usePageProps } from '@/common/hooks/usePageProps';

import type { TicketListSortParam, TicketListUrlState } from '../models';
import { readTicketListSearchParams } from '../utils/readTicketListSearchParams';
import { resolveInitialTicketListColumnFilters } from '../utils/resolveInitialTicketListColumnFilters';
import { serializeTicketListSearchParams } from '../utils/serializeTicketListSearchParams';
import { ticketListSort } from '../utils/ticketListSort';

interface TicketListHistoryState {
  ticketListSortParam?: unknown;
}

interface UseTicketListTableSyncProps {
  columnFilters: ColumnFiltersState;
  columnVisibilityOverrides: VisibilityState;
  serverDefaultColumnFilters: ColumnFiltersState;
  pageNumber: number;
  restoreState: (urlState: TicketListUrlState) => void;
  sortParam: TicketListSortParam;
}

/**
 * Keeps the URL in step with the table, so a refresh or a shared link
 * lands on the same view.
 */
export function useTicketListTableSync({
  columnFilters,
  columnVisibilityOverrides,
  serverDefaultColumnFilters,
  pageNumber,
  restoreState,
  sortParam,
}: UseTicketListTableSyncProps) {
  const { persistenceCookieName } =
    usePageProps<Pick<App.Platform.Data.TicketListPageProps, 'persistenceCookieName'>>();
  const [, setCookie] = useCookie(persistenceCookieName);
  const restoredUrlStateRef = useRef<TicketListUrlState | null>(null);
  const initialSortParamRef = useRef(sortParam);

  useEffect(() => {
    window.history.replaceState(
      { ...window.history.state, ticketListSortParam: initialSortParamRef.current },
      '',
    );
  }, []);

  useEffect(() => {
    const handlePopState = (event: PopStateEvent) => {
      const restored = readTicketListSearchParams(window.location.search);
      const historyState = event.state as TicketListHistoryState | null;

      const urlState: TicketListUrlState = {
        columnFilters: resolveInitialTicketListColumnFilters(
          restored.query,
          serverDefaultColumnFilters,
        ),
        pageNumber: restored.pageNumber,
        sortParam: ticketListSort.resolve(
          historyState?.ticketListSortParam,
          ticketListSort.resolve(restored.sort),
        ),
      };

      restoredUrlStateRef.current = urlState;
      restoreState(urlState);
    };

    window.addEventListener('popstate', handlePopState);

    return () => window.removeEventListener('popstate', handlePopState);
  });

  useUpdateEffect(() => {
    setCookie(
      JSON.stringify({
        columnVisibility: columnVisibilityOverrides,
        sortParam,
      }),
      { expires: 180 },
    );
  }, [columnVisibilityOverrides, sortParam]);

  useUpdateEffect(() => {
    const restoredUrlState = restoredUrlStateRef.current;
    restoredUrlStateRef.current = null;

    if (
      restoredUrlState &&
      restoredUrlState.columnFilters === columnFilters &&
      restoredUrlState.pageNumber === pageNumber &&
      restoredUrlState.sortParam === sortParam
    ) {
      return;
    }

    const searchParams = serializeTicketListSearchParams({
      columnFilters,
      serverDefaultColumnFilters,
      pageNumber,
      sortParam,
      currentSearch: window.location.search,
    });

    const newUrl = Array.from(searchParams).length
      ? `${window.location.pathname}?${searchParams.toString()}`
      : window.location.pathname;

    const currentUrl = `${window.location.pathname}${window.location.search}`;

    if (newUrl === currentUrl) {
      return;
    }

    window.history.pushState({ inertia: true, ticketListSortParam: sortParam }, '', newUrl);
  }, [columnFilters, pageNumber, sortParam]);
}
