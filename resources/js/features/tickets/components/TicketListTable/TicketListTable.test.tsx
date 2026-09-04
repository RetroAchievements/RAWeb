import type { ColumnDef } from '@tanstack/react-table';
import { FC } from 'react';
import { route } from 'ziggy-js';

import { render, screen } from '@/test';
import {
  createGame,
  createPaginatedData,
  createSystem,
  createTicketListEntry,
  createUser,
} from '@/test/factories';

import { useTicketListColumnDefinitions } from '../../hooks/useTicketListColumnDefinitions';
import { TICKET_LIST_COLUMN_IDS } from '../../utils/ticketListColumnIds';
import { TicketListTable } from './TicketListTable';

const allVisible = Object.fromEntries(TICKET_LIST_COLUMN_IDS.map((id) => [id, true]));
const noneVisible = Object.fromEntries(TICKET_LIST_COLUMN_IDS.map((id) => [id, false]));

interface TestHarnessProps {
  columnVisibility?: Record<string, boolean>;
  emptyStateNode?: React.ReactNode;
  isFetching?: boolean;
  lastPage?: number;
  paginatorNode?: React.ReactNode;
  tickets?: App.Platform.Data.TicketListEntry[];
}

const TestHarness: FC<TestHarnessProps> = ({
  emptyStateNode,
  isFetching,
  paginatorNode,
  columnVisibility = { ...noneVisible, id: true, ticketable: true, age: true },
  lastPage = 1,
  tickets = [createTicketListEntry()],
}) => {
  const columnDefinitions: ColumnDef<App.Platform.Data.TicketListEntry>[] =
    useTicketListColumnDefinitions();

  const paginatedTickets = createPaginatedData(tickets, {
    lastPage,
    currentPage: 1,
    perPage: 50,
    total: tickets.length,
  });

  return (
    <TicketListTable
      columnDefinitions={columnDefinitions}
      columnVisibility={columnVisibility}
      paginatedTickets={paginatedTickets}
      emptyStateNode={emptyStateNode}
      isFetching={isFetching}
      paginatorNode={paginatorNode}
    />
  );
};

describe('Component: TicketListTable', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<TestHarness />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the visible columns are id, ticketable, and age, the header has exactly those three labels in order', () => {
    // ARRANGE
    render(
      <TestHarness columnVisibility={{ ...noneVisible, id: true, ticketable: true, age: true }} />,
    );

    // ASSERT
    const headers = screen.getAllByRole('columnheader');
    expect(headers.map((header) => header.textContent)).toEqual(['ID', 'Issue with', 'Age']);
  });

  it('given every column is visible, renders all six headers in registry order', () => {
    // ARRANGE
    render(<TestHarness columnVisibility={allVisible} />);

    // ASSERT
    expect(screen.getAllByRole('columnheader').map((header) => header.textContent)).toEqual([
      'ID',
      'Issue with',
      'Game',
      'Developer',
      'Reporter',
      'Age',
    ]);
  });

  it('given zero rows, shows the empty state copy and no table', () => {
    // ARRANGE
    render(<TestHarness tickets={[]} />);

    // ASSERT
    expect(screen.getByText('No tickets match these filters.')).toBeVisible();
    expect(screen.queryByRole('table')).not.toBeInTheDocument();
  });

  it('given zero rows and a custom empty state node, renders that node instead', () => {
    // ARRANGE
    render(<TestHarness tickets={[]} emptyStateNode={<p>Nothing to resolve.</p>} />);

    // ASSERT
    expect(screen.getByText('Nothing to resolve.')).toBeVisible();
    expect(screen.queryByText('No tickets match these filters.')).not.toBeInTheDocument();
  });

  it('given a row, both the row and the id link to the ticket', () => {
    // ARRANGE
    const ticket = createTicketListEntry({ id: 512, ticketableTitle: 'Beat the first boss' });

    render(<TestHarness tickets={[ticket]} />);

    // ASSERT
    const rowLinkEl = screen.getByRole('link', { name: 'Ticket #512' });
    expect(rowLinkEl).toHaveAttribute('href', expect.stringContaining('ticket.show'));

    const idLinkEl = screen.getByRole('link', { name: '512' });
    expect(idLinkEl).toHaveAttribute('href', expect.stringContaining('ticket.show'));
    expect(route).toHaveBeenCalledWith('ticket.show', { ticket: 512 });

    const ticketLinks = screen
      .getAllByRole('link')
      .filter((linkEl) => linkEl.getAttribute('href')?.includes('ticket.show'));
    expect(ticketLinks).toHaveLength(2);
  });

  it('given an achievement ticket, links the badge and title to the achievement page', () => {
    // ARRANGE
    const ticket = createTicketListEntry({
      ticketableType: 'achievement',
      ticketableId: 777,
      ticketableTitle: 'Ring Collector',
      ticketableBadgeUrl: 'https://example.com/badge.png',
    });

    render(
      <TestHarness
        tickets={[ticket]}
        columnVisibility={{ ...noneVisible, id: true, ticketable: true }}
      />,
    );

    // ASSERT
    const linkEl = screen.getByRole('link', { name: 'Ring Collector' });
    expect(linkEl).toHaveAttribute('href', expect.stringContaining('achievement.show'));
    expect(route).toHaveBeenCalledWith('achievement.show', { achievement: 777 });

    const badgeEls = screen
      .getAllByRole('presentation')
      .filter((el) => el.getAttribute('src') === 'https://example.com/badge.png');
    expect(badgeEls).toHaveLength(2);
  });

  it('given a leaderboard ticket, prefixes the title as plain text', () => {
    // ARRANGE
    const ticket = createTicketListEntry({
      ticketableType: 'leaderboard',
      ticketableId: 88,
      ticketableTitle: 'Fastest lap',
      ticketableBadgeUrl: null,
    });

    render(<TestHarness tickets={[ticket]} />);

    // ASSERT
    expect(screen.getAllByText('(LB) Fastest lap')[0]).toBeVisible();
    expect(screen.queryByRole('link', { name: /Fastest lap/ })).not.toBeInTheDocument();
    expect(screen.queryByRole('img', { name: 'Fastest lap' })).not.toBeInTheDocument();
  });

  it('given a reporter and a developer, both user cells link to the user page', () => {
    // ARRANGE
    const ticket = createTicketListEntry({
      ticketableTitle: 'Ring Collector',
      author: createUser({ displayName: 'Dev' }),
      reporter: createUser({ displayName: 'Scott' }),
    });

    render(
      <TestHarness
        tickets={[ticket]}
        columnVisibility={{
          ...noneVisible,
          id: true,
          ticketable: true,
          developer: true,
          reporter: true,
        }}
      />,
    );

    // ASSERT
    expect(screen.getByRole('link', { name: /scott/i })).toHaveAttribute(
      'href',
      expect.stringContaining('user.show'),
    );
    expect(screen.getByRole('link', { name: /dev/i })).toBeVisible();
    expect(route).toHaveBeenCalledWith('user.show', ['Scott']);
    expect(route).toHaveBeenCalledWith('user.show', ['Dev']);
  });

  it('given a null reporter and a null author, shows the deleted user fallback and no user links', () => {
    // ARRANGE
    const ticket = createTicketListEntry({
      id: 640,
      author: null,
      reporter: null,
      ticketableTitle: 'Some achievement',
    });

    render(
      <TestHarness
        tickets={[ticket]}
        columnVisibility={{
          ...noneVisible,
          id: true,
          ticketable: true,
          developer: true,
          reporter: true,
        }}
      />,
    );

    // ASSERT
    expect(screen.getAllByText('Deleted user')).toHaveLength(2);
    expect(screen.getAllByRole('link')).toHaveLength(3);
    expect(screen.getByRole('link', { name: 'Ticket #640' })).toBeVisible();
    expect(screen.getByRole('link', { name: '640' })).toBeVisible();
    expect(screen.getByRole('link', { name: 'Some achievement' })).toBeVisible();
    expect(route).not.toHaveBeenCalledWith('user.show', expect.anything());
  });

  it('given a game with a badge and a short system name, links to the game and shows the system name inline', () => {
    // ARRANGE
    const ticket = createTicketListEntry({
      game: createGame({
        id: 1234,
        title: 'Sonic the Hedgehog',
        badgeUrl: 'https://example.com/game.png',
        system: createSystem({ name: 'Mega Drive', nameShort: 'MD' }),
      }),
      ticketableBadgeUrl: null,
    });

    render(
      <TestHarness
        tickets={[ticket]}
        columnVisibility={{ ...noneVisible, id: true, ticketable: true, game: true }}
      />,
    );

    // ASSERT
    const linkEl = screen.getByRole('link', { name: /sonic the hedgehog/i });
    expect(linkEl).toHaveAttribute('href', expect.stringContaining('game.show'));
    expect(route).toHaveBeenCalledWith('game.show', { game: 1234 });

    expect(screen.getByRole('img', { name: 'Sonic the Hedgehog' })).toHaveAttribute(
      'src',
      'https://example.com/game.png',
    );
    expect(screen.getByText('· MD')).toBeVisible();
  });

  it('given the id column is hidden, the state glyph leads the row and the header keeps its slot', () => {
    // ARRANGE
    const ticket = createTicketListEntry({ state: 'open' });

    render(
      <TestHarness
        tickets={[ticket]}
        columnVisibility={{ ...noneVisible, ticketable: true, age: true }}
      />,
    );

    // ASSERT
    expect(screen.getAllByRole('columnheader').map((el) => el.textContent)).toEqual([
      'Issue with',
      'Age',
    ]);
    expect(screen.getAllByRole('img', { name: 'Open' })).toHaveLength(2);
  });

  it('given a new page is being fetched, marks the table busy and dims it', () => {
    // ARRANGE
    render(<TestHarness isFetching={true} />);

    // ASSERT
    expect(screen.getByRole('table')).toHaveAttribute('aria-busy', 'true');
    expect(screen.getByRole('table')).toHaveClass('opacity-50');
  });

  it('given more than one page exists, renders the given paginator element', () => {
    // ARRANGE
    const { rerender } = render(
      <TestHarness lastPage={5} paginatorNode={<div data-testid="paginator" />} />,
    );

    // ASSERT
    expect(screen.getByTestId('paginator')).toBeVisible();

    // it should be hidden for a single page
    rerender(<TestHarness lastPage={1} paginatorNode={<div data-testid="paginator" />} />);
    expect(screen.queryByTestId('paginator')).not.toBeInTheDocument();
  });

  it('given a ticket whose reporter was deleted, the mobile row omits the avatar', () => {
    // ARRANGE
    const ticket = createTicketListEntry({
      ticketableType: 'achievement',
      ticketableBadgeUrl: null,
      reporter: null,
    });

    render(<TestHarness tickets={[ticket]} columnVisibility={{ ...noneVisible, id: true }} />);

    // ASSERT
    expect(screen.queryByRole('presentation')).not.toBeInTheDocument();
  });
});
