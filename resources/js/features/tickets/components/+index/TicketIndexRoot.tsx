import type { FC } from 'react';

import { usePageProps } from '@/common/hooks/usePageProps';

import { useTicketListColumnDefinitions } from '../../hooks/useTicketListColumnDefinitions';
import { TICKET_LIST_COLUMN_IDS } from '../../utils/ticketListColumnIds';
import { TicketListEmptyState } from '../TicketListEmptyState';
import { TicketListHeading } from '../TicketListHeading';
import { TicketListTable } from '../TicketListTable';

// temporary - selectable columns will arrive in a future commit
const columnVisibility = Object.fromEntries(TICKET_LIST_COLUMN_IDS.map((id) => [id, true]));

export const TicketIndexRoot: FC = () => {
  const { paginatedTickets } = usePageProps<App.Platform.Data.TicketListPageProps>();

  const columnDefinitions = useTicketListColumnDefinitions();

  return (
    <div data-testid="ticket-list" className="flex flex-col gap-4">
      <TicketListHeading />

      <TicketListTable
        columnDefinitions={columnDefinitions}
        columnVisibility={columnVisibility}
        emptyStateNode={<TicketListEmptyState />}
        paginatedTickets={paginatedTickets}
      />
    </div>
  );
};
