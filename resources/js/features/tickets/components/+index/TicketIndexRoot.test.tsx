import userEvent from '@testing-library/user-event';
import axios from 'axios';

import { act, render, screen, waitFor } from '@/test';
import {
  createGame,
  createPaginatedData,
  createSystem,
  createTicketListEntry,
  createTicketListStateCounts,
  createUser,
  createZiggyProps,
} from '@/test/factories';

import { openPropertySubmenu } from '../../test/openPropertySubmenu';
import { TicketIndexRoot } from './TicketIndexRoot';

type TicketIndexRenderProps = Partial<App.Platform.Data.TicketListPageProps> & {
  ziggy?: ReturnType<typeof createZiggyProps>;
};

function renderTicketIndexRoot(pageProps: TicketIndexRenderProps = {}) {
  return render<App.Platform.Data.TicketListPageProps>(<TicketIndexRoot />, {
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
      facetCounts: {},
      ziggy: createZiggyProps({ query: {} }),
      ...pageProps,
    },
  });
}

type TicketListResponse = Pick<
  App.Platform.Data.TicketListPageProps,
  'paginatedTickets' | 'stateCounts' | 'facetCounts'
>;

function createTicketListResponse(
  paginatedTickets: TicketListResponse['paginatedTickets'],
  overrides: Partial<Omit<TicketListResponse, 'paginatedTickets'>> = {},
): TicketListResponse {
  return {
    paginatedTickets,
    stateCounts: createTicketListStateCounts(),
    facetCounts: {},
    ...overrides,
  };
}

describe('Component: TicketIndexRoot', () => {
  it('shows the ticket manager list', () => {
    // ARRANGE
    renderTicketIndexRoot();

    // ASSERT
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
        game: createGame({ title: 'Sonic the Hedgehog', system: createSystem() }),
        author: createUser({ displayName: 'Dev' }),
        reporter: createUser({ displayName: 'Scott' }),
      }),
      createTicketListEntry({
        id: 1002,
        state: 'quarantined',
        ticketableTitle: 'Second Wind',
        game: createGame({ title: 'Streets of Rage', system: createSystem() }),
        author: createUser({ displayName: 'Kiterra' }),
        reporter: createUser({ displayName: 'Salsa' }),
      }),
    ];

    renderTicketIndexRoot({
      paginatedTickets: createPaginatedData(tickets, {
        currentPage: 1,
        lastPage: 1,
        perPage: 50,
        total: tickets.length,
        unfilteredTotal: 300,
      }),
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

    expect(screen.getByText('2 of 300 tickets')).toBeVisible();
  });

  it('given the page has no tickets, shows the empty state instead of a table', () => {
    // ARRANGE
    renderTicketIndexRoot({
      paginatedTickets: createPaginatedData([], {
        currentPage: 1,
        lastPage: 1,
        perPage: 50,
        total: 0,
      }),
    });

    // ASSERT
    expect(screen.getByText('No tickets match these filters.')).toBeVisible();
    expect(screen.queryByRole('table')).not.toBeInTheDocument();
  });

  it('given the scope exposes filters, offers them all behind one filter button', async () => {
    // ARRANGE
    renderTicketIndexRoot({
      stateCounts: createTicketListStateCounts({ unresolved: 7, resolved: 3 }),
      availableFilters: [
        { kind: 'type', values: ['0', '1', '2'] },
        { kind: 'mode', values: ['all', 'hardcore', 'softcore'] },
      ],
    });

    // ACT
    await userEvent.click(screen.getByTestId('add-filter'));

    // ASSERT
    expect(screen.getByTestId('filter-property-status')).toBeVisible();
    expect(screen.getByTestId('filter-property-type')).toBeVisible();
    expect(screen.getByTestId('filter-property-mode')).toBeVisible();
  });

  it('given the server counted a facet, every one of its options carries a count', async () => {
    // ARRANGE
    renderTicketIndexRoot({
      availableFilters: [{ kind: 'emulator', values: ['all', 'RetroArch', 'unknown'] }],
      facetCounts: { emulator: { all: 100, RetroArch: 40 } },
    });

    // ACT
    await openPropertySubmenu(1);

    // ASSERT
    expect(screen.getByRole('menuitem', { name: /^RetroArch/ })).toHaveTextContent('40');
    expect(screen.getByRole('menuitem', { name: /^all/i })).toHaveTextContent('100');
    expect(screen.getByRole('menuitem', { name: /^Unknown/ })).toHaveTextContent('0');
  });

  it('omits counts when the server does not provide them', async () => {
    // ARRANGE
    renderTicketIndexRoot({
      availableFilters: [{ kind: 'developerType', values: ['all', 'active', 'junior'] }],
    });

    // ACT
    await openPropertySubmenu(1);

    // ASSERT
    expect(screen.getByRole('menuitem', { name: 'Active' })).toHaveTextContent(/^Active$/);
  });

  it('given the default status narrows the list, shows it as a chip with the current value', () => {
    // ARRANGE
    renderTicketIndexRoot();

    // ASSERT
    expect(screen.getByTestId('chip-status')).toHaveTextContent('Open');
    expect(screen.queryByTestId('chip-type')).not.toBeInTheDocument();
  });

  it('given the filters have their current default values, the reset button is hidden and only appears once a non-default value is set', async () => {
    // ARRANGE
    vi.spyOn(window.history, 'pushState').mockImplementation(() => {});
    vi.spyOn(axios, 'get').mockResolvedValue({
      data: createTicketListResponse(
        createPaginatedData([createTicketListEntry()], {
          currentPage: 1,
          lastPage: 1,
          perPage: 50,
          total: 1,
        }),
      ),
    });

    renderTicketIndexRoot();

    expect(screen.queryByTestId('reset-all-filters')).not.toBeInTheDocument();

    // ACT
    await openPropertySubmenu(0);
    await userEvent.click(screen.getByRole('menuitem', { name: /resolved/i }));

    // ASSERT
    expect(screen.getByTestId('reset-all-filters')).toBeVisible();
  });

  it('given the user adds a status filter, refetches with it and syncs the URL', async () => {
    // ARRANGE
    const pushStateSpy = vi.spyOn(window.history, 'pushState').mockImplementation(() => {});

    const getSpy = vi.spyOn(axios, 'get').mockResolvedValue({
      data: createTicketListResponse(
        createPaginatedData([createTicketListEntry({ id: 4001 })], {
          currentPage: 1,
          lastPage: 1,
          perPage: 50,
          total: 1,
        }),
      ),
    });

    renderTicketIndexRoot({
      paginatedTickets: createPaginatedData([createTicketListEntry({ id: 1001 })], {
        currentPage: 1,
        lastPage: 1,
        perPage: 50,
        total: 1,
      }),
    });

    // ACT
    await openPropertySubmenu(0);
    await userEvent.click(screen.getByRole('menuitem', { name: /quarantined/i }));

    // ASSERT
    await waitFor(() => {
      expect(getSpy).toHaveBeenCalledWith([
        'api.ticket.index',
        { scope: 'all', 'filter[status]': 'quarantined', 'filter[type]': '0', 'page[number]': 1 },
      ]);
    });

    await waitFor(() => {
      expect(screen.getByRole('link', { name: 'Ticket #4001' })).toBeVisible();
    });

    expect(pushStateSpy).toHaveBeenCalledWith(
      { inertia: true },
      '',
      expect.stringContaining('filter%5Bstatus%5D=quarantined'),
    );

    expect(pushStateSpy).not.toHaveBeenCalledWith(
      { inertia: true },
      '',
      expect.stringContaining('filter%5Btype%5D'),
    );
  });

  it('given the user clears a chip, returns that filter to its default value', async () => {
    // ARRANGE
    vi.spyOn(window.history, 'pushState').mockImplementation(() => {});

    const getSpy = vi.spyOn(axios, 'get').mockResolvedValue({
      data: createTicketListResponse(
        createPaginatedData([createTicketListEntry()], {
          currentPage: 1,
          lastPage: 1,
          perPage: 50,
          total: 1,
        }),
      ),
    });

    renderTicketIndexRoot();

    // ACT
    await userEvent.click(screen.getByRole('button', { name: 'Remove Status filter' }));

    // ASSERT
    await waitFor(() => {
      expect(getSpy).toHaveBeenCalledWith([
        'api.ticket.index',
        { scope: 'all', 'filter[status]': 'all', 'filter[type]': '0', 'page[number]': 1 },
      ]);
    });

    expect(screen.queryByTestId('chip-status')).not.toBeInTheDocument();
  });

  it('given the user is not on the first page and sets a filter value, returns the user to the first page', async () => {
    // ARRANGE
    vi.spyOn(window.history, 'pushState').mockImplementation(() => {});

    const getSpy = vi.spyOn(axios, 'get').mockResolvedValue({
      data: createTicketListResponse(
        createPaginatedData([createTicketListEntry()], {
          currentPage: 1,
          lastPage: 3,
          perPage: 50,
          total: 150,
        }),
      ),
    });

    renderTicketIndexRoot({
      paginatedTickets: createPaginatedData([createTicketListEntry()], {
        currentPage: 2,
        lastPage: 3,
        perPage: 50,
        total: 150,
      }),
      ziggy: createZiggyProps({ query: { 'page[number]': '2' } }),
    });

    // ACT
    await openPropertySubmenu(0);
    await userEvent.click(screen.getByRole('menuitem', { name: /resolved/i }));

    // ASSERT
    await waitFor(() => {
      expect(getSpy).toHaveBeenCalledWith([
        'api.ticket.index',
        { scope: 'all', 'filter[status]': 'resolved', 'filter[type]': '0', 'page[number]': 1 },
      ]);
    });
  });

  it('given the query returns fresh state counts, the status value list shows them', async () => {
    // ARRANGE
    vi.spyOn(window.history, 'pushState').mockImplementation(() => {});
    vi.spyOn(axios, 'get').mockResolvedValue({
      data: createTicketListResponse(
        createPaginatedData([createTicketListEntry()], {
          currentPage: 1,
          lastPage: 1,
          perPage: 50,
          total: 1,
        }),
        { stateCounts: createTicketListStateCounts({ unresolved: 99, resolved: 5 }) },
      ),
    });

    renderTicketIndexRoot({
      stateCounts: createTicketListStateCounts({ unresolved: 7, resolved: 3 }),
    });

    // ACT
    await openPropertySubmenu(0);
    await userEvent.click(screen.getByRole('menuitem', { name: /resolved/i }));
    await userEvent.click(screen.getByTestId('chip-status-value'));

    // ASSERT
    await waitFor(() => {
      expect(screen.getByRole('menuitem', { name: /open/i })).toHaveTextContent('99');
    });
  });

  it('given the user advances to the next page, fetches the next page from the API and syncs the URL', async () => {
    // ARRANGE
    const pushStateSpy = vi.spyOn(window.history, 'pushState').mockImplementation(() => {});

    const getSpy = vi.spyOn(axios, 'get').mockResolvedValue({
      data: createTicketListResponse(
        createPaginatedData([createTicketListEntry({ id: 2001 })], {
          currentPage: 2,
          lastPage: 3,
          perPage: 50,
          total: 150,
        }),
      ),
    });

    renderTicketIndexRoot({
      paginatedTickets: createPaginatedData([createTicketListEntry({ id: 1001 })], {
        currentPage: 1,
        lastPage: 3,
        perPage: 50,
        total: 150,
      }),
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: 'Go to next page' }));

    // ASSERT
    await waitFor(() => {
      expect(getSpy).toHaveBeenCalledWith([
        'api.ticket.index',
        { scope: 'all', 'filter[status]': 'unresolved', 'filter[type]': '0', 'page[number]': 2 },
      ]);
    });

    await waitFor(() => {
      expect(getSpy).toHaveBeenCalledWith([
        'api.ticket.index',
        { scope: 'all', 'filter[status]': 'unresolved', 'filter[type]': '0', 'page[number]': 3 },
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

    await act(async () => {
      window.dispatchEvent(new PopStateEvent('popstate'));
    });

    await waitFor(() => {
      expect(screen.getByRole('link', { name: 'Ticket #1001' })).toBeVisible();
    });
  });

  it('given the user types a page number instead of clicking, the query fetches the page', async () => {
    // ARRANGE
    vi.spyOn(window.history, 'pushState').mockImplementation(() => {});

    const getSpy = vi.spyOn(axios, 'get').mockResolvedValue({
      data: createTicketListResponse(
        createPaginatedData([createTicketListEntry({ id: 3001 })], {
          currentPage: 3,
          lastPage: 3,
          perPage: 50,
          total: 150,
        }),
      ),
    });

    renderTicketIndexRoot({
      paginatedTickets: createPaginatedData([createTicketListEntry({ id: 1001 })], {
        currentPage: 1,
        lastPage: 3,
        perPage: 50,
        total: 150,
      }),
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
          { scope: 'all', 'filter[status]': 'unresolved', 'filter[type]': '0', 'page[number]': 3 },
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
      data: createTicketListResponse(
        createPaginatedData([createTicketListEntry()], {
          currentPage: 2,
          lastPage: 3,
          perPage: 50,
          total: 150,
        }),
      ),
    });

    renderTicketIndexRoot({
      paginatedTickets: createPaginatedData([createTicketListEntry()], {
        currentPage: 1,
        lastPage: 3,
        perPage: 50,
        total: 150,
      }),
      ziggy: createZiggyProps({ query: { filter: { status: 'resolved' }, sort: 'state' } }),
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
          'filter[type]': '0',
          'page[number]': 2,
        },
      ]);
    });
  });
});
