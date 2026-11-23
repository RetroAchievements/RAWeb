import type { ColumnFiltersState, VisibilityState } from '@tanstack/react-table';
import { useEffect, useRef } from 'react';
import { useCookie, useUpdateEffect } from 'react-use';

import { usePageProps } from '@/common/hooks/usePageProps';

import type { TicketListSortParam, TicketListUrlState } from '../models';
import { readTicketListSearchParams } from '../utils/readTicketListSearchParams';
import { resolveInitialTicketListColumnFilters } from '../utils/resolveInitialTicketListColumnFilters';
import { resolveTicketListGroupField } from '../utils/resolveTicketListGroupField';
import { serializeTicketListSearchParams } from '../utils/serializeTicketListSearchParams';
import { ticketListSort } from '../utils/ticketListSort';

interface TicketListHistoryState {
  ticketListGroupField?: unknown;
  ticketListSortParam?: unknown;
}

interface UseTicketListTableSyncProps {
  columnFilters: ColumnFiltersState;
  columnVisibilityOverrides: VisibilityState;
  groupField: App.Platform.Enums.TicketListGroupField;
  serverDefaultColumnFilters: ColumnFiltersState;
  serverDefaultSortParam: TicketListSortParam;
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
  groupField,
  serverDefaultColumnFilters,
  serverDefaultSortParam,
  pageNumber,
  restoreState,
  sortParam,
}: UseTicketListTableSyncProps) {
  const { groupFields, persistenceCookieName } =
    usePageProps<
      Pick<App.Platform.Data.TicketListPageProps, 'groupFields' | 'persistenceCookieName'>
    >();

  const [, setCookie] = useCookie(persistenceCookieName);

  const restoredUrlStateRef = useRef<TicketListUrlState | null>(null);
  const initialSortParamRef = useRef(sortParam);
  const initialGroupFieldRef = useRef(groupField);

  useEffect(() => {
    window.history.replaceState(
      {
        ...window.history.state,
        ticketListGroupField: initialGroupFieldRef.current,
        ticketListSortParam: initialSortParamRef.current,
      },
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
        groupField: resolveTicketListGroupField(
          historyState?.ticketListGroupField,
          resolveTicketListGroupField(restored.group, 'none', groupFields),
          groupFields,
        ),
        pageNumber: restored.pageNumber,
        sortParam: ticketListSort.resolve(
          historyState?.ticketListSortParam,
          ticketListSort.resolve(restored.sort, serverDefaultSortParam),
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
        groupField,
        sortParam,
      }),
      { expires: 180 },
    );
  }, [columnVisibilityOverrides, groupField, sortParam]);

  useUpdateEffect(() => {
    const restoredUrlState = restoredUrlStateRef.current;
    restoredUrlStateRef.current = null;

    if (
      restoredUrlState &&
      restoredUrlState.columnFilters === columnFilters &&
      restoredUrlState.groupField === groupField &&
      restoredUrlState.pageNumber === pageNumber &&
      restoredUrlState.sortParam === sortParam
    ) {
      return;
    }

    const searchParams = serializeTicketListSearchParams({
      columnFilters,
      groupField,
      pageNumber,
      serverDefaultColumnFilters,
      serverDefaultSortParam,
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

    window.history.pushState(
      { inertia: true, ticketListGroupField: groupField, ticketListSortParam: sortParam },
      '',
      newUrl,
    );
  }, [columnFilters, groupField, pageNumber, sortParam]);
}
