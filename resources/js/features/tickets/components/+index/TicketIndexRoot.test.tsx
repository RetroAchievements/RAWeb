import userEvent from '@testing-library/user-event';
import axios from 'axios';

import { render, screen, waitFor } from '@/test';
import {
  createPaginatedData,
  createTicketListEntry,
  createTicketListStateCounts,
  createUser,
  createZiggyProps,
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
        ziggy: createZiggyProps({ query: {} }),
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
        ziggy: createZiggyProps({ query: {} }),
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

    expect(screen.getAllByRole('img', { name: 'Quarantined' })[0]).toBeVisible();
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
        ziggy: createZiggyProps({ query: {} }),
      },
    });

    // ASSERT
    expect(screen.getByText('No tickets match these filters.')).toBeVisible();
    expect(screen.queryByRole('table')).not.toBeInTheDocument();
  });

  it('given the user advances to the next page, fetches the next page from the API and syncs the URL', async () => {
    // ARRANGE
    const pushStateSpy = vi.spyOn(window.history, 'pushState').mockImplementation(() => {});

    const getSpy = vi.spyOn(axios, 'get').mockResolvedValue({
      data: {
        paginatedTickets: createPaginatedData([createTicketListEntry({ id: 2001 })], {
          currentPage: 2,
          lastPage: 3,
          perPage: 50,
          total: 150,
        }),
      },
    });

    render<App.Platform.Data.TicketListPageProps>(<TicketIndexRoot />, {
      pageProps: {
        scope: 'all',
        paginatedTickets: createPaginatedData([createTicketListEntry({ id: 1001 })], {
          currentPage: 1,
          lastPage: 3,
          perPage: 50,
          total: 150,
        }),
        stateCounts: createTicketListStateCounts(),
        availableFilters: [{ kind: 'type', values: ['0', '1', '2'] }],
        ziggy: createZiggyProps({ query: {} }),
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: 'Go to next page' }));

    // ASSERT
    await waitFor(() => {
      expect(getSpy).toHaveBeenCalledWith([
        'api.ticket.index',
        { scope: 'all', 'page[number]': 2 },
      ]);
    });

    await waitFor(() => {
      expect(getSpy).toHaveBeenCalledWith([
        'api.ticket.index',
        { scope: 'all', 'page[number]': 3 },
      ]);
    });

    await waitFor(() => {
      expect(screen.getByRole('link', { name: 'Ticket #2001' })).toBeVisible();
    });

    expect(pushStateSpy).toHaveBeenCalledWith(
      { inertia: true },
      '',
      expect.stringContaining('page%5Bnumber%5D=2'),
    );
  });

  it('given the user types a page number instead of clicking, the query fetches the page', async () => {
    // ARRANGE
    vi.spyOn(window.history, 'pushState').mockImplementation(() => {});

    const getSpy = vi.spyOn(axios, 'get').mockResolvedValue({
      data: {
        paginatedTickets: createPaginatedData([createTicketListEntry({ id: 3001 })], {
          currentPage: 3,
          lastPage: 3,
          perPage: 50,
          total: 150,
        }),
      },
    });

    render<App.Platform.Data.TicketListPageProps>(<TicketIndexRoot />, {
      pageProps: {
        scope: 'all',
        paginatedTickets: createPaginatedData([createTicketListEntry({ id: 1001 })], {
          currentPage: 1,
          lastPage: 3,
          perPage: 50,
          total: 150,
        }),
        stateCounts: createTicketListStateCounts(),
        availableFilters: [{ kind: 'type', values: ['0', '1', '2'] }],
        ziggy: createZiggyProps({ query: {} }),
      },
    });

    // ACT
    const inputEl = screen.getByRole('spinbutton', { name: 'current page number' });
    await userEvent.clear(inputEl);
    await userEvent.type(inputEl, '3');

    // ASSERT
    await waitFor(
      () => {
        expect(getSpy).toHaveBeenCalledWith([
          'api.ticket.index',
          { scope: 'all', 'page[number]': 3 },
        ]);
      },
      { timeout: 2000 },
    );

    await waitFor(() => {
      expect(screen.getByRole('link', { name: 'Ticket #3001' })).toBeVisible();
    });
  });

  it('given the URL has a filter and a sort, also sends those things to the API when the user paginates', async () => {
    // ARRANGE
    vi.spyOn(window.history, 'pushState').mockImplementation(() => {});

    const getSpy = vi.spyOn(axios, 'get').mockResolvedValue({
      data: {
        paginatedTickets: createPaginatedData([createTicketListEntry()], {
          currentPage: 2,
          lastPage: 3,
          perPage: 50,
          total: 150,
        }),
      },
    });

    render<App.Platform.Data.TicketListPageProps>(<TicketIndexRoot />, {
      pageProps: {
        scope: 'all',
        paginatedTickets: createPaginatedData([createTicketListEntry()], {
          currentPage: 1,
          lastPage: 3,
          perPage: 50,
          total: 150,
        }),
        stateCounts: createTicketListStateCounts(),
        availableFilters: [{ kind: 'type', values: ['0', '1', '2'] }],
        ziggy: createZiggyProps({ query: { filter: { status: 'resolved' }, sort: 'state' } }),
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: 'Go to next page' }));

    // ASSERT
    await waitFor(() => {
      expect(getSpy).toHaveBeenCalledWith([
        'api.ticket.index',
        {
          scope: 'all',
          sort: 'state',
          'filter[status]': 'resolved',
          'page[number]': 2,
        },
      ]);
    });
  });
});
