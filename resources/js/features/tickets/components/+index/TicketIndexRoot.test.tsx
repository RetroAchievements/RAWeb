import { render, screen } from '@/test';
import {
  createPaginatedData,
  createTicketListEntry,
  createTicketListStateCounts,
  createUser,
} from '@/test/factories';

import { TicketIndexRoot } from './TicketIndexRoot';

describe('Component: TicketIndexRoot', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render<App.Platform.Data.TicketListPageProps>(<TicketIndexRoot />, {
      pageProps: {
        scope: 'all',
        paginatedTickets: createPaginatedData([createTicketListEntry()], {
          currentPage: 1,
          lastPage: 1,
          perPage: 50,
          total: 1,
        }),
        stateCounts: createTicketListStateCounts(),
        availableFilters: [{ kind: 'type', values: ['0', '1', '2'] }],
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
    expect(screen.getByTestId('ticket-list')).toBeVisible();

    expect(screen.getByRole('heading', { level: 1, name: 'Ticket Manager' })).toBeVisible();
  });

  it('renders every default column and one row per ticket from the page props', () => {
    // ARRANGE
    const tickets = [
      createTicketListEntry({
        id: 1001,
        state: 'open',
        ticketableTitle: 'First Blood',
        author: createUser({ displayName: 'Dev' }),
        reporter: createUser({ displayName: 'Scott' }),
      }),
      createTicketListEntry({ id: 1002, ticketableTitle: 'Second Wind', state: 'quarantined' }),
    ];

    render<App.Platform.Data.TicketListPageProps>(<TicketIndexRoot />, {
      pageProps: {
        scope: 'all',
        paginatedTickets: createPaginatedData(tickets, {
          currentPage: 1,
          lastPage: 1,
          perPage: 50,
          total: tickets.length,
        }),
        stateCounts: createTicketListStateCounts(),
        availableFilters: [{ kind: 'type', values: ['0', '1', '2'] }],
      },
    });

    // ASSERT
    expect(screen.getAllByRole('columnheader').map((header) => header.textContent)).toEqual([
      'ID',
      'Issue with',
      'Game',
      'Developer',
      'Reporter',
      'Age',
    ]);

    expect(screen.getAllByRole('row')).toHaveLength(3);

    expect(screen.getByRole('link', { name: 'Ticket #1001' })).toHaveAttribute(
      'href',
      expect.stringContaining('ticket.show'),
    );
    expect(screen.getByRole('link', { name: 'Ticket #1002' })).toBeVisible();

    expect(screen.getByRole('link', { name: 'First Blood' })).toHaveAttribute(
      'href',
      expect.stringContaining('achievement.show'),
    );
    expect(screen.getByRole('link', { name: 'Second Wind' })).toBeVisible();

    expect(screen.getByRole('link', { name: /dev/i })).toHaveAttribute(
      'href',
      expect.stringContaining('user.show'),
    );
    expect(screen.getByRole('link', { name: /scott/i })).toBeVisible();

    expect(screen.getByRole('img', { name: 'Quarantined' })).toBeVisible();
  });

  it('given the page has no tickets, shows the empty state instead of a table', () => {
    // ARRANGE
    render<App.Platform.Data.TicketListPageProps>(<TicketIndexRoot />, {
      pageProps: {
        scope: 'all',
        paginatedTickets: createPaginatedData([], {
          currentPage: 1,
          lastPage: 1,
          perPage: 50,
          total: 0,
        }),
        stateCounts: createTicketListStateCounts(),
        availableFilters: [{ kind: 'type', values: ['0', '1', '2'] }],
      },
    });

    // ASSERT
    expect(screen.getByText('No tickets match these filters.')).toBeVisible();
    expect(screen.queryByRole('table')).not.toBeInTheDocument();
  });
});
