import { HydrationBoundary } from '@tanstack/react-query';
import type { FC } from 'react';

import { DataTablePaginationControls } from '@/common/components/DataTablePaginationControls';
import { usePageProps } from '@/common/hooks/usePageProps';

import { useTicketListColumnDefinitions } from '../../hooks/useTicketListColumnDefinitions';
import { useTicketListTableRoot } from '../../hooks/useTicketListTableRoot';
import { TICKET_LIST_COLUMN_IDS } from '../../utils/ticketListColumnIds';
import { TicketListEmptyState } from '../TicketListEmptyState';
import { TicketListHeading } from '../TicketListHeading';
import { TicketListTable } from '../TicketListTable';

// temporary - selectable columns will arrive in a future commit
const columnVisibility = Object.fromEntries(TICKET_LIST_COLUMN_IDS.map((id) => [id, true]));

export const TicketIndexRoot: FC = () => {
  const { paginatedTickets, scope } = usePageProps<App.Platform.Data.TicketListPageProps>();

  const columnDefinitions = useTicketListColumnDefinitions();

  const { hydrationState, ticketListTableProps } = useTicketListTableRoot({
    paginatedTickets,
    scope,
  });

  return (
    <div
      id="pagination-scroll-target"
      data-testid="ticket-list"
      className="flex scroll-mt-16 flex-col gap-4"
    >
      <TicketListHeading />

      <HydrationBoundary state={hydrationState}>
        <TicketListTable
          columnDefinitions={columnDefinitions}
          columnVisibility={columnVisibility}
          emptyStateNode={<TicketListEmptyState />}
          isFetching={ticketListTableProps.isFetching}
          paginatedTickets={ticketListTableProps.paginatedTickets}
          paginatorNode={
            <div className="flex items-center justify-center sm:justify-end">
              <DataTablePaginationControls
                currentPage={ticketListTableProps.paginatedTickets.currentPage}
                lastPage={ticketListTableProps.paginatedTickets.lastPage}
                onPageChange={ticketListTableProps.setPageNumber}
                onPrefetchPage={ticketListTableProps.prefetchPage}
              />
            </div>
          }
        />
      </HydrationBoundary>
    </div>
  );
};
