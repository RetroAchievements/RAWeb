import { route } from 'ziggy-js';

import { render, screen } from '@/test';
import { createGame, createTicketListEntry } from '@/test/factories';

import { TicketShowBreadcrumbs } from './TicketShowBreadcrumbs';

describe('Component: TicketShowBreadcrumbs', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <TicketShowBreadcrumbs ticket={createTicketListEntry({ state: 'open' })} />,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('routes the root crumb to the unfiltered ticket list', () => {
    // ARRANGE
    render(<TicketShowBreadcrumbs ticket={createTicketListEntry({ state: 'resolved' })} />);

    // ASSERT
    expect(screen.getByRole('link', { name: 'Tickets' })).toHaveAttribute(
      'href',
      expect.stringContaining('tickets.index'),
    );
    expect(route).toHaveBeenCalledWith('tickets.index');
  });

  it('given a ticket for a game, routes the game crumb to the unfiltered game ticket list', () => {
    // ARRANGE
    render(
      <TicketShowBreadcrumbs
        ticket={createTicketListEntry({
          state: 'resolved',
          game: createGame({ id: 812, title: 'Chrono Trigger' }),
        })}
      />,
    );

    // ASSERT
    expect(screen.getByRole('link', { name: 'Chrono Trigger' })).toHaveAttribute(
      'href',
      expect.stringContaining('game.tickets'),
    );
    expect(route).toHaveBeenCalledWith('game.tickets', { game: 812 });
  });

  it('given a closed achievement ticket, routes the achievement crumb with all status filter', () => {
    // ARRANGE
    render(
      <TicketShowBreadcrumbs
        ticket={createTicketListEntry({
          state: 'closed',
          ticketableType: 'achievement',
          ticketableId: 4402,
          ticketableTitle: 'Dragon Slayer',
        })}
      />,
    );

    // ASSERT
    expect(screen.getByRole('link', { name: 'Dragon Slayer' })).toHaveAttribute(
      'href',
      expect.stringContaining('achievement.tickets'),
    );
    expect(route).toHaveBeenCalledWith('achievement.tickets', {
      achievement: 4402,
      filter: { status: 'all' },
    });
  });

  it('given a leaderboard ticket, omits the leaderboard breadcrumb item', () => {
    // ARRANGE
    render(
      <TicketShowBreadcrumbs
        ticket={createTicketListEntry({
          ticketableType: 'leaderboard',
          ticketableTitle: 'Grand Prix Champion',
        })}
      />,
    );

    // ASSERT
    expect(screen.queryByRole('link', { name: 'Grand Prix Champion' })).not.toBeInTheDocument();
  });

  it('displays the ticket identifier as the active terminal breadcrumb', () => {
    // ARRANGE
    render(<TicketShowBreadcrumbs ticket={createTicketListEntry({ id: 888, state: 'open' })} />);

    // ASSERT
    expect(screen.getByText('Ticket #888')).toBeVisible();
  });
});
